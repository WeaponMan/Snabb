<?php

abstract class Literal {
  private $string;
  public function __construct($string) {
    $this->string = (string)$string;
  }
  public function __toString() {
    return $this->string;
  }
}