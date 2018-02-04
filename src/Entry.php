<?php

if(file_exists(__DIR__ . '/../../../autoload.php')){
  require __DIR__ . '/../../../autoload.php';
}

$input = file_get_contents('php://stdin');

$configs = unserialize($input);

$server = new \Chenmobuys\LaravelSwoole\Server($configs);

$server->start();
