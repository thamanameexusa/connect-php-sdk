<?php
require 'vendor/autoload.php';
require 'autoload.php';
require_once 'tests/support/ApiShim.php';

$GLOBALS['ACCESS_TOKEN'] = getenv('ACCESS_TOKEN');

$GLOBALS['SANDBOX_ACCESS_TOKEN'] = getenv('SANDBOX_ACCESS_TOKEN');

$configuration = new \SquareConnect\Configuration();
$configuration->setApiKey('Authorization', $GLOBALS['SANDBOX_ACCESS_TOKEN']);

$GLOBALS['API_CLIENT'] = new \SquareConnect\ApiClient($configuration);

$GLOBALS['SANDBOX_LOCATION_ID'] = getenv('SANDBOX_LOCATION_ID');



?>
