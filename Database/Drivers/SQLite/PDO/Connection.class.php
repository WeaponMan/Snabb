<?php
/**
 * @author WeaponMan <weaponman@centrum.cz>
 * @author midlan <6midlan@gmail.com>
 * @property-read bool $in_transaction aktivní transakce
 */

namespace Snabb\Database\Drivers\SQLite\PDO;

class Connection extends \Snabb\Database\Connection 
{
  private $pdo;
  
  protected static $__getters = ['errmode', 'in_transaction','executedQueries'];
  
  public function __construct($filename, $errmode = self::ERRMODE_SILENT) 
  {
    try
    {
      $this->pdo = new \PDO('sqlite:'.$filename);
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
    return new \Snabb\Database\Literal($what);
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
      $sql .= 'OR IGNORE ';
    $sql .= 'INTO '.$this->backtick($table_name);
    if(\Snabb\Tools\Arrays::is_assoc($data))
      $sql .= '('.implode(', ', $this->bactickArrayValues(array_keys($data))).')'; 
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
      return new \Snabb\Database\Literal(0);
    if($what === false)
      return new \Snabb\Database\Literal(1);
    return new \Snabb\Database\Literal($this->pdo->quote($what));
  }

  public function update($table_name, array $data, $where = null, $limit = null) 
  {
    $sql = 'UPDATE '.$this->backtick($table_name).' SET '.$this->sqlColumnsEqual(', ', $data);
    if($where !== null)
      $sql .= ' WHERE '.(is_array($where) ? $this->sqlColumnsEqual(' AND ', $where) : $where);
    return $this->exec($sql.';');
  }  
  
  public function close()
  {
    $this->pdo = null;
  }
  
  public function getDriverName() {
      return "SQLite/PDO";
  }
}