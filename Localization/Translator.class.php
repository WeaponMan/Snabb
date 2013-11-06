<?php

namespace Snabb\Localization;

/**
 * @author Milan Davídek <6midlan@gmail.com>
 */
class Translator {
  
  private $translations = array();
  private $sample_translations = array();
  
  private $trigger_errors;
  private $sample_filepath;
  
  public function __construct($current_language, array $supported_languges, $translations_dir, $generate_sample = false, $trigger_errors = false, $filename_format = '%s.php', $sample_filename = 'sample') {
    if(($current_language = in_array($current_language, $supported_languges) ? $current_language : $supported_languges[0]) !== $supported_languges[0]) {
      if(file_exists($translations_filepath = $translations_dir.sprintf($filename_format, $current_language)))
        $this->translations = eval(file_get_contents($translations_filepath));
      else
        trigger_error('Translation file "'.$translations_filepath.'" does not exists', E_USER_WARNING);
    }
    if($generate_sample && file_exists($this->sample_filepath = $translations_dir.sprintf($filename_format, $sample_filename)))
      $this->sample_translations = eval(file_get_contents($this->sample_filepath));
    $this->trigger_errors = $trigger_errors;
  }

  public function __destruct() {
    if($this->sample_filepath !== null) {
      ksort($this->sample_translations, SORT_STRING);
      if(file_put_contents($this->sample_filepath, var_export($this->sample_translations, true)) === false)
        trigger_error('Sample translation file "'.$this->sample_filepath.'" could\'t be updated', E_USER_WARNING);
    }
  }
  
  public function __invoke($string) {
    if(!($string instanceof namespace\Literal)) {
      if($this->sample_filepath !== null && !isset($this->sample_translations[$string]))
        $this->sample_translations[$string] = '';
      if(empty($this->translations[$string])) {
        if($this->trigger_errors)
          trigger_error('Translation for "'.$string.'" wasn\'t found', E_USER_NOTICE);
      }
      else
        return $this->translations[$string];
    }
    return $string;
  }
}