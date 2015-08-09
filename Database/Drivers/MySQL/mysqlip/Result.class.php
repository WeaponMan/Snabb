<?php
/**
 * @author Ondřej Kotora <weaponman@centrum.cz> 
 * @author Milan Davidek <6midlan@gmail.com> 
 */
namespace Snabb\Database\Drivers\MySQL\mysqlip;

final class Result implements \Snabb\Database\Result, \Iterator {
  private $mysqli;
  private $mysqli_result;
  private $position = 0;
  private $data_iterable = [];

  public function __construct(\mysqli $db) {
    $this->position = 0;
    $this->mysqli = $db;
    $this->mysqli_result = mysqli_store_result($this->mysqli);
    if($this->mysqli_result === false)
      throw new \Snabb\Database\Exception('Failed init result in '.__CLASS__.'::'.__METHOD__.' with errno: '.  mysqli_errno($this->mysqli).', error: '.mysqli_error($this->mysqli));
  }
  
  public function valid() {
    if($this->position === 0)
      $this->data_iterable = $this->fetchAll(\Snabb\Database\Connection::FETCH_ASSOC);
    return isset($this->data_iterable[$this->position]);
  }

  public function current() {
    return $this->data_iterable[$this->position];
  }

  public function key() {
    return $this->position;
  }

  public function next() {
    $this->position++;
  }

  public function fetch($how = \Snabb\Database\Connection::FETCH_ASSOC, $parameter = null) {
    if(mysqli_num_rows($this->mysqli_result) === 0)
      return false;
    switch($how) {
      case \Snabb\Database\Connection::FETCH_ASSOC:
        return mysqli_fetch_assoc($this->mysqli_result);
      case \Snabb\Database\Connection::FETCH_NUM:
        return mysqli_fetch_row($this->mysqli_result);
      case \Snabb\Database\Connection::FETCH_COLUMN:
        $data = mysqli_fetch_row($this->mysqli_result);
        return $data[(int)$parameter];
      default:
        throw new \Snabb\Database\Exception('Unknown type in  '.__CLASS__.'::'.__METHOD__);
        break;
    }
  }

  public function fetchAll($how = \Snabb\Database\Connection::FETCH_ASSOC, $parameter = null) {
    if(mysqli_num_rows($this->mysqli_result) === 0)
      return [];
    
    switch ($how) {
      case \Snabb\Database\Connection::FETCH_ASSOC:
      case \Snabb\Database\Connection::FETCH_NUM:
        if(function_exists('mysqli_fetch_all'))
          return mysqli_fetch_all($this->mysqli_result,$how === \Snabb\Database\Connection::FETCH_ASSOC ? MYSQLI_ASSOC : MYSQLI_NUM);
        $data = [];
        if($how === \Snabb\Database\Connection::FETCH_ASSOC)
          while($data[] = mysqli_fetch_assoc($this->mysqli_result));
        else
          while($data[] = mysqli_fetch_row($this->mysqli_result));
        array_pop($data);
        return $data;
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
  public function nextRowset() {
    $this->position = 0;
    if(mysqli_next_result($this->mysqli)) {
      $this->mysqli_result = mysqli_store_result($this->mysqli);
      if($this->mysqli_result === false)
        throw new \Snabb\Database\Exception('Failed init result in '.__CLASS__.'::'.__METHOD__.' with errno: '.  mysqli_errno($this->mysqli).', error: '.mysqli_error($this->mysqli));
    }
    else
      throw new \Snabb\Database\Exception('Failed in '.__CLASS__.'::'.__METHOD__.' with error: No more results in set.');
  }

  public function rewind() {
    $this->position = 0;
  }
}