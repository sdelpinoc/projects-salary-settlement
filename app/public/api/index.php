<?php
require_once dirname(__FILE__) . "../../bootstrap.php";
require_once dirname(__FILE__) . "../../controller/AfpController.php";
require_once dirname(__FILE__) . "../../controller/HealthForecastController.php";
require_once dirname(__FILE__) . "../../controller/SalarySettlementController.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// API Routes
// GET
// /afp,
// /healthForecast
// POST
// /salarySettlement

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

// print '<pre>';
// print_r($uri);
// print '</pre>';
// (
//   [0] => 
//   [1] => api
//   [2] => afp
// )
$validUris = [
  'afp',
  'healthForecast',
  'salarySettlement'
];

if (!in_array($uri[2], $validUris)) {
  header("HTTP/1.1 404 Not Found");
  exit();
}

$requestMethod = $_SERVER["REQUEST_METHOD"];

if (!in_array($requestMethod, ['GET', 'POST'])) {
  header("HTTP/1.1 405 Method Not Allowed");
  exit();
}

if ($uri[2] === 'afp') {
  $controller = new AfpController($db, $requestMethod);
} else if ($uri[2] === 'healthForecast') {
  $controller = new HealthForecastController($db, $requestMethod);
} else if ($uri[2] === 'salarySettlement') {
  if (!empty($_POST) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $postData = $_POST;
  } else {
    $postData = json_decode(file_get_contents('php://input'), true);
  }

  $controller = new SalarySettlementController($db, $requestMethod, $postData);
}

$controller->processRequest();