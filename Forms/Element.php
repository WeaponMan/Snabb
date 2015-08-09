<?php

namespace Snabb\Forms;

abstract class Element {
   private $attributes;
   private $ruleSet = [];
   
   public function __construct(\Snabb\HTML\ElementAttributes $attributes) {
       $this->attributes = $attributes;
   }
   
   public function addRule($type, $rule, $errorMsg){
       $this->ruleSet[] = [$type, $rule, $errorMsg];
   } 
           
   abstract function valid($method);
   abstract function __toString();
}
