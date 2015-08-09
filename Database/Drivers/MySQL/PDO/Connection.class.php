<?php
/**
 * @author WeaponMan <weaponman@centrum.cz>
 * @author midlan <6midlan@gmail.com>
 * @property-read bool $in_transaction aktivní transakce
 */

namespace Snabb\Database\Drivers\MySQL\PDO;

class Connection extends \Snabb\Database\Connection 
{
  private $pdo;
  
  protected static $__getters = ['errmode', 'in_transaction','executedQueries'];
  
  public function __construct($host, $user, $password, $database, $port = 3306, $errmode = self::ERRMODE_SILENT, $persistent = true) 
  {
    try
    {
      $this->pdo = new \PDO('mysql:host='.$host.';dbname='.$database.';port='.$port,$user,$password,
              [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',\PDO::ATTR_PERSISTENT => $persistent]);
    } 
    catch (\PDOException $e)
    {
      throw new \Snabb\Database\Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
    }
    $this->errmode = $errmode;
    $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
  }

  public function backtick($what) 
  {
    if($what instanceof \Snabb\Database\Literal)
      return $what;
    /**
     * @todo Escape all
     */
    return new \Snabb\Database\Literal('`'.str_replace('`', '``', $what).'`');
  }

  public function cachedQuery($sql, $seconds) {
    
  }

  public function countRows($table_name, $where_statement = null) {
    return (int)$this->query('SELECT COUNT(*) FROM '.$this->backtick($table_name).($where_statement === null ? null : ' WHERE '.(is_array($where_statement) ? $this->sqlColumnsEqual(' AND ', $where_statement) : $where_statement)).';')->fetch(self::FETCH_COLUMN);
  }

  public function exec($sql) 
  {
    $this->executedQueries[$sql] = ['type' => 'exec', 'duration' => - microtime(true), 'status' => 'OK'];
    try
    {
      $exec = $this->pdo->exec($sql);
      $this->executedQueries[$sql]['duration'] += microtime(true);
      return $exec;
    } 
    catch (\PDOException $e)
    {
      $this->executedQueries[$sql]['duration'] += microtime(true);
      $this->executedQueries[$sql]['status'] = '['.$e->getCode().'] '.$e->getMessage();
      $this->processError($e->getMessage(), $e->getCode());
      return false;
    }
  }

  public function insert($table_name, array $data, $ignore = false) 
  {
    $sql = 'INSERT ';
    if($ignore)
      $sql .= 'IGNORE ';
    $sql .= 'INTO '.$this->backtick($table_name);
    if(\Snabb\Tools\Arrays::is_assoc($data))
      return $this->exec($sql.' SET '.$this->sqlColumnsEqual(', ', $data).';');
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
    $this->executedQueries[$sql] = ['type' => 'query', 'duration' => - microtime(true), 'status' => 'OK'];
    try
    {
      $query = $this->pdo->query($sql);
      $this->executedQueries[$sql]['duration'] += microtime(true);
      return new namespace\Result($query);
    } 
    catch (\PDOException $e)
    {
      $this->executedQueries[$sql]['duration'] += microtime(true);
      $this->executedQueries[$sql]['status'] = '['.$e->getCode().'] '.$e->getMessage();
      $this->processError($e->getMessage(), $e->getCode());
      return false;
    }
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
        throw new \Snabb\Database\Exception(); #unknown param type
        break;
    }
    if($what === true)
      return new \Snabb\Database\Literal('TRUE');
    if($what === false)
      return new \Snabb\Database\Literal('FALSE');
    return new \Snabb\Database\Literal($this->pdo->quote($what));
  }

  public function update($table_name, array $data, $where = null, $limit = null) 
  {
    $sql = 'UPDATE '.$this->backtick($table_name).' SET '.$this->sqlColumnsEqual(', ', $data);
    if($where !== null)
      $sql .= ' WHERE '.(is_array($where) ? $this->sqlColumnsEqual(' AND ', $where) : $where);
    if($limit !== null)
      $sql .= ' LIMIT '.(int)$limit;
    return $this->exec($sql.';');
  }  
  
  public function close() {
    $this->pdo = null;
  }

  public function getDriverName() {
      return "MySQL/PDO";
  }
}