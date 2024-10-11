<?php
require_once dirname(__FILE__) . '../../tableGateways/AfpGateway.php';
class AfpController
{
  private $db;
  private $requestMethod;

  private $afpGateway;

  public function __construct($db, $requestMethod)
  {
    $this->db = $db;
    $this->requestMethod = $requestMethod;

    $this->afpGateway = new AfpGateway($db);
  }

  public function processRequest()
  {
    switch ($this->requestMethod) {
      case 'GET':
        $response = $this->getAllAfp();
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

  private function getAllAfp()
  {
    $result = $this->afpGateway->findAll();

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