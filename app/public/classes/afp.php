<?php
require_once dirname(__FILE__) . '../../db/Connection.php';

class Afp
{
  private $db = null;

  public function getAll()
  {
    $this->db = new Connection();
    $db = $this->db->getConnection();

    $data = [];

    try {
      $stmt = $db->prepare('SELECT name, LOWER(value) AS value, rate FROM afp;');

      $stmt->execute();

      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      print $e->getMessage();
    }

    return $data;
  }
}
