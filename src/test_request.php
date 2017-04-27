<?php

require '../vendor/autoload.php';

require 'config.php';
require 'PrepaidGateRequest.php';

$pgRequest = new PrepaidGateRequest($glConfig);

$pgRequest->defineAction('AccountBalance');

$data = array();
$data['Currency'] = "USD";
$pgRequest->defineData($data);

$pgRequest->processRequest();
$result = $pgRequest->getRequestResult();

consoleLog($result);

?>
