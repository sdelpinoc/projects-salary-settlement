<?php
require_once dirname(__FILE__) . '../../classes/afp.php';
require_once dirname(__FILE__) . '../../classes/healthForecast.php';
require_once dirname(__FILE__) . '../../classes/SalarySettlement.php';

$aValidGetData = ['afp', 'healthForecast'];
$aValidGetAction = ['get'];

$aValidPostData = ['salarySettlement'];
$aValidPostAction = ['calculate'];

$input = file_get_contents('php://input');

if (!isset($_GET)) {
  sendResponse(1);
} else if (
  (!empty($_POST) && $_SERVER['REQUEST_METHOD'] == 'POST')
  || ($input !== false && !empty($input))
) {
  $url = $_GET;
  if (!isset($url['data']) || !isset($url['action'])) {
    sendResponse(2);
  }

  if (!empty($_POST) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $postData = $_POST;
  } else {
    $postData = json_decode(file_get_contents('php://input'), true);
  }

  $data = $url['data'];
  $action = $url['action'];

  if (!in_array($data, $aValidPostData) || !in_array($action, $aValidPostAction)) {
    sendResponse(3);
  }

  if ($data === 'salarySettlement') {
    switch ($action) {
      case 'calculate':
        // Validate form data
        $salarySettlement = new SalarySettlement();

        $validatedData = $salarySettlement->validate($postData);

        if (!$validatedData['ok']) {
          sendResponse(6, message: $validatedData['message']);
        }

        $calculatedSalary = $salarySettlement->calculate($validatedData['data']);

        sendResponse(code: 4, ok: true, data: $calculatedSalary['data'], message: '');
        break;

      default:
        sendResponse(5);
        break;
    }
  }
} else if (isset($_GET)) {
  $url = $_GET;

  if (!isset($url['data']) || !isset($url['action'])) {
    sendResponse(2);
  }

  $data = $url['data'];
  $action = $url['action'];

  if (!in_array($data, $aValidGetData) || !in_array($action, $aValidGetAction)) {
    sendResponse(3);
  }

  if ($data === 'afp') {
    switch ($action) {
      case 'get':
        $afp = new Afp();
        $data = $afp->get();

        sendResponse(code: 4, ok: true, data: $data, message: '');
        break;

      default:
        sendResponse(5);
        break;
    }
  }

  if ($data === 'healthForecast') {
    switch ($action) {
      case 'get':
        $hf = new HealthForecast();
        $data = $hf->get();
        sendResponse(code: 4, ok: true, data: $data, message: '');
        break;

      default:
        break;
    }
  }
}

function sendResponse($code, $ok = false, $data = array(), $message = 'Invalid request')
{
  $response = [
    'ok' => $ok,
    'code' => $code,
    'data' => $data,
    'message' => $message
  ];

  header('Content-type: application/json; charset=UTF-8');
  print json_encode($response);
  exit();
}
