<?php
require_once dirname(__FILE__) . '../../classes/SalarySettlement.php';
class SalarySettlementController
{
  private $db;
  private $requestMethod;

  private $data;

  public function __construct($db, $requestMethod, $data)
  {
    $this->db = $db;
    $this->requestMethod = $requestMethod;

    $this->data = $data;
  }

  public function processRequest()
  {
    switch ($this->requestMethod) {
      case 'POST':
        $response = $this->calculate();
        break;

      default:
        $response = $this->notFoundResponse();
        break;
    }

    header($response['status_code_header']);
    if ($response['body']) {
      echo $response['body'];
    }
  }

  private function calculate()
  {
    // print '<pre>';
    // print_r($this->data);
    // print '</pre>';
    $salarySettlement = new SalarySettlement();
    $validatedData = $salarySettlement->validate($this->data);
    // print '<pre>';
    // print_r($validatedData);
    // print '</pre>';

    if (!$validatedData['ok']) {
      return ['status_code_header' => 'HTTP/1.1 200 OK', 'body' => json_encode($validatedData)];
    }

    $response = ['status_code_header' => 'HTTP/1.1 200 OK', 'body' => null];

    return $response;
  }

  private function notFoundResponse()
  {
    $response = ['status_code_header' => 'HTTP/1.1 404 Not Found', 'body' => null];
    return $response;
  }

  // private function invalidData($body)
  // {
  //   $response = ['status_code_header' => 'HTTP/1.1 400 Bad Request', 'body' => json_encode($body)];

  //   return $response;
  // }
}