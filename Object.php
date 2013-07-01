<?php
namespace snabb;

class Object 
{
  protected $_getters = array();
  
  public function &__get($name) 
  {
    if(!in_array($name, $this->_getters) or !isset($this->$$name))
      trigger_error ('Can not access variable '.$name.' in '.__CLASS__);
    else 
      return $this->$$name;
  }
}

