<?php

namespace Snabb\Database\Drivers\SQLite\sqlite3;

class Result implements \Snabb\Database\Result, \Iterator
{
  private $stack_position = 0;
  private $result_stack;
  private $position = 0;
  private $data_iterable = [];
  
  public function __construct($results) 
  {
    $this->result_stack = $results;
  }

  public function fetch($how = \Snabb\Database\Connection::FETCH_ASSOC, $parameter = null)
  {
    switch($how) {
      case \Snabb\Database\Connection::FETCH_ASSOC:
        return $this->result_stack[$this->stack_position]->fetchArray(SQLITE3_ASSOC);
      case \Snabb\Database\Connection::FETCH_NUM:
        return $this->result_stack[$this->stack_position]->fetchArray(SQLITE3_NUM);
      case \Snabb\Database\Connection::FETCH_COLUMN:
         $data = $this->result_stack[$this->stack_position]->fetchArray(SQLITE3_NUM);
        return $data[(int)$parameter];
      default:
        throw new\Snabb\Database\Exception('Unknown type in  '.__CLASS__.'::'.__METHOD__);
        break;
    }
  }
  
  public function fetchAll($how = \Snabb\Database\Connection::FETCH_ASSOC, $parameter = null) 
  { 
    switch ($how) {
      case \Snabb\Database\Connection::FETCH_ASSOC:
      case \Snabb\Database\Connection::FETCH_NUM:
          $rows = [];
          while ($row = $this->fetch($how)) $rows[] = $row;
          return $rows;
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
    $this->stack_position++;
    if($this->stack_position > count($this->result_stack))
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