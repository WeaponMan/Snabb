<?php
/**
 * @author Milan Davídek <mydlofous@seznam.cz>
 */
final class RegExp {
  const ORDER_PATTERN = 1;
  const ORDER_SET = 2;

  const SPLIT_NO_EMPTY = 1;
  const SPLIT_DELIM_CAPTURE = 2;
  const SPLIT_OFFSET_CAPTURE = 4;

  const MODIFIER_CASE_INSENSITIVE = 1;
  const MODIFIER_MULTILINE = 2;
  const MODIFIER_DOTALL = 4;
  const MODIFIER_EXTENDED = 8;
  const MODIFIER_ANCHORED = 16;
  const MODIFIER_DOLLAR_ENDONLY = 32;

  const DEFAULT_DELIMITER = '~';

  private static $modifiersChars = array(
    self::MODIFIER_CASE_INSENSITIVE => 'i',
    self::MODIFIER_MULTILINE => 'm',
    self::MODIFIER_DOTALL => 's',
    self::MODIFIER_EXTENDED => 'x',
    self::MODIFIER_ANCHORED => 'A',
    self::MODIFIER_DOLLAR_ENDONLY => 'D',
  );

  private $delimited_pattern;
  private $modifiers;
  private $compiled;

  public static $delimiter = '~';

  public function __construct($pattern, $modifiers = 0) {
    $this->delimited_pattern = self::DEFAULT_DELIMITER.addcslashes($pattern, self::DEFAULT_DELIMITER).self::DEFAULT_DELIMITER;
    $this->modifiers = $modifiers;
    $this->recompile();
  }

  public function __toString() {
    return $this->compiled;
  }

  private function recompile() {
    $this->compiled = $this->delimited_pattern;
    if($this->modifiers > 0)
      foreach(self::$modifiersChars as $num_modifier => $char_modifier)
        if(($num_modifier & $this->modifiers) === $num_modifier)
          $this->compiled .= $char_modifier;
  }

  public function addModifiers($modifiers) {
    $this->modifiers |= $modifiers;
    $this->recompile();
  }

  public function removeModifiers($modifiers) {
    $this->modifiers ^= $this->modifiers & $modifiers;
    $this->recompile();
  }

  public function test($subject) {
    return (bool)preg_match($this, $subject);
  }

  public function matchesCount($subject) {
    return count($this->matches($subject));
  }

  public function &match($subject, $offset_capture = false, $offset = 0, &$count = null) {
    $count = (int)preg_match($this, $subject, $match, $offset_capture ? PREG_OFFSET_CAPTURE : 0, $offset);
    return $match;
  }

  public function &matches($subject, $ordering = self::ORDER_SET, $offset_capture = false, $offset = 0, &$count = null) {
    $count = (int)preg_match_all($this, $subject, $matches, $ordering | ($offset_capture ? PREG_OFFSET_CAPTURE : 0), $offset);
    return $matches;
  }

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

  public function filter($subject, $replacement, $limit = -1, &$count = null) {
    return preg_filter($this, $replacement, $subject, $limit, $count);
  }

  public static function quote($what) {
    return preg_quote($what, self::DEFAULT_DELIMITER);
  }
}