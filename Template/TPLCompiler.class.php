<?php
final class TPLCompiler {

  private $php_start_tag;
  private $php_end_tag;
  private $left_delimiter;
  private $right_delimiter;
  private $noescape_modifier;
  private static $var_regex_noreference = '\\$(?!\\d)[a-z_\\d]+(?:(?:\\.(?:(?:\\$(?!\\d)[a-z_\\d]+)|(?:[a-z_\\d]+)))|(?:\\-\\>(?!\\d)[a-z_\\d]+(?:\\(\\))?))*(?:\\|[a-z]+(?:\\:(?:(?:".*")|(?:\'.*\'))?))*';
  private static $var_regex = '\\$((?!\\d)[a-z_\\d]+)((?:(?:\\.(?:(?:\\$(?!\\d)[a-z_\\d]+)|(?:[a-z_\\d]+)))|(?:\\-\\>(?!\\d)[a-z_\\d]+(?:\\(\\))?))*)((?:\\|[a-z]+(?:\\:("|\').*\\4)?)*)';
                 #$1=var_name, $2=keys+properties+methods, $3=modifiers, $4=parameter_delimeter
  private static $assignable_var_regex = '\\$(?!\\d)[a-z_\\d]+(?:(?:\\.(?:(?:\\$(?!\\d)[a-z_\\d]+)|(?:(?!\\$)[^\\.\\s\\|\\>]+)))|(?:\\-\\>(?!\\d)[a-z_\\d]+))*';
  private static $constant_regex = '#((?:(?!\\d)[\\w\\x7f-\\xff]+\\:\\:)?(?!\\d)[\\w\\x7f-\\xff]+)#((?:\\|[a-z]+(?:\\:("|\').*\\3)?)*)';
                 #$1=constant_name, $2=modifiers, $3=parameter_delimeter
  private static $modifier_regex = '~([a-z]+)\\:("|\')(.*)\\2~uis';
                 #$1=modifier_name, $2=param delimiter, $3=parameter

