<?php
require_once dirname(__FILE__) . '/Afp.php';
// require_once dirname(__FILE__) . '../tableGateways/AfpGateway.php';
require_once dirname(__FILE__) . '/HealthForecast.php';
require_once dirname(__FILE__) . '/Uf.php';

class SalarySettlement
{
  private $RANGES = [
    [
      'maxAmount' => 868630.5,
      'factor' => 0,
      'exempt' => 0
    ],
    [
      'maxAmount' => 1930290,
      'factor' => 0.04,
      'exempt' => 34745.22
    ],
    [
      'maxAmount' => 3217150,
      'factor' => 0.08,
      'exempt' => 111956.82
    ],
    [
      'maxAmount' => 4504010,
      'factor' => 0.135,
      'exempt' => 288900.07
    ],
    [
      'maxAmount' => 5790870,
      'factor' => 0.23,
      'exempt' => 716781.02
    ],
    [
      'maxAmount' => 7721160,
      'factor' => 0.304,
      'exempt' => 1145305.4
    ],
    [
      'maxAmount' => 19946330,
      'factor' => 0.35,
      'exempt' => 1500478.76
    ],
    [
      'maxAmount' => 999999999, // $999.999.999
      'factor' => 0.4,
      'exempt' => 2497795.26
    ]
  ];

  private $MIN_BASE_SALARY_FOR_COLLATION = 1200000;
  private $AFP_FONASA_CODE = '122';
  private $VALID_HEALTH_FORECAST_AMOUNT_TYPES = ['pesos', 'uf'];
  private $HEALTH_FORECAST_AMOUNT_TYPE_UF = 'uf';
  private $TRANSPORT_AMOUNT = 36000;
  private $COLLATION_AMOUNT = 52000;

  private $UF = 37500; // Default UF value

  private $MIN_BASE_SALARY = 100000; // $100.000
  private $MAX_BASE_SALARY = 99999999; // $99.999.999

  private $MIN_GRATIFICATION = 10000; // $10.000
  private $MAX_GRATIFICATION = 99999999; // $99.999.999

  private $MIN_AMOUNT_HC_PESOS = 1000; // $1.000
  private $MAX_AMOUNT_HC_PESOS = 9999999; // $9.999.999

  private $MIN_AMOUNT_HC_UF = 1; // 1 UF
  private $MAX_AMOUNT_HC_UF = 99; // 99 UF

