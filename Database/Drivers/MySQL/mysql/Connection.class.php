<?php

namespace Snabb\Database\Drivers\MySQL\mysql;

class Connection extends \Snabb\Database\Connection {

  private $mysql;
  protected static $__getters = ['errmode', 'in_transaction', 'executedQueries'];

  public function __construct($host, $user, $password, $database, $port = 3306, $errmode = self::ERRMODE_SILENT, $persistent = true) {
    if ($persistent)
      $this->mysql = mysql_pconnect($host . ':' . $port, $user, $password);
    else
      $this->mysql = mysql_connect($host . ':' . $port, $user, $password);

    if (!$this->mysql)
      throw new \Snabb\Database\Exception(mysql_error($this->mysql), mysql_errno($this->mysql));

    if (!mysql_select_db($database, $this->mysql))
      throw new \Snabb\Database\Exception(mysql_error($this->mysql), mysql_errno($this->mysql));

    $this->errmode = $errmode;
  }

  public function backtick($what) {
    if ($what instanceof \Snabb\Database\Literal)
      return $what;
    /**
     * @todo Escape all
     */
    return new \Snabb\Database\Literal('`' . str_replace('`', '``', $what) . '`');
  }

  public function cachedQuery($sql, $seconds) {
    
  }

  public function countRows($table_name, $where_statement = null) {
    return (int) $this->query('SELECT COUNT(*) FROM ' . $this->backtick($table_name) . ($where_statement === null ? null : ' WHERE ' . (is_array($where_statement) ? $this->sqlColumnsEqual(' AND ', $where_statement) : $where_statement)) . ';')->fetch(self::FETCH_COLUMN);
  }

  public function exec($sql) {
    $this->executedQueries[$sql] = ['type' => 'exec', 'duration' => - microtime(true), 'status' => 'OK'];
    $query = mysql_query($sql, $this->mysql);
    $this->executedQueries[$sql]['duration'] += microtime(true);
    if (!$query) {
      $this->executedQueries[$sql]['status'] = '[' . mysql_errno($this->mysql) . '] ' . mysql_error($this->mysql);
      $this->processError(mysql_error($this->mysql), mysql_errno($this->mysql));
      return false;
    }
    return mysql_affected_rows($this->mysql);
  }

  public function insert($table_name, array $data, $ignore = false) {
    $sql = 'INSERT ';
    if ($ignore)
      $sql .= 'IGNORE ';
    $sql .= 'INTO ' . $this->backtick($table_name);
    if (\Snabb\Tools\Arrays::is_assoc($data))
      return $this->exec($sql . ' SET ' . $this->sqlColumnsEqual(', ', $data) . ';');
    if (is_array($data[0])) {
      if (\Snabb\Tools\Arrays::is_assoc($data[0]))
        $sql .= '(' . implode(', ', $this->bactickArrayValues(array_keys($data[0]))) . ')';
      $sql .= ' VALUES ';
      $last_row = array_pop($data);
      foreach ($data as $row)
        $sql .= '(' . implode(', ', $this->quoteArrayValues($row)) . '), ';
      return $this->exec($sql . '(' . implode(', ', $this->quoteArrayValues($last_row)) . ');');
    }
    return $this->exec($sql . ' VALUES(' . implode(', ', $this->quoteArrayValues($data)) . ');');
  }

  public function prepare($sql) {
    return new namespace\PreparedStatement($this, $sql);
  }

  public function query($sql) {
    $queries = $this->multiQuery($sql);
    $exec_queries = [];
    foreach ($queries as $query)
    {
      $executed = $this->_query($query);
      if(!$executed) return false;
      $exec_queries[] = $executed;
    }
    return new namespace\Result($exec_queries);
  }

  private function _query($sql) {
    $this->executedQueries[$sql] = ['type' => 'query', 'duration' => - microtime(true), 'status' => 'OK'];
    $query = mysql_query($sql, $this->mysql);
    $this->executedQueries[$sql]['duration'] += microtime(true);
    if (!$query) {
      $this->executedQueries[$sql]['status'] = '[' . mysql_errno($this->mysql) . '] ' . mysql_error($this->mysql);
      $this->processError(mysql_error($this->mysql), mysql_errno($this->mysql));
      return false;
    }
    return $query;
  }

  public function quote($what, $parameter_type = self::PARAM_PHP_SAME) {
    if ($what instanceof \Snabb\Database\Literal)
      return $what;
    if ($what === null)
      return new \Snabb\Database\Literal('NULL');
    switch ($parameter_type) {
      case self::PARAM_BOOL:
        $what = (bool) $what;
        break;
      case self::PARAM_FLOAT:
        $what = (float) $what;
        break;
      case self::PARAM_INT:
        $what = (int) $what;
        break;
      case self::PARAM_STR:
        $what = (string) $what;
      case self::PARAM_PHP_SAME:
        break;
      default:
        throw new\Snabb\Database\Exception(); #unknown param type
        break;
    }
    if ($what === true)
      return new \Snabb\Database\Literal('TRUE');
    if ($what === false)
      return new \Snabb\Database\Literal('FALSE');
    return new \Snabb\Database\Literal("'" . mysql_real_escape_string($what, $this->mysql) . "'");
  }

  public function update($table_name, array $data, $where = null, $limit = null) {
    $sql = 'UPDATE ' . $this->backtick($table_name) . ' SET ' . $this->sqlColumnsEqual(', ', $data);
    if ($where !== null)
      $sql .= ' WHERE ' . (is_array($where) ? $this->sqlColumnsEqual(' AND ', $where) : $where);
    if ($limit !== null)
      $sql .= ' LIMIT ' . (int) $limit;
    return $this->exec($sql . ';');
  }

  public function close() {
    mysql_close($this->mysql);
  }

  private function multiQuery($queryBlock, $delimiter = ';') {
    $inString = false;
    $escChar = false;
    $sql = '';
    $stringChar = '';
    $queryLine = [];
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
            $delimiterMatch = [];
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
    
    if($queryLine)
        return $queryLine;
    else
        return [$queryBlock];
  }
  
  public function getDriverName() {
      return "MySQL/mysql";
  }
}