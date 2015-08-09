<?php
namespace Snabb\Forms;

class Form {
    
    private $elements = [];
    private $attributes;
    
    public function __construct(\Snabb\HTML\ElementAttributes $attributes){
        $this->attributes = $attributes;
    }
    
    public function addInput($label, $type, $name, $value = null, $className = null, $id = null, $disabled = false){
        
    }
    
    public function addTextArea($label, $name, $value = null, $className = null, $id = null, $disabled = false){
        
    }
    
    public function addSelect($label, $name, array $options, $selected = null, $className = null, $id = null, $disabled = false){
        
    }
    
    public function addCheckBox($label, $name, $value = null, $className = null, $id = null, $disabled = false){
        
    }
    
    public function addRadioButtons($label, $name, array $options, $checked = null, $className = null, $id = null, $disabled = false){
        
    }
    
    public function addSubmit($name, $value = null, $className = null, $id = null, $disabled = false){
        
    }
    
    public function addFileInput($label, $name, $value = null, $className = null, $id = null, $disabled = false){
        
    }
    
    public function __toString() {
        $form = '<form '.$this->attributes.'>';
        
        return $form;
    }
}
