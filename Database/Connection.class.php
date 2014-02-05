<?php
namespace Snabb\Database;

abstract class Connection {

  const FETCH_ASSOC = 0;
  const FETCH_NUM = 1;
  const FETCH_COLUMN = 2;
  const FETCH_ARRAY_KEY = 3;
  const FETCH_LEAVE_EMPTY_COLS_ASSOC = 4;
  const FETCH_OBJECT = 5;

  const PARAM_PHP_SAME = 0;
  const PARAM_STR = 1;
  const PARAM_INT = 2;
  const PARAM_FLOAT = 3;
  const PARAM_BOOL = 4;

  const ERRMODE_SILENT = 0;
  const ERRMODE_WARNING = 1;
  const ERRMODE_EXCEPTION = 2;

  protected $errmode = self::ERRMODE_SILENT;
  protected $inTransaction = false;
  protected $originalErrmode;
  protected $executedQueries = array();
  public $errorInfo;
  public $errorCode;

  final public function setErrmode($errmode) {
    if($this->inTransaction) {
      $this->originalErrmode = $errmode;
      $errmode |= self::ERRMODE_EXCEPTION;
}
    $this->errmode = $errmode;
  }

  final public function getExecutedQueries(){
    return $this->executedQueries;
  }
  
  final protected function processError($message = '', $code = 0) {
    $this->errorInfo = $message;
    $this->errorCode = $code;
    if(($this->errmode & self::ERRMODE_WARNING) === self::ERRMODE_WARNING)
      trigger_error('['.$code.'] '.$message, E_USER_WARNING);
    if(($this->errmode & self::ERRMODE_EXCEPTION) === self::ERRMODE_EXCEPTION)
      throw new namespace\Exception($message, $code);
  }

  public function beginTransaction() {
    if($this->inTransaction) {
      $this->processError('Transaction already started!');
      return false;
    }
    if($this->exec('START TRANSACTION;') !== false) {
      $this->originalErrmode = $this->errmode;
      $this->errmode |= self::ERRMODE_EXCEPTION;
      $this->inTransaction = true;
      return true;
    }
    return false;
  }

  public function createSavepoint($savepoint_name) {
    if(!$this->inTransaction) {
      $this->processError('Transaction isn\'t opened!');
      return false;
    }
    return $this->exec('SAVEPOINT '.$this->backtick($savepoint_name).';') !== false;
  }

  public function commit() {
    if(!$this->inTransaction) {
      $this->processError('Transaction isn\'t opened!');
      return false;
    }
    if($this->exec('COMMIT;') !== false) {
      $this->errmode = $this->originalErrmode;
      $this->inTransaction = false;
      return true;
    }
    return false;
  }

  public function rollback($savepoint_name = null) {
    if(!$this->inTransaction) {
      $this->processError('Transaction isn\'t opened!');
      return false;
    }
    if($savepoint_name !== null)
      return $this->exec('ROLLBACK TO SAVEPOINT '.$this->backtick($savepoint_name).';') !== false;
    if($this->exec('ROLLBACK;') !== false) {
      $this->errmode = $this->originalErrmode;
      $this->inTransaction = false;
      return true;
    }
    return false;
  }

  abstract public function prepare($sql);
  abstract public function query($sql);
  abstract public function cachedQuery($sql, $seconds);
  abstract public function exec($sql);
  abstract public function backtick($what);
  abstract public function getDriverName();
  
  final public function bactickArrayValues($array) {
    if(is_array($array)) {
      foreach($array as &$inside)
        $inside = $this->bactickArrayValues($inside);
      return $array;
    }
    return $this->backtick($array);
  }

  abstract public function quote($what, $parameter_type = self::PARAM_PHP_SAME);

  final public function quoteArrayValues($array, $parameter_type = self::PARAM_PHP_SAME) {
    if(is_array($array)) {
      foreach($array as &$inside)
        $inside = $this->quoteArrayValues($inside, $parameter_type);
      return $array;
    }
    return $this->quote($array, $parameter_type);
  }

  public function sqlColumnsEqual($glue, array $columns, $null_equal_sign = ' = ') {
    $last_key = key(array_slice( $columns, -1, 1, true ));
    $last_value = array_pop($columns);
    $output = '';
    foreach($columns as $key => $value)
      $output .= $this->backtick($key).($value === null ? $null_equal_sign : ' = ').$this->quote($value).$glue;
    return $output.$this->backtick($last_key).($last_value === null ? $null_equal_sign : ' = ').$this->quote($last_value);
  }

  abstract public function insert($table_name, array $data, $ignore = false);
  abstract public function update($table_name, array $data, $where = null, $limit = null);
  abstract public function countRows($table_name, $where_statement = null);
}