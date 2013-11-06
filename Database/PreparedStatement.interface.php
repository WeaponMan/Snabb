<?php
namespace Snabb\Database;

interface PreparedStatement {

  public function bindParam($name, $value, $type = namespace\Connection::PARAM_PHP_SAME);

  public function execute();

  public function close();
}