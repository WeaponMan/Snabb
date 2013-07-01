<?php
/**
 * @author WeaponMan <weaponman@centrum.cz>
 * @author midlan <6midlan@gmail.com>
 * @property-read bool $in_transaction aktivní transakce
 */

namespace Database\MySQLio;

final class Connection extends \Getter implements \Database\Connection {

  protected $errmode;
  protected $in_transaction = false;
  protected $errorInfo;
  protected $errorCode;
  private $mysqli;

  protected static $__getters = array('errmode', 'in_transaction');

  public function __construct($host, $user, $password, $database, $error_reporting = self::ERRMODE_SILENT) {
    $mysqli_driver = new \mysqli_driver();
    $mysqli_driver->report_mode = MYSQLI_REPORT_STRICT;
    try {
      $this->mysqli = new \mysqli('p:'.$host, $user, $password, $database);
    }
    catch(\mysqli_sql_exception $e) {
      throw new \Database\Exception(); #todo messages
    }
    $this->mysqli->set_charset('utf8');
  }

  public function __set($name, $value) {
    if($name === 'errmode') {
      $this->errmode = $value & (self::ERRMODE_WARNING | self::ERRMODE_EXCEPTION);
    }
  }

  private function processError($message = '', $code = 0) {
    if(($this->errmode & self::ERRMODE_WARNING) === self::ERRMODE_WARNING)
      trigger_error($message, E_USER_WARNING);
    if(($this->errmode & self::ERRMODE_EXCEPTION) === self::ERRMODE_EXCEPTION)
      throw new \Database\Exception($message, $code);
  }

  private function mysqliSafeMethodCall($method_name, $params) {
    try {
      return call_user_func_array(array($this->mysqli,$method_name), (array)$params);
    }
    catch(\mysqli_sql_exception $e) {
      $this->processError($e->getMessage(), $e->getCode());
    }
  }

  public function beginTransaction() {
    if($this->in_transaction)
      $this->processError('Transaction already started!');
    $this->exec('START TRANSACTION;') !== false;
  }

  public function createSavepoint($savepoint_name) {
    if(!$this->in_transaction)
      $this->processError(); #todo message transaction isn't opened
    return $this->exec('SAVEPOINT '.$this->backtick($savepoint_name).';') !== false;
  }

  public function commit() {
    if(!$this->in_transaction) {
      $this->processError(); #todo message transaction isn't opened
      return false;
    }
    return $this->exec('COMMIT;') !== false;
  }

  public function rollback($savepoint_name = null) {
    if(!$this->in_transaction)
      $this->processError(); #todo message transaction isn't opened
    return $this->exec('ROLLBACK'.($savepoint_name === null ? null : ' TO SAVEPOINT '.$this->backtick($savepoint_name)).';') !== false;
  }

  public function prepare($sql) {
    return new namespace\PreparedStatement($this, $sql);
  }

  public function query($sql) {
    $this->mysqliSafeMethodCall('multi_query', $sql);
    return new namespace\Result($this->mysqli);
  }

  public function cachedQuery($sql, $seconds) {/*todo*/}

  public function exec($sql) {
    $this->mysqliSafeMethodCall('real_query', $sql);
    #todo return false on error
    return $this->mysqli->affected_rows;
  }

  public function backtick($what) {
    if($what instanceof \Database\Literal)
      return $what;
    return new \Database\Literal('`'.str_replace('`', '``', $what).'`');
  }

  public function bactickArrayValues($array) {
    if(is_array($array)) {
      foreach($array as &$inside)
        $inside = $this->bactickArrayValues($inside);
      return $array;
    }
    return $this->backtick($array);
  }

  public function quote($what, $parameter_type = self::PARAM_PHP_SAME) {
    if($what instanceof \Database\Literal)
      return $what;
    if($what === null)
      return new \Database\Literal('NULL');
    switch($parameter_type) {
      case self::PARAM_BOOL:
        $what = (bool)$what;
        break;
      case self::PARAM_FLOAT:
        $what = (float)$what;
        break;
      case self::PARAM_INT:
        $what = (int)$what;
        break;
      case self::PARAM_STR:
        $what = (string)$what;
      case self::PARAM_PHP_SAME:
        break;
      default:
        throw new \Database\Exception(); #unknown param type
        break;
    }
    if($what === true)
      return new \Database\Literal('TRUE');
    if($what === false)
      return new \Database\Literal('FALSE');
    return new \Database\Literal("'".$this->mysqli->real_escape_string($what)."'");
  }

  public function quoteArrayValues($array, $parameter_type = self::PARAM_PHP_SAME) {
    if(is_array($array)) {
      foreach($array as &$inside)
        $inside = $this->quoteArrayValues($inside, $parameter_type);
      return $array;
    }
    return $this->quote($array, $parameter_type);
  }

  public function sqlColumnsEqual($glue, array $columns, $null_equal_sign = ' = ') {
    $last_key = array_last_key($columns);
    $last_value = array_pop($columns);
    $output = '';
    foreach($columns as $key => $value)
      $output .= $this->backtick($key).($value === null ? $null_equal_sign : ' = ').$this->quote($value).$glue;
    return $output.$this->backtick($last_key).($last_value === null ? $null_equal_sign : ' = ').$this->quote($last_value);
  }

  public function insert($table_name, array $data, $ignore = false) {
    $sql = 'INSERT ';
    if($ignore)
      $sql .= 'IGNORE ';
    $sql .= 'INTO '.$this->backtick($table_name);
    if(array_is_assoc($data))
      return $this->exec($sql.' SET '.$this->sqlColumnsEqual(', ', $data).';');
    if(is_array($data[0])) {
      if(array_is_assoc($data[0]))
        $sql .= '('.implode(', ', $this->bactickArrayValues(array_keys($data[0]))).')';
      $sql .= ' VALUES ';
      $last_row = array_pop($data);
      foreach($data as $row)
        $sql .= '('.implode(', ', $this->quoteArrayValues($row)).'), ';
      return $this->exec($sql.'('.implode(', ', $this->quoteArrayValues($last_row)).');');
    }
    return $this->exec($sql.' VALUES('.implode(', ', $this->quoteArrayValues($data)).');');
  }

  public function update($table_name, array $data, $where = null, $limit = null) {
    $sql = 'UPDATE '.$this->backtick($table_name).' SET '.$this->sqlColumnsEqual(', ', $data);
    if($where !== null)
      $sql .= ' WHERE '.(is_array($where) ? $this->sqlColumnsEqual(' AND ', $where) : $where);
    if($limit !== null)
      $sql .= ' LIMIT '.(int)$limit;
    return $this->exec($sql.';');
  }

  public function countRows($table_name, $where_statement = null) {
    return (int)$this->query('SELECT COUNT(*) FROM '.$this->backtick($table_name).($where_statement === null ? null : ' WHERE '.(is_array($where_statement) ? $this->sqlColumnsEqual(' AND ', $where_statement) : $where_statement)).';')->fetch(self::FETCH_COLUMN);
  }
}