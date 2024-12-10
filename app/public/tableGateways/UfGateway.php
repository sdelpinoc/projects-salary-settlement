<?php
require_once dirname(__FILE__) . '../../db/Connection.php';

class UfGateway
{
  private $db;

  public function __construct($db)
  {
    $this->db = $db;
  }

  public function findByYearAndMonth($year, $month)
  {
    try {
      $stmt = $this->db->prepare('
        SELECT
          year,
          month,
          uf
        FROM
          uf
        WHERE
          year = :year
        AND
          month = :month
      ');

      $stmt->bindValue(':year', $year, PDO::PARAM_STR);
      $stmt->bindValue(':month', $month, PDO::PARAM_STR);

      $stmt->execute();

      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      exit($e->getMessage());
    }
  }

  public function insert($getUf)
  {
    if (!$getUf['ok']) {
      $result['ok'] = false;
      return $result;
    }
  
    try {
      $stmt = $this->db->prepare('INSERT INTO uf (year, month, uf) VALUES (:year, :month, :uf);');
  
      $stmt->bindParam(':year', $getUf['year']);
      $stmt->bindParam(':month', $getUf['month']);
      $stmt->bindParam(':uf', $getUf['ufLastDay']);
  
      $stmt->execute();

      return [
        'ok' => true,
        'uf' => $getUf['ufLastDay']
      ];
    } catch (PDOException $e) {
      exit($e->getMessage());
    }
  }
}
