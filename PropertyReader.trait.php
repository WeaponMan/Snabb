<?php

trait PropertyReader {
  
  protected static $readable_properties = [];
  
  //todo call parent?
  public function __get($name) {
    if(property_exists($this, $name)) {
      if(in_array($name, static::$readable_properties))
        return $this->$name;
      else
        trigger_error('Cannot read private property '.$this->getClass().'::$'.$name, E_USER_ERROR); //todo change to exception
    }
    else
      trigger_error('Undefined property: '.$this->getClass().'::$'.$name, E_USER_NOTICE); //todo change to exception
  }
  
  public function __isset($name) {
    return in_array($name, static::$readable_properties) && isset($this->$name);
  }
}