  public function calculate($validatedData = [])
  {
    $response = [];

    $uf = new Uf();

    $ufLastDay = $uf->getUfFromDB(year: date('Y'), month: date('m'));

    $ufValue = $this->UF;

    if (isset($ufLastDay[0]) && isset($ufLastDay[0]['uf'])) {
      $ufValue = $ufLastDay[0]['uf'];
    } else {
      // Si no encontramos la UF del mes, intentamos obtenerla nuevamente y guardarla en la base de datos, caso contrario, ocupamos la UF establecida en esta clase
      $ufLastDay = $uf->getUfFromUrlAndSaveInBD();

      if ($ufLastDay['ok']) {
        $ufValue = $ufLastDay['uf'];
      }
    }

    // Sueldo bruto
    $taxableIncome = $validatedData['baseSalary'] + $validatedData['gratification'] + $validatedData['transport'] + $validatedData['collation'];

    $factor = 0;
    $exempt = 0;
    $incomeTax = 0; // Impuesto a la renta

    foreach ($this->RANGES as $range) {
      if ($taxableIncome <= $range['maxAmount']) {
        $factor = $range['factor'];
        $exempt = $range['exempt'];

        // Impuesto a la renta = Monto tributable * factor - cantidad a rebajar
        $incomeTax = ($taxableIncome * $factor) - $exempt;
        break;
      }
    }

    $afpDiscount = $taxableIncome * ($validatedData['afpPercentage'] / 100);
    $healthForecastDiscount = 0;

    if ($validatedData['healthForecastCode'] === $this->AFP_FONASA_CODE) {
      $healthForecastDiscount = $taxableIncome * ($validatedData['healthForecastPercentage'] / 100);
    } else {
      if ($validatedData['healthForecastAmountType'] === $this->HEALTH_FORECAST_AMOUNT_TYPE_UF) {
        $healthForecastDiscount = $validatedData['healthForecastAmount'] * $ufValue;
      } else {
        $healthForecastDiscount = $validatedData['healthForecastAmount'];
      }
    }

    $totalDuties = $afpDiscount + $healthForecastDiscount + $incomeTax;

    $netSalary = $taxableIncome - $totalDuties;

    $dataToDisplay = [
      'baseSalary' => $this->formatNumberForDisplay($validatedData['baseSalary']),
      'gratification' => $this->formatNumberForDisplay($validatedData['gratification']),

      'afpName' => $validatedData['afpName'],
      'afpPercentage' => $validatedData['afpPercentage'],
      'afpDiscount' => $this->formatNumberForDisplay($afpDiscount),

      'healthForecastName' => $validatedData['healthForecastName'],
      'healthForecastAmountType' => $validatedData['healthForecastAmountType'],
      'healthForecastAmount' => $this->formatNumberForDisplay($validatedData['healthForecastAmount'], decimals: 1, round: false),
      'healthForecastDiscount' => $this->formatNumberForDisplay($healthForecastDiscount),

      'incomeTax' => $this->formatNumberForDisplay($incomeTax),
      'incomeTaxRaw' => $incomeTax,
      'factor' => $factor,
      'exempt' => $exempt,

      'transport' => $this->formatNumberForDisplay($validatedData['transport']),
      'collation' => $this->formatNumberForDisplay($validatedData['collation']),

      'totalAssets' => $this->formatNumberForDisplay($taxableIncome),
      'totalDuties' => $this->formatNumberForDisplay($totalDuties),
      'netSalary' => $this->formatNumberForDisplay($netSalary)
    ];

    $response['data'] = $dataToDisplay;

    return $response;
  }

