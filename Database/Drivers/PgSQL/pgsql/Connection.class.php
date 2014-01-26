<?php
/**
 * @author WeaponMan <weaponman@centrum.cz>
 * @author midlan <6midlan@gmail.com>
 * @property-read bool $in_transaction aktivní transakce
 */

namespace \Snabb\Database\Drivers\PgSQL\pgsql;

class Connection extends \Snabb\Database\Connection 
{
  private $pg;
  
  protected static $__getters = array('errmode', 'in_transaction','executedQueries');
  
  public function __construct($host, $user, $password, $database, $port = 5432, $errmode = self::ERRMODE_SILENT, $persistent = true) 
  {   
    $this->pg = pg_connect('host='.$host.' port='.$port.' user='.$user.' password='.$password.' dbname='.$database." options='--client_encoding=UTF8'");
    if($this->pg === false)
      throw new \Snabb\Database\Exception(pg_last_error($this->pg));
    $this->errmode = $errmode;
  }

  public function backtick($what) 
  {
    if($what instanceof \Snabb\Database\Literal)
      return $what;
    return new \Snabb\Database\Literal($what);
  }

  public function cachedQuery($sql, $seconds) {
    
  }

  public function countRows($table_name, $where_statement = null) {
    return (int)$this->query('SELECT COUNT(*) FROM '.$this->backtick($table_name).($where_statement === null ? null : ' WHERE '.(is_array($where_statement) ? $this->sqlColumnsEqual(' AND ', $where_statement) : $where_statement)).';')->fetch(self::FETCH_COLUMN);
  }

  public function exec($sql) 
  {
    $this->executedQueries[$sql] = array('type' => 'exec', 'duration' => - microtime(true), 'status' => 'OK');
    $exec = pg_query($this->pg, $sql);
    $this->executedQueries[$sql]['duration'] += microtime(true);
    if($exec === false)
    {  
      $this->executedQueries[$sql]['status'] = pg_last_error($this->pg);
      $this->processError(pg_last_error($this->pg));
      return false;
    }
    return pg_affected_rows($exec);
  }

  public function insert($table_name, array $data, $ignore = false) 
  {
    $sql = 'INSERT INTO '.$this->backtick($table_name);
    if(\Snabb\Tools\Arrays::is_assoc($data))
      return $this->exec($sql.'('.implode(', ', $this->bactickArrayValues(array_keys($data[0]))).') VALUES('.implode(', ', $this->quoteArrayValues($data)).');');
    if(is_array($data[0])) {
      if(\Snabb\Tools\Arrays::is_assoc($data[0]))
        $sql .= '('.implode(', ', $this->bactickArrayValues(array_keys($data[0]))).')';
      $sql .= ' VALUES ';
      $last_row = array_pop($data);
      foreach($data as $row)
        $sql .= '('.implode(', ', $this->quoteArrayValues($row)).'), ';
      return $this->exec($sql.'('.implode(', ', $this->quoteArrayValues($last_row)).');');
    }
    return $this->exec($sql.' VALUES('.implode(', ', $this->quoteArrayValues($data)).');');
  }

  public function prepare($sql) {
    return new namespace\PreparedStatement($this, $sql);
  }

  public function query($sql) 
  {
    $this->executedQueries[$sql] = array('type' => 'query', 'duration' => - microtime(true), 'status' => 'OK');
    $query = pg_send_query($this->pg, $sql);
    $this->executedQueries[$sql]['duration'] += microtime(true);
    if($query === false)
    {
      $this->executedQueries[$sql]['status'] = pg_last_error($this->pg);
      $this->processError(pg_last_error($this->pg));
      return false;
    }
    return new namespace\Result($this->pg);
  }

  public function quote($what, $parameter_type = self::PARAM_PHP_SAME) 
  {
    if($what instanceof \Snabb\Database\Literal)
      return $what;
    if($what === null)
      return new \Snabb\Database\Literal('NULL');
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
        throw new\Snabb\Database\Exception(); #unknown param type
        break;
    }
    if($what === true)
      return new \Snabb\Database\Literal('t');
    if($what === false)
      return new \Snabb\Database\Literal('f');
    return new \Snabb\Database\Literal("'".pg_escape_string($this->pg,$what)."'");
  }

  public function update($table_name, array $data, $where = null, $limit = null) 
  {
    $sql = 'UPDATE '.$this->backtick($table_name).' SET '.$this->sqlColumnsEqual(', ', $data);
    if($where !== null)
      $sql .= ' WHERE '.(is_array($where) ? $this->sqlColumnsEqual(' AND ', $where) : $where);
    return $this->exec($sql.';');
  }  
  
  public function close(){
    pg_close($this->pg);
  }
  
  public function getDriverName() {
      return "PgSQL/pgsql";
  } 
}