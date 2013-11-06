<?php

namespace Snabb\Caching;

class FilesystemCache extends MemoryCache {
  
  private $filepath;

  public function __construct($filepath) {
    $this->filepath = $filepath;
    if(file_exists($filepath))
      $this->data = unserialize(file_get_contents($this->filepath));
  }
  
  public function __destruct() {
    file_put_contents($this->filepath, serialize($this->data), LOCK_EX); #todo exception cannot write to file, unseriazable object e.g. PDOStatement
  }
}