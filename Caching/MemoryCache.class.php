<?php

namespace Snabb\Caching;

class MemoryCache extends \Snabb\Object implements Cache {
  
  protected $data = array();
  
  public function read($key) {
    if(isset($this->data[$key])) {
      if(isset($this->data[$key][1]) && $this->data[$key][1] < time())
        unset($this->data[$key]);
      else
        return $this->data[$key][0];
    }
  }
  
  public function &readAll() {
    $all = array();
    foreach($this->data as $key => $values) {
      if(isset($values[1]) && $values[1] < time())
        unset($this->data[$key]);
      else
        $all[$key] = $values[0];
    }
    return $all;
  }

  public function write($key, $value, $expiration = null) {
    if($value !== null) {
      $this->data[$key] = array($value);
      if($expiration !== null)
        $this->data[$key][] = $expiration;
    }
  }
  
  public function exists($key) {
    return isset($this->data[$key]);
  }
  
  public function delete($key) {
    unset($this->data[$key]);
  }

  public function clear() {
    $this->data = array();
  }
  
  public function offsetExists($offset) {
    return $this->exists($offset);
  }
  
  public function offsetGet($offset) {
    return $this->read($offset);
  }
  
  public function offsetSet($offset, $value) {#todo exception on null offset (array addition operator)
    $this->write($offset, $value);
  }
  
  public function offsetUnset($offset) {
    $this->delete($offset);
  }
}