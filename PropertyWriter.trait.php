<?php

trait PropertyWriter {
  
  protected static $writable_properties = array();
  
  //todo call parent?
  public function __set($name, $value) {
    if(property_exists($this, $name)) {
      if(in_array($name, static::$writable_properties))
        $this->$name = $value;
      else
        trigger_error('Cannot write in private property '.$this->getClass().'::$'.$name, E_USER_ERROR); //todo change to exception
    }
    else
      trigger_error('Undefined property: '.$this->getClass().'::$'.$name, E_USER_NOTICE); //todo change to exception
  }
  
  public function __unset($name) {
    if(in_array($name, static::$writable_properties))
      unset($this->$name);
  }
}