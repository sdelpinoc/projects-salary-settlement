<?php
require_once dirname(__FILE__) . '../../scripts/connection.php';

class HealthForecast
{

  private $connection;

  public function get()
  {
    $this->connection = new Connection();

    $data = array();

    try {
      $stmt = $this->connection->dbh->prepare('SELECT name, code FROM health_forecast;');

      $stmt->execute();

      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      print $e->getMessage();
    }

    return $data;
  }
}
