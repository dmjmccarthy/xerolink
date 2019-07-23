<?php
require __DIR__ . '/vendor/autoload.php';
use XeroPHP\Application\PrivateApplication;

require 'config.php';
$xero = new PrivateApplication($xeroconfig);

print_r($xero->load('Accounting\\Organisation')->execute());

?>