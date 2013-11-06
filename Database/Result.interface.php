<?php
namespace Snabb\Database;

interface Result {
  public function fetch($how = namespace\Connection::FETCH_ASSOC, $parameter = null);
  public function fetchAll($how = namespace\Connection::FETCH_ASSOC, $parameter = null);
  public function nextRowset();
}