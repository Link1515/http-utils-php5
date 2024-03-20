<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Link1515\HttpUtilsPhp5\Request;

header('Content-Type: application/json');
Request::setIpHeaderFilterChain([
  'HTTP_CLIENT_IP',
  'HTTP_X_FORWARDED_FOR',
  'REMOTE_ADDR'
]);
$req = Request::getInstance();

echo $req;
