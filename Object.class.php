<?php
namespace Snabb;

class Object {

  public function getClass() {
    return get_class($this);
  }
  
  public static function className() {
    return get_called_class();
  }
  
  public function __set($name, $value) {
    if(property_exists($this, $name)) {
      trigger_error('Cannot write in private property '.$this->getClass().'::$'.$name, E_USER_ERROR);
    }
    else
      trigger_error('Undefined property: '.$this->getClass().'::$'.$name, E_USER_NOTICE);
  }
  
  public function __get($name) {
    if(property_exists($this, $name)) {
      trigger_error('Cannot read private property '.$this->getClass().'::$'.$name, E_USER_ERROR); //todo change to exception
    }
    else
      trigger_error('Undefined property: '.$this->getClass().'::$'.$name, E_USER_NOTICE); //todo change to exception
  }
}