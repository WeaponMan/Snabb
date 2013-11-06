<?php

namespace Snabb\Caching;

interface Cache extends \ArrayAccess {
  public function read($key);
  public function readAll();
  public function write($key, $value, $expiration = null);
  public function exists($key);
  public function delete($key);
  public function clear();
}