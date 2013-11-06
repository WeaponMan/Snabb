<?php
#todo exception handling

namespace Snabb\Autoloading;

class NativeLoader extends Loader {
  
  public function __invoke($class_name) {
    if(!class_exists($class_name, false)) {
      $old_include_path = set_include_path(implode(PATH_SEPARATOR, $this->path));
      spl_autoload($class_name, implode(',', $this->extensions)); #spl extension, throws
      set_include_path($old_include_path);
    }
  }
}