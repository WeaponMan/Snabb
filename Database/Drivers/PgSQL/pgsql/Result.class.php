<?php

namespace Snabb\Database\Drivers\PgSQL\pgsql;

class Result implements \Snabb\Database\Result, \Iterator
{
  private $pg;
  private $position = 0;
  private $data_iterable = array();
  private $current_result;
  
  public function __construct($pg) 
  {
    $this->pg = $pg; 
    $this->current_result = pg_get_result($this->pg);
    if($this->current_result === false)
      throw new \Snabb\Database\Exception('Failed init result in '.__CLASS__.'::'.__METHOD__.' with error: '.  pg_last_error($this->pg));
  }

  public function fetch($how = \Snabb\Database\Connection::FETCH_ASSOC, $parameter = null)
  {
    if(pg_num_rows($this->current_result) === 0)
      return false;
    switch($how) {
      case \Snabb\Database\Connection::FETCH_ASSOC:
        return pg_fetch_assoc($this->current_result);
      case \Snabb\Database\Connection::FETCH_NUM:
        return pg_fetch_array($this->current_result,PGSQL_NUM);
      case \Snabb\Database\Connection::FETCH_COLUMN:
         $data = pg_fetch_array($this->current_result,PGSQL_NUM);
        return $data[(int)$parameter];
      default:
        throw new \Snabb\Database\Exception('Unknown type in  '.__CLASS__.'::'.__METHOD__);
        break;
    }
  }
  
  public function fetchAll($how = \Snabb\Database\Connection::FETCH_ASSOC, $parameter = null) 
  {
    if(pg_num_rows($this->current_result) === 0)
      return array();
    
    switch ($how) {
      case \Snabb\Database\Connection::FETCH_ASSOC:
        return pg_fetch_all($this->current_result);
      case \Snabb\Database\Connection::FETCH_NUM:
          while ($row = $this->fetch($how)) $rows[] = $row;
          return $rows;
      case \Snabb\Database\Connection::FETCH_COLUMN:
        $data = array();
        while($data[] = $this->fetch(\Snabb\Database\Connection::FETCH_COLUMN, $parameter));
        array_pop($data);
        return $data;
      case \Snabb\Database\Connection::FETCH_ARRAY_KEY:
        $data = array();
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
    $this->current_result = pg_get_result($this->pg);
    if($this->current_result === false)
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