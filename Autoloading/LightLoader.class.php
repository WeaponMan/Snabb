<?php
#todo exception handling

namespace Snabb\Autoloading;

class LightLoader extends Loader {
  
  public function __invoke($class_name) {
    if(!class_exists($class_name, false)) {
      $filepath = str_replace('\\', DIRECTORY_SEPARATOR, $class_name); #todo utf8
      foreach($this->path as $path)
        foreach($this->extensions as $extension) {
          if(file_exists($path.DIRECTORY_SEPARATOR.$filepath.$extension)) {
            require_once $path.DIRECTORY_SEPARATOR.$filepath.$extension;
            return;
          }
        }
    }
  }
}