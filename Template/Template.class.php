<?php
class Template {
  public $autoescape = true;
  private $tpl_dir;
  private $cache_dir;
  private $TPLCompiler;
  private $vars = array();
  private $autoescape_fn;
  private $default_fn;
  private $modifiers;
  private $messages = array();
  private $css = array();
  private $js = array();

  public function __construct($tpl_dir, $cache_dir, $default_fn = null, array $modifiers = array(), $autoescape_fn = null) {
    list($this->tpl_dir, $this->cache_dir) = preg_replace('~[^/]$~Ds', '$0/', array($tpl_dir, $cache_dir));
    $this->modifiers = $modifiers;
    if($autoescape_fn === null)
      $this->autoescape_fn = function($string) {
        if(defined('ENT_HTML5'))
          return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5);
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
      };
    elseif(!is_callable($autoescape_fn, true))
      trigger_error('Parameter $autoescape_function is not callable!', E_USER_ERROR);
    else
      $this->autoescape_fn = $autoescape_fn;

    if($default_fn === null)
      $this->default_fn = function($string) {
        return $string;
      };
    elseif(!is_callable($default_fn, true))
      trigger_error('Parameter $default_fn is not callable', E_USER_ERROR);
    else
      $this->default_fn = $default_fn;
  }
  public function addJavascript() {
    foreach(func_get_args() as $js)
      if(!in_array($js, $this->js))
        $this->js[] = $js;
  }
  public function addCss() {
    foreach(func_get_args() as $css)
      if(!in_array($css, $this->css))
        $this->css[] = $css;
  }
  public function addMessage(\TemplateMessage $message) {
    $this->messages[] = $message;
  }
  public function clearMessages() {
    $this->messages = array();
  }
  public function renderTop($title = null, $description = null) {
    require $this->tpl_dir.'core/top.php';
  }
  public function renderBottom() {
    require $this->tpl_dir.'core/bottom.php';
  }
  public function renderMenu(\Database\Connection $db, \User $user) {
    require $this->tpl_dir.'core/menu.php';
  }
  public function renderMessages() {
    require $this->tpl_dir.'core/messages.php';
  }
  public function render($tpl_filename, $return = false) {
    if(!file_exists(($tpl_filepath = $this->tpl_dir.$tpl_filename))) {
      trigger_error('Template '.$tpl_filename.' not exists!', E_USER_ERROR);
      return;
    }
    elseif(!file_exists(($cached_tpl_filepath = $this->cache_dir.str_replace('/', '%', $tpl_filename).'.php')) || filemtime($cached_tpl_filepath) <= filemtime($tpl_filepath)) {
      if($this->TPLCompiler === null)
        $this->TPLCompiler = new TPLCompiler();
      $this->TPLCompiler->compile($tpl_filepath, $cached_tpl_filepath);
    }
    if($return)
      ob_start();
    require $cached_tpl_filepath;
    if($return)
      return ob_get_clean();
  }
  public function &renderWithoutEscape($tpl_filename, $return = false) {
    $escaping = $this->autoescape;
    $this->autoescape = false;
    $to_return = $this->render($tpl_filename, $return);
    $this->autoescape = $escaping;
    return $to_return;
  }
  public function assign(array $variables_array) {
    foreach($variables_array as $name => $value)
      $this->vars[(string)$name] = $value;
  }
  function argsValidate(array $args, \Database\Connection $db, \User $user, array $rules = array(), $invalid_link_callback = null) {
    $valid_link = count($args) <= count($rules);
    if($valid_link) {
      foreach($rules as $arg_number => $rule) {
        if(!$rule->testValue($this, var_value($args[$arg_number]))) {
          $valid_link = false;
          break;
        }
      }
    }
    if(!$valid_link) {
      if($invalid_link_callback === null)
        $this->renderInvalidLinkReplacement($db, $user);
      else
        $invalid_link_callback();
    }
  }
  public function renderInvalidLinkReplacement(\Database\Connection $db, \User $user) {
    if(isset($_SERVER['HTTP_REFERER']))
      trigger_error('HTTP referer is set, but url is invalid (referer url: '.$_SERVER['HTTP_REFERER'].' ).');
    $this->clearMessages();
    $this->renderTop('Naplatná adresa URL');
    $this->renderMenu($db, $user);
    $this->render('errors/invalid_link.tpl');
    $this->renderBottom();
    exit;
  }
  private function autoEscape($string) {
    if($this->autoescape)
      return call_user_func($this->autoescape_fn, $string);
    return $string;
  }
  private function modifier($modifier_name, &$variable, $parameter = null) {
    if(isset($this->modifiers[$modifier_name])) {
      $fn = new ReflectionFunction($this->modifiers[$modifier_name]);
      $parameters = $fn->getNumberOfRequiredParameters();
      if($parameters === 1)
        return $this->modifiers[$modifier_name]($variable);
      elseif($parameters === 2)
        return $this->modifiers[$modifier_name]($variable, $parameter);
      else
        trigger_error('Modifier '.$modifier_name.' assigned with '.$parameters.' parameters, but supported is one or two!', E_USER_ERROR);
    }
    else
      trigger_error('Modifier '.$modifier_name.' is not assigned!', E_USER_ERROR);
  }
  private function constModifier($modifier_name, $constant, $parameter = null) {
    return $this->modifier($modifier_name, $constant, $parameter);
  }
  private function default_fn($value) {
    return call_user_func($this->default_fn, $value);
  }
}