  public function __construct($php_start_tag = '<?php', $php_end_tag = '?>', $left_delimiter = '{', $right_delimiter = '}', $noescape_modifier = 'noescape') {
    $this->php_start_tag = $php_start_tag;
    $this->php_end_tag = $php_end_tag;
    $this->right_delimiter = $right_delimiter;
    $this->left_delimiter = $left_delimiter;
    $this->noescape_modifier = $noescape_modifier;
  }
  public function compile($tpl_filepath, $cached_tpl_filepath) {
    $left_delimiter_replacement = '@'.generate(10, null).'@';
    $right_delimiter_replacement = '@'.generate(10, null).'@';
    $tpl_content = preg_replace(array('~\\\\'.preg_quote($this->left_delimiter).'~Ds', '~\\\\'.preg_quote($this->right_delimiter).'~Ds', '~\\\\'.preg_quote($left_delimiter_replacement).'~Ds', '~\\\\'.preg_quote($right_delimiter_replacement).'~Ds'), array($left_delimiter_replacement, $right_delimiter_replacement, '\\'.$this->right_delimiter, '\\'.$this->left_delimiter), file_get_contents($tpl_filepath));
    $html = preg_split('~'.preg_quote($this->left_delimiter).'.*?'.preg_quote($this->right_delimiter).'~Ds', $tpl_content);
    preg_match_all('~'.preg_quote($this->left_delimiter).'\\s*(.*?)\\s*'.preg_quote($this->right_delimiter).'~Ds', $tpl_content, $to_parse);
    list(,$to_parse) = $to_parse;

    for($foreach_from_variables = $opened_tags = array(), $inner_php = false, $foreach_level = $if_level = $while_level = $i = 0, $to_write = $html[0], $size = count($to_parse); $i < $size; $i++) {
      if(preg_match('~^php$~Dsi', $to_parse[$i])) { #inner php start tag parse
        if($inner_php)
          trigger_error('Unexpected inner php start tag, inner php is already active!', E_USER_ERROR);
        else {
          $inner_php = true;
          $to_write .= $this->php_start_tag.' ';
        }
      }

      if(!$inner_php) {
        #default_fn parse
        if(preg_match('~^(\'|")(.+)\\1$~Ds', $to_parse[$i], $quote_match))
          if(preg_match('~[^\\\\]'.$quote_match[1].'~s', $quote_match[2]))
            trigger_error('TPL parse error: unexpected delimiter ('.$quote_match[1].')', E_USER_ERROR);
          else
            $to_parse[$i] = 'echo $this->autoEscape($this->default_fn('.self::string_parse($quote_match[2], $quote_match[1]).'));';
        #var_parse
        elseif(preg_match('~^'.self::$var_regex.'$~uis', $to_parse[$i])) {
          $parsed_var = $this->var_parse($to_parse[$i], $noescape);
          $to_parse[$i] = 'echo '.($noescape? $parsed_var:'$this->autoEscape('.$parsed_var.')').';';
        }
        #if parse
        elseif(preg_match('~^if\\s+(.+)$~Dsi', $to_parse[$i], $if_match)) {
          $to_parse[$i] = 'if('.$this->var_parse($if_match[1]).') {';
          $opened_tags[] = 'if';
          $if_level++;
        }
        #else parse
        elseif($to_parse[$i] === 'else') {
          if(($last_tag = array_last_item($opened_tags)) !== 'if')
            trigger_error('Unexpected "'.$to_parse[$i].'", '.$last_tag.' not closed!', E_USER_ERROR);
          elseif($if_level > 0)
            $to_parse[$i] = '} else {';
          else
            trigger_error('Unexpected "'.$to_parse[$i].'", not in "if"!', E_USER_ERROR);
        }
        #closing tags parse (/if, /foreach, ...)
        elseif(preg_match('~^/(if|foreach|while)$~Dsi', $to_parse[$i], $close_tag_match)) {
          if(($last_item = array_last_item($opened_tags)) === null)
            trigger_error('Unexpected '.$to_parse[$i].', start tag wasnt found!', E_USER_ERROR);
          elseif($last_item === $close_tag_match[1]) {
            array_pop($opened_tags);
            $to_parse[$i] = '}';
            ${$last_item.'_level'}--;
          }
          else
            trigger_error('Unexpected "'.$to_parse[$i].'", last opened tag was "'.$last_item.'"!', E_USER_ERROR);
        }
        #constant_parse
        elseif(preg_match('~^'.self::$constant_regex.'$~Dsi', $to_parse[$i])) {
          $constant_name = $this->constant_parse($to_parse[$i], $noescape);
          $to_parse[$i] = 'echo '.($noescape? $constant_name:'$this->autoEscape('.$constant_name.')').';';
        }
        #foreach parse
        elseif(preg_match('~^foreach\\s+('.self::$var_regex_noreference.')\\s+as\\s+(?:('.self::$assignable_var_regex.')\\s+\\=\\>\\s+)?('.self::$assignable_var_regex.')$~Dsi', $to_parse[$i], $foreach_match)) {
          list(,$foreach_from_variables[]) = $foreach_match = $this->var_parse(array_selective_keys($foreach_match, array(1, 2, 3)));
          $to_parse[$i] = 'foreach('.$foreach_match[1].' as ';
          if($foreach_match[2] !== '')
            $to_parse[$i] .= $foreach_match[2].' => ';
          $to_parse[$i] .= $foreach_match[3].') {';
          $opened_tags[] = 'foreach';
          $foreach_level++;
        }
        #while parse
        elseif(preg_match('~^while\\s+(.+)$~Dsi', $to_parse[$i], $while_match)) {
          $to_parse[$i] = 'while('.$this->constant_parse($this->var_parse($while_match[1])).') {';
          $opened_tags[] = 'while';
          $while_level++;
        }
        #elseif parse
        elseif(preg_match('~^else\\s*if\\s+(.+)$~is', $to_parse[$i], $elseif_match)) {
          if(($last_tag = array_last_item($opened_tags)) !== 'if')
            trigger_error('Unexpected "'.$to_parse[$i].'", "'.$last_tag.'" not closed!', E_USER_ERROR);
          elseif($if_level > 0)
            $to_parse[$i] = '} elseif('.$this->constant_parse($this->var_parse($elseif_match[1])).') {';
          else
            trigger_error('Unexpected "'.$to_parse[$i].'", not in "if"!', E_USER_ERROR);
        }
        #foreachelse
        elseif(preg_match('~^foreachelse$~Dsi', $to_parse[$i])) {
          if(($last_tag = array_last_item($opened_tags)) === 'foreach') {
            $to_parse[$i] = '} if(empty('.array_last_item($foreach_from_variables).')) {';
            array_pop($foreach_from_variables);
          }
          else
            trigger_error('Unexpected "'.$to_parse[$i].'", "'.$last_tag.'" not closed!', E_USER_ERROR);
        }
        #break & continue parse
        elseif(preg_match('~^(break|continue)(?:\\s+(\\d+))?$~Dis', $to_parse[$i], $loop_driver_level_match)) {
          if($while_level+$foreach_level < 1)
            trigger_error('Unexpected "'.$to_parse[$i].'", not in a loop!', E_USER_ERROR);
          elseif($loop_driver_level_match[2] === '') {
            $to_parse[$i] = $loop_driver_level_match[1].';';
            $parsed = true;
          }
          elseif((int)$loop_driver_level_match[2] < 1)
            trigger_error(ucfirst($loop_driver_level_match[1]).' accepts only numbers higher than 0!', E_USER_ERROR);
          elseif((int)$loop_driver_level_match[2] > $while_level+$foreach_level)
            trigger_error('Cannot '.$loop_driver_level_match[1].' '.$loop_driver_level_match[2].' levels!', E_USER_ERROR);
          else {
            $to_parse[$i] = $loop_driver_level_match[1].' '.(int)$break_level.';';
            $parsed = true;
          }
        }
        else
          trigger_error('Unknown tag "'.$to_parse[$i].'"', E_USER_ERROR);

        //finální spojování html a parsed tags
        $to_write .= $this->php_start_tag.' '.$to_parse[$i].' '.$this->php_end_tag;
      }
      elseif(preg_match('~^/php$~is', $to_parse[$i])) { #inner php end tag parse
        if($inner_php) {
          $inner_php = false;
          $to_write .= ' '.$this->php_end_tag;
        }
        else
          trigger_error('Unexpected inner php end tag, inner php start tag wasnt found!', E_USER_ERROR);
      }

      $to_write .= $html[$i+1];

    }
    if($opened_tags)
      trigger_error('Parse error: there are some unclosed tags ('.implode(', ', $opened_tags).')!', E_USER_ERROR);
    file_put_contents($cached_tpl_filepath, preg_replace('~'.preg_quote($this->php_end_tag.$this->php_start_tag).'~s', '', str_replace(array($left_delimiter_replacement, $right_delimiter_replacement), array($this->left_delimiter, $this->right_delimiter), $to_write)), LOCK_EX);
  }
  private function var_parse($string_or_array, &$noescape = false) {
    $was_string = is_string($string_or_array);
    $string_or_array = (array)$string_or_array;
    foreach($string_or_array as &$source) {
      $no_vars = preg_split('~'.self::$var_regex.'~Dsi', $source);
      preg_match_all('~'.self::$var_regex.'~Dsi', $source, $vars_match, PREG_SET_ORDER);
      for($i = 0, $source = $no_vars[0], $size = count($vars_match); $i < $size; $i++) {
        #keys
        $keys = ($vars_match[$i][2] === '' ? array() : explode('.', preg_replace('~^\\.~', '', $vars_match[$i][2])));
        #properties and methods
        $properties_methods = '';
        if($keys && ($pos = strpos($keys[($last_key = array_last_key($keys))], '->')) !== false){
          list($keys[$last_key], $properties_methods) = str_split_at($keys[$last_key], $pos);
          if($keys[$last_key] === '')
            array_pop($keys);
        }
        #modifiers
        $modifiers = ($vars_match[$i][3] === '' ? array() : explode('|', preg_replace('~^\\|~', '', $vars_match[$i][3])));
        #varname
        $var = '$this->vars['.var_export($vars_match[$i][1], true).']';
        #concat with keys
        foreach($keys as $key)
          $var .= '['.($key[0] === '$' ? '$this->vars['.var_export(substr($key, 1), true).']' : var_export($key, true)).']';
        #concat with methods and properties
        $var .= $properties_methods;
        #add modifiers
        foreach($modifiers as $mod)
          if($mod !== $this->noescape_modifier)
            if(preg_match(self::$modifier_regex, $mod, $mod_match))
              $var = '$this->modifier('.var_export($mod_match[1], true).', '.$var.', '.self::string_parse($mod_match[3], $mod_match[2]).')';
            else
              $var = '$this->modifier('.var_export($mod, true).', '.$var.')';
          elseif(!$noescape)
            $noescape = true;
        #save
        $source .= $var.$no_vars[$i+1];
      }
    }
    if($was_string) {
      return $string_or_array[0];
    }
    return $string_or_array;
  }
  private function constant_parse($string_or_array, &$noescape = false) {
    $was_string = is_string($string_or_array);
    $string_or_array = (array)$string_or_array;
    foreach($string_or_array as &$source) {
      $no_const = preg_split('~'.self::$constant_regex.'~uis', $source);
      preg_match_all('~'.self::$constant_regex.'~uis', $source, $constants_match, PREG_SET_ORDER);
      for($i = 0, $source = $no_const[0], $size = count($constants_match); $i < $size; $i++) {
        #constant name
        $constant = $constants_match[$i][1];
        #modifiers
        $modifiers = ($constants_match[$i][2] === ''? array():explode('|', preg_replace('~^\\|~', '', $constants_match[$i][2])));
        #add modifiers
        foreach($modifiers as $mod)
          if($mod !== $this->noescape_modifier)
            if(preg_match(self::$modifier_regex, $mod, $mod_match))
              $constant = '$this->constModifier('.var_export($mod_match[1], true).', '.$constant.', '.self::string_parse($mod_match[3], $mod_match[2]).')';
            else
              $constant = '$this->constModifier('.var_export($mod, true).', '.$constant.')';
          elseif(!$noescape)
            $noescape = true;
        #save
        $source .= $constant.$no_const[$i+1];
      }
    }
    if($was_string) {
      return $string_or_array[0];
    }
    return $string_or_array;
  }
  private static function string_parse($what, $delimiter) {
    if(preg_match('~[^\\\\]'.preg_quote($delimiter).'~s', $what))
      trigger_error('Unexpected quote delimiter!', E_USER_ERROR);
    return var_export(preg_replace('~([^\\\\])\\\\'.preg_quote($delimiter).'~', '$1'.$delimiter, $what), true);
  }
}