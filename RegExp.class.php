<?php
namespace Snabb;
/**
 * @author Milan Davídek <mydlofous@seznam.cz>
 * @copyright (c) 2013, Milan Davídek
 */
final class RegExp extends \Snabb\Object {
  const ORDER_PATTERN = 1;
  const ORDER_SET = 2;

  /**
   * If this flag is set, only non-empty pieces will be returned.
   */
  const SPLIT_NO_EMPTY = 1;
  /**
   * If this flag is set, parenthesized expression in the delimiter pattern will be captured and returned as well.
   */
  const SPLIT_DELIM_CAPTURE = 2;
  /**
   * this flag is set, for every occurring match the appendant string offset will also be returned. Note that this changes the return value in an array where every element is an array consisting of the matched string at offset 0 and its string offset into subject at offset 1.
   */
  const SPLIT_OFFSET_CAPTURE = 4;

  /**
   * Do case-insensitive pattern matching.
   */
  const MODIFIER_CASE_INSENSITIVE = 1;
  /**
   * Treat string as multiple lines. That is, change "^" and "$" from matching the start or end of line only at the left and right ends of the string to matching them anywhere within the string.
   */
  const MODIFIER_MULTILINE = 2;
  /**
   * Treat string as single line. That is, change "." to match any character whatsoever, even a newline, which normally it would not match.
   * Used together with MODIFIER_MULTILINE, they let the "." match any character whatsoever, while still allowing "^" and "$" to match, respectively, just after and just before newlines within the string.
   */
  const MODIFIER_SINGLELINE = 4;
  /**
   * Tells the regular expression parser to ignore most whitespace that is neither backslashed nor within a character class. You can use this to break up your regular expression into (slightly) more readable parts. The # character is also treated as a metacharacter introducing a comment, just as in ordinary PHP code. This also means that if you want real whitespace or # characters in the pattern (outside a character class, where they are unaffected by /x), then you'll either have to escape them (using backslashes) or encode them using octal or hex.
   */
  const MODIFIER_EXTENDED = 8;
  /**
   * If this modifier is set, the pattern is forced to be "anchored", that is, it is constrained to match only at the start of the string which is being searched (the "subject string"). This effect can also be achieved by appropriate constructs in the pattern itself, which is the only way to do it in Perl.
   */
  const MODIFIER_ANCHORED = 16;
  /**
   * If this modifier is set, a dollar metacharacter in the pattern matches only at the end of the subject string. Without this modifier, a dollar also matches immediately before the final character if it is a newline (but not before any other newlines). This modifier is ignored if m modifier is set.
   */
  const MODIFIER_DOLLAR_ENDONLY = 32;
  /**
   * When a pattern is going to be used several times, it is worth spending more time analyzing it in order to speed up the time taken for matching. If this modifier is set, then this extra analysis is performed. At present, studying a pattern is useful only for non-anchored patterns that do nothave a single fixed starting character.
   */
  const MODIFIER_SPEEDUP = 64;
  /**
   * This modifier inverts the "greediness" of the quantifiers so that they are not greedy by default, but become greedy if followed by ?. It can also be set by a question mark behind a quantifier (e.g. .*?).
   */
  const MODIFIER_UNGREEDY = 128;
  /**
   * Any backslash in a pattern that is followed by a letter that has no special meaning causes an error, thus reserving these combinations for future expansion. By default, as in Perl, a backslash followed by a letter with no special meaning is treated as a literal. There are at present no other features controlled by this modifier.
   */
  const MODIFIER_EXTRA = 256;
  /**
   * Pattern strings are treated as UTF-8.
   */
  const MODIFIER_UTF8 = 512;

  private static $modifiersChars = array(
    self::MODIFIER_CASE_INSENSITIVE => 'i',
    self::MODIFIER_MULTILINE => 'm',
    self::MODIFIER_SINGLELINE => 's',
    self::MODIFIER_EXTENDED => 'x',
    self::MODIFIER_ANCHORED => 'A',
    self::MODIFIER_DOLLAR_ENDONLY => 'D',
    self::MODIFIER_SPEEDUP => 'S',
    self::MODIFIER_UNGREEDY => 'U',
    self::MODIFIER_EXTRA => 'X',
    self::MODIFIER_UTF8 => 'u',
  );

