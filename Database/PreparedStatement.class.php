<?php
namespace \snabb\Database;

interface PreparedStatement {

  public function bindParam($name, $value, $parameter_type = namespace\Connection::PARAM_PHP_SAME);

  public function execute();

  public function close();
}