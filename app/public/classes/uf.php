<?php
require_once dirname(__FILE__) . '../../db/Connection.php';

class Uf
{
  private $months;
  private $apiUrl;
  private $db = null;

  public function __construct()
  {
    $this->months = [
      '01' => 'enero',
      '02' => 'febrero',
      '03' => 'marzo',
      '04' => 'abril',
      '05' => 'mayo',
      '06' => 'junio',
      '07' => 'julio',
      '08' => 'agosto',
      '09' => 'septiembre',
      '10' => 'octubre',
      '11' => 'noviembre',
      '12' => 'diciembre',
    ];

    $this->apiUrl = 'https://mindicador.cl/api/uf';
  }

  /**
   * Fetches the UF (Unidad de Fomento) value for the last day of the current month
   * from the mindicador.cl API. If the value for the last day is not available,
   * it fetches the UF value for the current day. Returns an associative array
   * containing the status, the UF value, the current month, and year.
   *
   * @return array {
   *     @type bool   $ok        Indicates if the operation was successful.
   *     @type float  $ufLastDay UF value for the last day of the current month or current day.
   *     @type string $month     Current month in "mm" format.
   *     @type string $year      Current year in "YYYY" format.
   * }
   */
  public function getUFFromApi()
  {
    $result = [
      'ok' => false
    ];

    // https://mindicador.cl/api/{tipo_indicador}/{dd-mm-yyyy}
    $date = new DateTime('now');
    $date->modify('last day of this month');
    $lastDayOfMonth = $date->format('d-m-Y'); // To use in the API

    $url = "{$this->apiUrl}/{$lastDayOfMonth}";
    $content = file_get_contents($url);
    $result = json_decode($content, true);

    if (empty($result['serie'])) {
      $date = new DateTime('now');
      $currentDay = $date->format('d-m-Y');
      $url = "{$this->apiUrl}/{$currentDay}";
      $content = file_get_contents($url);
      $result = json_decode($content, true);
    }

    $result = [
      'ok' => true,
      'ufLastDay' => $result['serie'][0]['valor'],
      'month' => $date->format('m'),
      'year' => $date->format('Y')
    ];

    return $result;
  }

  public function getUfFromDB($year, $month)
  {
    try {
      $this->db = new Connection();
      $db = $this->db->getConnection();

      $stmt = $db->prepare('
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

  public function getUfFromUrlAndSaveInBD()
  {
    $result = [];

    $getUf = $this->getUFFromApi();

    if (!$getUf['ok']) {
      $result['ok'] = false;
      return $result;
    }

    try {
      $this->db = new Connection();
      $db = $this->db->getConnection();

      $stmt = $db->prepare('INSERT INTO uf (year, month, uf) VALUES (:year, :month, :uf);');

      $stmt->bindParam(':year', $getUf['year']);
      $stmt->bindParam(':month', $getUf['month']);
      $stmt->bindParam(':uf', $getUf['ufLastDay']);

      $stmt->execute();

      $result['ok'] = true;
      $result['uf'] = $getUf['ufLastDay'];
    } catch (PDOException $e) {
      exit($e->getMessage());
    }

    return $result;
  }
}
