<?php
require_once dirname(__FILE__) . '../../db/Connection.php';

class AfpGateway
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function findAll()
  {
    try {
      $stmt = $this->db->prepare('SELECT name, LOWER(value) AS value, rate FROM afp;');

      $stmt->execute();

      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      exit($e->getMessage());
    }
  }
}
