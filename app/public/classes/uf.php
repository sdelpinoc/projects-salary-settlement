<?php
require_once dirname(__FILE__) . '../../scripts/connection.php';

class Uf
{
  private $months;
  private $url;
  private $connection;

  public function __construct()
  {
    $this->months = array(
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
    );

    $this->url = 'https://www.sii.cl/valores_y_fechas/uf/uf2024.htm';
  }

  /**
   * Get the uf value of the last day of the actual month
   * 
   * @return array $result
   */
  public function getUfFromUrl()
  {
    $result = array();

    $content = file_get_contents($this->url);
    $doc = new DOMDocument();
    $searchPage = mb_convert_encoding($content, "UTF-8");

    $doc->loadHTML($searchPage);
    // echo $doc->saveHTML();

    $actualMonth = date("m"); // 07 -> July
    $actualYear = date("Y"); // 2024

    $titles = iterator_to_array($doc->getElementsByTagName('h2'));

    if ($titles[0]->nodeValue !== 'UF ' . $actualYear) {
      $result['ok'] = false;
      return $result;
    }

    $actualTableMonth = $doc->getElementById("mes_" . $this->months[$actualMonth]);

    $actualTableMonthClean = preg_replace('/[\s]+/', ' ', trim($actualTableMonth->nodeValue));
    $onlyUfValues = preg_replace('/\s\d{1,2}\s/', ' ', $actualTableMonthClean);

    $ufByDay = explode(' ', $onlyUfValues);

    $replace = array(',' => '.', '.' => '');
    $ufLastDay = strtr(end($ufByDay), $replace);

    $result = array(
      'ok' => true,
      'ufLastDay' => $ufLastDay,
      'month' => $actualMonth,
      'year' => $actualYear
    );

    return $result;
  }

  public function getUfFromDB($year, $month)
  {
    $this->connection = new Connection();

    $data = array();

    try {
      $stmt = $this->connection->dbh->prepare('
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

      $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      print $e->getMessage();
    }

    return $data;
  }

  public function getUfFromUrlAndSaveInBD()
  {
    $result = array();

    $this->connection = new Connection();

    $getUf = $this->getUfFromUrl();

    if (!$getUf['ok']) {
      $result['ok'] = false;
      return $result;
    }
  
    try {
      $stmt = $this->connection->dbh->prepare('INSERT INTO uf (year, month, uf) VALUES (:year, :month, :uf);');
  
      $stmt->bindParam(':year', $getUf['year']);
      $stmt->bindParam(':month', $getUf['month']);
      $stmt->bindParam(':uf', $getUf['ufLastDay']);
  
      $stmt->execute();

      $result['ok'] = true;
      $result['uf'] = $getUf['ufLastDay'];
    } catch (PDOException $e) {
      print $e->getMessage();
    }

    return $result;
  }
}
