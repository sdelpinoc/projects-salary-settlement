<?php
if (isset($_GET['access']) && $_GET['access'] === 'seed') {
  require_once dirname(__FILE__) . '../../classes/uf.php';
  require_once dirname(__FILE__) . '/Connection.php';

  $connection = new Connection();
  $db = $connection->getConnection();

  // Create afp and health_forecast tables
  try {
    $stmt = $db->prepare('
      CREATE OR REPLACE TABLE afp (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        value VARCHAR(100) NOT NULL,
        rate FLOAT NOT NULL
      );
    ');

    $stmt->execute();

    print 'Table afp created...<br />';

    $stmt = $db->prepare('
      CREATE OR REPLACE TABLE health_forecast (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        code INT NOT NULL
      );
    ');

    $stmt->execute();

    print 'Table health_forecast created...<br />';

    // Create table uf
    $stmt = $db->prepare('
      CREATE OR REPLACE TABLE uf (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        year INT NOT NULL,
        month VARCHAR(2) NOT NULL,
        uf DECIMAL(10,2) NOT NULL
      );
    ');

    $stmt->execute();

    print 'Table uf created...<br />';
  } catch (PDOException $e) {
    print '<pre>';
    print_r($e);
    print '</pre>';
    print $e->getMessage();
  }

  // Insert afp
  $afps = [
    [
      'name' => 'AFP Capital',
      'value' => 'Capital',
      'rate' => 11.44
    ],
    [
      'name' => 'AFP Cuprum',
      'value' => 'Cuprum',
      'rate' => 11.44
    ],
    [
      'name' => 'AFP Habitat',
      'value' => 'Habitat',
      'rate' => 11.27
    ],
    [
      'name' => 'AFP Modelo',
      'value' => 'Modelo',
      'rate' => 10.58
    ],
    [
      'name' => 'AFP PlanVital',
      'value' => 'PlanVital',
      'rate' => 11.16
    ],
    [
      'name' => 'AFP ProVida',
      'value' => 'ProVida',
      'rate' => 11.45
    ],
    [
      'name' => 'AFP Uno',
      'value' => 'Uno',
      'rate' => 10.49
    ]
  ];

  try {
    foreach ($afps as $afp) {
      $stmt = $db->prepare('INSERT INTO afp (name, value, rate) VALUES (:name, :value, :rate)');

      $stmt->bindParam(':name', $afp['name']);
      $stmt->bindParam(':value', $afp['value']);
      $stmt->bindParam(':rate', $afp['rate']);

      $stmt->execute();
    }

    print 'Afps inserted...<br />';
  } catch (PDOException $e) {
    print '<pre>';
    print_r($e);
    print '</pre>';
    print $e->getMessage();
  }

  // Insert healthForecast
  $healthForecasts = [
    [
      'code' => '101',
      'name' => 'Banmédica'
    ],
    [
      'code' => '102',
      'name' => 'Isapre Chuquicamata'
    ],
    [
      'code' => '103',
      'name' => 'Colmena'
    ],
    [
      'code' => '104',
      'name' => 'Consalud'
    ],
    [
      'code' => '105',
      'name' => 'Cruz Blanca'
    ],
    [
      'code' => '106',
      'name' => 'Cruz del Norte'
    ],
    [
      'code' => '107',
      'name' => 'Nueva Masvida'
    ],
    [
      'code' => '108',
      'name' => 'Isapre Fundación Banco Estado'
    ],
    [
      'code' => '109',
      'name' => 'FusatLtda.'
    ],
    [
      'code' => '110',
      'name' => 'Mas vida'
    ],
    [
      'code' => '111',
      'name' => 'Río Blanco'
    ],
    [
      'code' => '112',
      'name' => 'San Lorenzo'
    ],
    [
      'code' => '113',
      'name' => 'Vida Tres'
    ],
    [
      'code' => '122',
      'name' => 'FONASA'
    ],
    [
      'code' => '830',
      'name' => 'Esencial'
    ]
  ];

  try {
    foreach ($healthForecasts as $hf) {
      $stmt = $db->prepare('INSERT INTO health_forecast (name, code) VALUES (:name, :code)');

      $stmt->bindParam(':name', $hf['name']);
      $stmt->bindParam(':code', $hf['code']);

      $stmt->execute();
    }

    print 'Health Forecasts inserted...<br />';
  } catch (PDOException $e) {
    print '<pre>';
    print_r($e);
    print '</pre>';
    print $e->getMessage();
  }

  // Insert UF
  $uf = new Uf();

  $getUf = $uf->getUFFromApi();

  if (!$getUf['ok']) {
    print 'The value of the uf could not be obtained...';
    exit();
  }

  try {
    $stmt = $db->prepare('INSERT INTO uf (year, month, uf) VALUES (:year, :month, :uf);');

    $stmt->bindParam(':year', $getUf['year']);
    $stmt->bindParam(':month', $getUf['month']);
    $stmt->bindParam(':uf', $getUf['ufLastDay']);

    $stmt->execute();

    print 'UF inserted...<br />';
  } catch (PDOException $e) {
    print '<pre>';
    print_r($e);
    print '</pre>';
    print $e->getMessage();
  }
}
