<?php
/**
 * @author Milan Davidek <6midlan@gmail.com> 
 */

namespace Snabb\Database\Drivers\MySQL\mysqlio;

final class PreparedStatement implements \Snabb\Database\PreparedStatement {

  private $name;
  private $params = array();
  private $params_order = array();
  private $db;

  public function __construct(namespace\Connection $db, $sql) {
    $this->name = $db->backtick(generate(20, ''));

    $params = &$this->params;
    $params_order = &$this->params_order;

    $param_regexp = new \Snabb\RegExp('\\:(\\w+)'); #todo regexp

    $db->exec('PREPARE '.$this->name.' FROM '.$db->quote($param_regexp->replace($sql, function($matches) use ($db, &$params, &$params_order) {
      $params[$matches[1]] = $db->quote(null);
      $params_order[] = '@'.$db->quote($matches[1], namespace\Connection::PARAM_STR);
      return '?';
    }), namespace\Connection::PARAM_STR).';');
    $this->db = $db;
  }

  public function bindParam($name, $value, $parameter_type = namespace\Connection::PARAM_PHP_SAME) {
    $this->params[$name] = '@'.$this->db->quote($name, namespace\Connection::PARAM_STR).' = '.$this->db->quote($value, $parameter_type);
  }

  public function execute() {
//    $sql = '';
//    if($this->params)
//      $sql .= 'SET '.implode(', ', $this->params).'; ';
//    $sql .= 'EXECUTE '.$this->name;
//    if($this->params_order)
//      $sql .= ' USING '.implode(', ', $this->params_order);
//    $this->db->exec($sql.';');
    $sql = '';
    if($this->params)
      $this->db->exec('SET '.implode(', ', $this->params).'; ');
    $sql .= 'EXECUTE '.$this->name;
    if($this->params_order)
      $sql .= ' USING '.implode(', ', $this->params_order);
    $this->db->exec($sql.';');
  }

  public function close() {
    $this->db->exec('DEALLOCATE PREPARE '.$this->name.';');
  }
}