  public function validate($formData = [])
  {
    $response = [
      'ok' => false,
      'message' => '',
      'data' => []
    ];

    if (
      !isset($formData['baseSalary'])
      || !isset($formData['gratification'])
      || !isset($formData['afp'])
      || !isset($formData['afpPercentage'])
      || !isset($formData['healthForecast'])
      || !isset($formData['healthForecastPercentage'])
      || !isset($formData['healthForecastAmountType'])
      || !isset($formData['healthForecastAmount'])
      || !isset($formData['transport'])
      || !isset($formData['collation'])
    ) {
      $response['message'] = 'Falta información para calcular la liquidación';
      return $response;
    }

    $baseSalary = preg_replace('/[^\d]+/', '', $formData['baseSalary']);
    $gratification = preg_replace('/[^\d]+/', '', $formData['gratification']);

    if (empty($baseSalary) || empty($gratification)) {
      $response['message'] = 'Sueldo base o gratificación inválidos';
      return $response;
    }

    if (!$this->isBetween($baseSalary, $this->MIN_BASE_SALARY, $this->MAX_BASE_SALARY)) {
      $response['message'] = 'Monto inválido del sueldo base [$' . $this->formatNumberForDisplay($this->MIN_BASE_SALARY) . ' - $' . $this->formatNumberForDisplay($this->MAX_BASE_SALARY) . ']';
      return $response;
    }

    if (!$this->isBetween($gratification, $this->MIN_GRATIFICATION, $this->MAX_GRATIFICATION)) {
      $response['message'] = 'Monto inválido de gratificación [$' . $this->formatNumberForDisplay($this->MIN_GRATIFICATION) . ' - $' . $this->formatNumberForDisplay($this->MAX_GRATIFICATION) . ']';
      return $response;
    }

    $afp = $formData['afp'];

    $afpObject = new Afp();
    $afps = $afpObject->getAll();

    $foundAfp = array_search(strtolower($afp), array_column($afps, 'value'));

    if ($foundAfp === false) {
      $response['message'] = 'AFP inválida';
      return $response;
    }

    $afpPercentage = preg_replace('/[^\d\.]+/', '', $formData['afpPercentage']);

    $healthForecast = explode('-', $formData['healthForecast']);

    $hf = new HealthForecast();
    $hfs = $hf->getAll();

    $foundHFCode = array_search($healthForecast[0], array_column($hfs, 'code'));

    if ($foundHFCode === false) {
      $response['message'] = 'Sistema de salud inválido';
      return $response;
    }

    if ($healthForecast[0] === $this->AFP_FONASA_CODE) {
      $healthForecastPercentage = preg_replace('/[^\d\.]+/', '', $formData['healthForecastPercentage']);

      $healthForecastAmountType = '';
      $healthForecastAmount = 0;
    } else {
      $healthForecastAmountType = strtolower($formData['healthForecastAmountType']);

      if (!in_array($healthForecastAmountType, $this->VALID_HEALTH_FORECAST_AMOUNT_TYPES)) {
        $response['message'] = 'Tipo de monto(UF, pesos) de Sistema de salud inválido';
        return $response;
      }

      if ($healthForecastAmountType === $this->HEALTH_FORECAST_AMOUNT_TYPE_UF) {
        $healthForecastAmount = preg_replace('/[^\d,]+/', '', $formData['healthForecastAmount']);

        $replace = [',' => '.', '.' => ''];
        $healthForecastAmount = strtr($healthForecastAmount, $replace);

        if (!$this->isBetween($healthForecastAmount, $this->MIN_AMOUNT_HC_UF, $this->MAX_AMOUNT_HC_UF)) {
          $response['message'] = 'Monto inválido de Sistema de salud [' . $this->formatNumberForDisplay($this->MIN_AMOUNT_HC_UF) . ' UF - ' . $this->formatNumberForDisplay($this->MAX_AMOUNT_HC_UF) . ' UF]';
          ;
          return $response;
        }
      } else {
        $healthForecastAmount = preg_replace('/[^\d]+/', '', $formData['healthForecastAmount']);
        if (!$this->isBetween($healthForecastAmount, $this->MIN_AMOUNT_HC_PESOS, $this->MAX_AMOUNT_HC_PESOS)) {
          $response['message'] = 'Monto inválido de Sistema de salud [$' . $this->formatNumberForDisplay($this->MIN_AMOUNT_HC_PESOS) . ' - $' . $this->formatNumberForDisplay($this->MAX_AMOUNT_HC_PESOS) . ']';
          return $response;
        }
      }

      $healthForecastPercentage = 0;
    }

    $transport = (int) preg_replace('/[^\d]+/', '', $formData['transport']);

    if ($transport !== $this->TRANSPORT_AMOUNT) {
      $response['message'] = 'Monto de transporte inválido';
      return $response;
    }

    if ($baseSalary < $this->MIN_BASE_SALARY_FOR_COLLATION) {
      $collation = (int) preg_replace('/[^\d]+/', '', $formData['collation']);

      if ($collation !== $this->COLLATION_AMOUNT) {
        $response['message'] = 'Monto de colación inválido';
        return $response;
      }
    } else {
      $collation = 0;
    }

    $validatedData = [
      'baseSalary' => $baseSalary,
      'gratification' => $gratification,

      'afpName' => $afp,
      'afpPercentage' => $afpPercentage,

      'healthForecastCode' => $healthForecast[0],
      'healthForecastName' => $healthForecast[1],
      'healthForecastPercentage' => $healthForecastPercentage,
      'healthForecastAmountType' => $healthForecastAmountType,
      'healthForecastAmount' => $healthForecastAmount,

      'transport' => $transport,
      'collation' => $collation
    ];

    $response['ok'] = true;
    $response['data'] = $validatedData;

    return $response;
  }

  private function formatNumberForDisplay($number, $round = true, $decimals = 0)
  {
    if ($round) {
      return number_format(round($number), $decimals, ',', '.');
    }

    return number_format($number, $decimals, ',', '.');
  }

  function isBetween($value, $min, $max)
  {
    return ($value >= $min && $value <= $max);
  }
}
