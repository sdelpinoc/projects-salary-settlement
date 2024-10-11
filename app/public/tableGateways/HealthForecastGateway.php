<?php
require_once dirname(__FILE__) . '../../db/Connection.php';

class HealthForecastGateway
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function findAll()
  {
    try {
      $stmt = $this->db->prepare('SELECT name, code FROM health_forecast;');

      $stmt->execute();

      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      exit($e->getMessage());
    }
  }
}
