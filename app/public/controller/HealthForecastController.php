<?php
require_once dirname(__FILE__) . '../../tableGateways/HealthForecastGateway.php';
class HealthForecastController
{
  private $db;
  private $requestMethod;

  private $healthForecastGateway;

  public function __construct($db, $requestMethod)
  {
    $this->db = $db;
    $this->requestMethod = $requestMethod;

    $this->healthForecastGateway = new HealthForecastGateway($db);
  }

  public function processRequest()
  {
    switch ($this->requestMethod) {
      case 'GET':
        $response = $this->getAllHealthForecast();
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

  public function getAllHealthForecast()
  {
    $result = $this->healthForecastGateway->findAll();

    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode($result);

    return $response;
  }

  private function notFoundResponse()
  {
    $response = ['status_code_header' => 'HTTP/1.1 404 Not Found', 'body' => null];
    return $response;
  }
}