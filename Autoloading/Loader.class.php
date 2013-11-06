<?php

namespace Snabb\Autoloading;

abstract class Loader extends \Snabb\Object {
  protected $path, $extensions;
  private $registred = false;
  
  public function __construct($path, array $extensions = array('.php', '.class.php', '.interface.php', '.trait.php'), $register = true, $prepend = false) {
    $this->path = (array)$path;
    $this->extensions = $extensions;
    if($register)
      $this->register($prepend);
  }
  
  final public function register($prepend) {
    if(!$this->registred)
      $this->registred = spl_autoload_register($this, true, $prepend); #extension spl, throws
  }

  abstract public function __invoke($class_name); #fix php bug #49143

  public function __destruct() {
    if($this->registred)
      spl_autoload_unregister($this); #extension spl
  }
}