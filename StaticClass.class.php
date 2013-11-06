<?php

namespace Snabb;

abstract class StaticClass extends Snabb\Object {
   final public function __construct() {
    throw new StaticClassException();
  }
}