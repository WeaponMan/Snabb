<?php

namespace Snabb\HTML;

class ElementAttributes implements \ArrayAccess {

    private $attrs;

    public function __construct(array $atrributes = []) {
        $this->attrs = $atrributes;
    }

    public function offsetSet($name, $value) {
        if (is_null($name)) {
            trigger_error("ElementAttributes::offsetSet() : Name cant be null.");
        } else {
            $this->attrs[$name] = $value;
        }
    }

    public function offsetExists($name) {
        return isset($this->attrs[$name]);
    }

    public function offsetUnset($name) {
        unset($this->attrs[$name]);
    }

    public function offsetGet($offset) {
        return isset($this->attrs[$name]) ? $this->attrs[$name] : null;
    }

    public function __toString() {
        if ($this->attrs) {
            $bf = '';
            foreach ($this->attrs as $attrName => $attrValue){
                if(isset($attrValue))
                    $bf .= ' '.$attrName.'="'.$attrValue.'"';
                else
                    $bf .= ' '.$attrName;
            }
            return $bf;
        } else {
            return "";
        }
    }

}