  private $delimited_pattern;
  private $modifiers;
  private $compiled;

  /**
   * 
   * @param type $pattern
   * @param type $modifiers [optional]
   * @param type $delimiter [optional]
   */
  public function __construct($pattern, $modifiers = 0, $delimiter = '~') {
    $this->delimited_pattern = $delimiter.addcslashes($pattern, $delimiter).$delimiter;
    $this->modifiers = $modifiers;
    $this->recompile();
  }

  /**
   * 
   * @return string
   */
  public function __toString() {
    return $this->compiled;
  }

  private function recompile() {
    $this->compiled = $this->delimited_pattern;
    if($this->modifiers > 0)
      foreach(array_keys(self::$modifiersChars) as $num_modifier)
        if(($num_modifier & $this->modifiers) === $num_modifier)
          $this->compiled .= self::$modifiersChars[$num_modifier];
  }

  /**
   * 
   * @param type $modifiers
   */
  public function addModifiers($modifiers) {
    $this->modifiers |= $modifiers;
    $this->recompile();
  }

  /**
   * 
   * @param type $modifiers
   */
  public function removeModifiers($modifiers) {
    $this->modifiers ^= $this->modifiers & $modifiers;
    $this->recompile();
  }

  /**
   * 
   * @param type $subject
   * @return bool
   */
  public function test($subject) {
    return (bool)preg_match($this, $subject);
  }

  /**
   * Count and returns the number of full pattern matches.
   * @param string $subject The input string
   * @return int The number of full pattern matches
   */
  public function matchesCount($subject) {
    return (int)preg_match_all($this, $subject);
  }

  /**
   * 
   * @param type $subject
   * @param type $offset_capture [optional]
   * @param type $offset [optional]
   * @param type $count [optional]
   * @return type
   */
  public function &match($subject, $offset_capture = false, $offset = 0, &$count = null) {
    $count = (int)preg_match($this, $subject, $match, $offset_capture ? PREG_OFFSET_CAPTURE : 0, $offset);
    return $match;
  }

  /**
   * 
   * @param type $subject
   * @param type $ordering [optional]
   * @param type $offset_capture [optional]
   * @param type $offset [optional]
   * @param type $count [optional]
   * @return int
   */
  public function &matches($subject, $ordering = self::ORDER_SET, $offset_capture = false, $offset = 0, &$count = null) {
    $count = (int)preg_match_all($this, $subject, $matches, $ordering | ($offset_capture ? PREG_OFFSET_CAPTURE : 0), $offset);
    return $matches;
  }

  /**
   * 
   * @param mixed $subject
   * @param mixed $replacement [optional]
   * @param type $limit
   * @param int $count
   * If specified, this variable will be filled with the number of replacements done.
   * @return mixed 
   * Returns array if the subject parameter is an array, or a string otherwise.
   */
  public function replace($subject, $replacement = '', $limit = -1, &$count = null) {
    if(is_callable($replacement))
      return preg_replace_callback($this, $replacement, $subject, $limit, $count);
    return preg_replace($this, $replacement, $subject, $limit, $count);
  }

  public function split($subject, $limit = -1, $flags = 0) {
    return preg_split($this, $subject, $limit, $flags);
  }

  public function grep(array $input, $invert = false) {
    return preg_grep($this, $input, $invert ? PREG_GREP_INVERT : 0);
  }
  /**
   * Perform a regular expression search and replace
   * @param mixed $subject
   * @param mixed $replacement
   * @param int $limit [optional]
   * @param int $count [optional]
   * @return mixed an array if the <i>subject</i> parameter is an array,
   * @todo PHPdoc
   * or a string otherwise.
   * If no matches are found or an error occurred, an empty array is returned
   * when <i>subject</i> is an array or <b>NULL</b> otherwise.
   */
  public function filter($subject, $replacement, $limit = -1, &$count = null) {
    return preg_filter($this, $replacement, $subject, $limit, $count);
  }
  
  /**
   * Quote regular expression characters
   * @param string $string The input string
   * @return string The quoted string
   */
  public static function quote($string) {
    preg_quote($string);
  }
}