<?php
/**
 * @author Milan Davidek <6midlan@gmail.com> 
 */

namespace Database\MySQLio;

final class PreparedStatement implements \Database\PreparedStatement {

  private $name;
  private $params = array();
  private $params_order = array();
  private $db;

  public function __construct(namespace\Connection $db, $sql) {
    $this->name = $db->backtick(generate(20, ''));

    $params = &$this->params;
    $params_order = &$this->params_order;

    $param_regexp = new \RegExp('(?<=:).+'); #todo regexp

    $db->exec('PREPARE '.$this->name.' FROM '.$db->quote($param_regexp->replace($sql, function($matches) use ($db, &$params, &$params_order) {
      $params[$params_order[] = '@'.$db->quote($matches[0], \Database\Connection::PARAM_STR)] = $db->quote(null);
      return '?';
    }), \Database\Connection::PARAM_STR).';');
    $this->db = $db;
  }

  public function bindParam($name, $value, $parameter_type = \Database\Connection::PARAM_PHP_SAME) {
    $this->params['@'.$this->db->quote($name, MySQLio::PARAM_STR)] = $this->db->quote($value, $parameter_type);
  }

  public function execute() {
    $sql = '';
    if($this->params) {
      $sql .= 'SET';
      $params_copy = $this->params;
      $last_param_name = array_last_key($params_copy);
      $last_param_value = array_pop($params_copy);
      foreach($params_copy as $param_name => $param_value)
        $sql .= ' '.$param_name.' = '.$param_value.',';
      $sql .= ' '.$last_param_name.' = '.$last_param_value.'; ';
    }
    $sql .= 'EXECUTE '.$this->name;
    if($this->params_order)
      $sql .= ' USING '.implode(', ', $this->params_order);
    $this->db->exec($sql.';');
  }

  public function close() {
    $this->db->exec('DEALLOCATE PREPARE '.$this->name.';');
  }
}