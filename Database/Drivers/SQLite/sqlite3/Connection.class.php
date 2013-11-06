<?php
/**
 * @author WeaponMan <weaponman@centrum.cz>
 * @author midlan <6midlan@gmail.com>
 * @property-read bool $in_transaction aktivní transakce
 */

namespace Database\Drivers\SQLite\sqlite3;

class Connection extends \Database\Connection 
{
  private $sqlite3;
  
  protected static $__getters = array('errmode', 'in_transaction','executedQueries');
  
  public function __construct($filename, $flags, $encryption_key) 
  {
    try
    {
      $this->sqlite3 = new \SQLite3($filename, $flags, $encryption_key);
    } 
    catch (\Exception $e)
    {
      throw new\Snabb\Database\Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
    }
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
    $exec = $this->sqlite3->exec($sql);
    $this->executedQueries[$sql]['duration'] += microtime(true);
    if($exec === true)
      return $this->sqlite3->changes();
    $this->executedQueries[$sql]['duration'] += microtime(true);
    $this->executedQueries[$sql]['status'] = '['.$this->sqlite3->lastErrorCode().'] '.$this->sqlite3->lastErrorMsg();
    $this->processError($this->sqlite3->lastErrorMsg(), $this->sqlite3->lastErrorCode());
    return false; 
  }

  public function insert($table_name, array $data, $ignore = false) 
  {
    $sql = 'INSERT ';
    if($ignore)
      $sql .= 'IGNORE ';
    $sql .= 'INTO '.$this->backtick($table_name);
    if(\Snabb\Tools\Arrays::is_assoc($data))
      $sql .= '('.implode(', ', $this->bactickArrayValues(array_keys($data))).')'; 
    return $this->exec($sql.' VALUES('.implode(', ', $this->quoteArrayValues($data)).');');
  }

  public function prepare($sql) {
    return new namespace\PreparedStatement($this, $sql);
  }

  public function query($sql) {
    $queries = $this->multiQuery($sql);
    $exec_queries = array();
    foreach ($queries as $query)
    {
      $executed = $this->_query($query);
      if(!$executed) return false;
      $exec_queries[] = $executed;
    }
    return new namespace\Result($exec_queries);
  }
  
  public function _query($sql) 
  {
    $this->executedQueries[$sql] = array('type' => 'query', 'duration' => - microtime(true), 'status' => 'OK');
    $query = $this->sqlite3->query($sql);
    $this->executedQueries[$sql]['duration'] += microtime(true);
    if($query !== false)
     return $query;
    $this->executedQueries[$sql]['status'] = '['.$this->sqlite3->lastErrorCode().'] '.$this->sqlite3->lastErrorMsg();
    $this->processError($this->sqlite3->lastErrorMsg(), $this->sqlite3->lastErrorCode());
    return false; 
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
      return new \Snabb\Database\Literal(1);
    if($what === false)
      return new \Snabb\Database\Literal(0);
    return new \Snabb\Database\Literal("'".$this->sqlite3->escapeString($what)."'");
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
  
  public function close()
  {
    $this->sqlite3->close();
  }
  
  private function multiQuery($queryBlock, $delimiter = ';') {
    $inString = false;
    $escChar = false;
    $sql = '';
    $stringChar = '';
    $queryLine = array();
    $sqlRows = explode("\n", $queryBlock);
    $delimiterLen = strlen($delimiter);
    do {
      $sqlRow = current($sqlRows) . "\n";
      $sqlRowLen = strlen($sqlRow);
      for ($i = 0; $i < $sqlRowLen; $i++) {
        if (( substr(ltrim($sqlRow), $i, 2) === '--' || substr(ltrim($sqlRow), $i, 1) === '#' ) && !$inString)
          break;

        $znak = substr($sqlRow, $i, 1);
        if ($znak === '\'' || $znak === '"') {
          if ($inString) {
            if (!$escChar && $znak === $stringChar)
              $inString = false;
          }
          else {
            $stringChar = $znak;
            $inString = true;
          }
        }

        if ($znak === '\\' && substr($sqlRow, $i - 1, 2) !== '\\\\')
          $escChar = !$escChar;
        else
          $escChar = false;

        if (substr($sqlRow, $i, $delimiterLen) === $delimiter and !$inString) 
        {
            $sql = trim($sql);
            $delimiterMatch = array();
            if (preg_match('/^DELIMITER[[:space:]]*([^[:space:]] )$/i', $sql, $delimiterMatch)) {
              $delimiter = $delimiterMatch [1];
              $delimiterLen = strlen($delimiter);
            } else
              $queryLine [] = $sql;

            $sql = '';
            continue;
        }
        $sql .= $znak;
      }
    } while (next($sqlRows) !== false);
    return $queryLine;
  }
}

?>
