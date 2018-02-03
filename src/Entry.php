<?php

require __DIR__.'/../../../vendor/autoload.php';

$input = file_get_contents('php://stdin');

$configs = unserialize($input);

$server = new \Chenmobuys\LaravelSwoole\Server($configs);

$server->start();
