<?php
namespace Snabb;

class Object {
  
  protected static $readable_properties = array();
  protected static $writable_properties = array();

  public function getClass() {
    return get_class($this);
  }
  
  public function __set($name, $value) {
    if(property_exists($this, $name)) {
      if(in_array($name, static::$writable_properties))
        $this->$name = $value;
      else
        trigger_error('Cannot write in private property '.$this->getClass().'::$'.$name, E_USER_ERROR);
    }
    else
      trigger_error('Undefined property: '.$this->getClass().'::$'.$name, E_USER_NOTICE);
  }
  
  public function __get($name) {
    if(property_exists($this, $name)) {
      if(in_array($name, static::$readable_properties))
        return $this->$name;
      else
        trigger_error('Cannot read private property '.$this->getClass().'::$'.$name, E_USER_ERROR);
    }
    else
      trigger_error('Undefined property: '.$this->getClass().'::$'.$name, E_USER_NOTICE);
  }
  
  public function __isset($name) {
    return in_array($name, static::$readable_properties) && isset($this->$name);
  }
  
  public function __unset($name) {
    if(in_array($name, static::$writable_properties))
      unset($this->$name);
  }
}