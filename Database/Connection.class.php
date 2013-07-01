<?php
namespace Database;

interface Connection {

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

  public function __construct($host, $user, $password, $database, $error_reporting = self::ERRMODE_SILENT);
  public function beginTransaction();
  public function commit();
  public function createSavepoint($savepoint_name);
  public function rollback($savepoint_name = null);
  public function prepare($sql);
  public function query($sql);
  public function cachedQuery($sql, $seconds);
  public function exec($sql);
  public function backtick($what);
  public function bactickArrayValues($array);
  public function quote($what, $parameter_type = self::PARAM_PHP_SAME);
  public function quoteArrayValues($array, $parameter_type = self::PARAM_PHP_SAME);
  public function sqlColumnsEqual($glue, array $columns, $null_equal_sign = ' = ');
  public function insert($table_name, array $data, $ignore = false);
  public function update($table_name, array $data, $where = null, $limit = null);
  public function countRows($table_name, $where_statement = null);
}