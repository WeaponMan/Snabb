<?php

abstract class PropertyReader {

  protected static $__getters = array();

  public function __get($name) {
    if(in_array($name, static::$__getters))
      return $this->$name;
    trigger_error('Cannot access private property '.get_class($this).'::$'.$name, E_USER_ERROR);
  }
}