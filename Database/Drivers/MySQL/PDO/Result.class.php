<?php

namespace Snabb\Database\Drivers\MySQL\PDO;

class Result implements \Snabb\Database\Result, \Iterator
{
  private $stmt;
  private $position = 0;
  private $data_iterable = [];
  
  public function __construct($stmt) 
  {
    $this->stmt = $stmt;
  }

  public function fetch($how = \Snabb\Database\Connection::FETCH_ASSOC, $parameter = null)
  {
    if($this->stmt->rowCount() === 0)
      return false;
    switch($how) {
      case \Snabb\Database\Connection::FETCH_ASSOC:
        return $this->stmt->fetch(\PDO::FETCH_ASSOC);
      case \Snabb\Database\Connection::FETCH_NUM:
        return $this->stmt->fetch(\PDO::FETCH_NUM);
      case \Snabb\Database\Connection::FETCH_COLUMN:
         return $this->stmt->fetchColumn((int)$parameter);
      default:
        throw new \Snabb\Database\Exception('Unknown type in  '.__CLASS__.'::'.__METHOD__);
        break;
    }
  }
  
  public function fetchAll($how = \Snabb\Database\Connection::FETCH_ASSOC, $parameter = null) 
  {
    if($this->stmt->rowCount() === 0)
      return [];
    
    switch ($how) {
      case \Snabb\Database\Connection::FETCH_ASSOC:
      case \Snabb\Database\Connection::FETCH_NUM:
          return $this->stmt->fetchAll($how === \Snabb\Database\Connection::FETCH_ASSOC ? \PDO::FETCH_ASSOC : \PDO::FETCH_NUM);
      case \Snabb\Database\Connection::FETCH_COLUMN:
        $data = [];
        while($data[] = $this->fetch(\Snabb\Database\Connection::FETCH_COLUMN, $parameter));
        array_pop($data);
        return $data;
      case \Snabb\Database\Connection::FETCH_ARRAY_KEY:
        $data = [];
        foreach($this->fetchAll(\Snabb\Database\Connection::FETCH_ASSOC) as $row) {
          $data[$value[$parameter]] = $row;
          unset($data[$row[$parameter]][$parameter]);
        }
        return $data;
      case \Snabb\Database\Connection::FETCH_LEAVE_EMPTY_COLS:
        $data = $this->fetchAll(\Snabb\Database\Connection::FETCH_ASSOC);
        if(($leave_columns = current($data))) {
          foreach($leave_columns as &$value)
            $value = false;
          foreach($data as $row)
            foreach($row as $column => $value)
              if(!$leave_columns[$column])
                $leave_columns[$column] = $value === null;
          foreach($data as $rownum => $row)
            foreach($row as $column)
              if($leave_columns[$column])
                unset($data[$rownum][$column]);
        }
        return $data;
      default:
        throw new \Snabb\Database\Exception('Unknown type in '.__CLASS__.'::'.__METHOD__);
        break;
    }
  }
  
  public function nextRowset() 
  {
    $this->position = 0;
    if(!$this->stmt->nextRowset())
      throw new \Snabb\Database\Exception('Failed in '.__CLASS__.'::'.__METHOD__.' with error: No more results in set.');
  }

  public function current() 
  {
    return $this->data_iterable[$this->position];
  }

  public function key() {
    return $this->position;
  }

  public function next() {
    $this->position++;
  }

  public function rewind() {
    $this->position = 0;
  }

  public function valid() {
    if($this->position === 0)
      $this->data_iterable = $this->fetchAll(\Snabb\Database\Connection::FETCH_ASSOC);
    return isset($this->data_iterable[$this->position]);
  }
}
?>
