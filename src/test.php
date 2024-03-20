<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Link1515\HttpUtilsPhp5\Request;

header('Content-Type: application/json');
$req = Request::getInstance();

echo $req;
