<?php
require_once dirname(__FILE__) . '../../db/Connection.php';

class Afp
{
  private $connection;

  public function get()
  {
    $this->connection = new Connection();

    $data = array();

    try {
      $stmt = $this->connection->prepare('SELECT name, LOWER(value) AS value, rate FROM afp;');

      $stmt->execute();

      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      print $e->getMessage();
    }

    return $data;
  }
}
