<?php

echo __DIR__;

if(file_exists(__DIR__ . '/../../../vendor/autoload.php')){
  require __DIR__ . '/../../../vendor/autoload.php';
}else{
  echo 1;
}

if(file_exists(__DIR__ . '/../../vendor/autoload.php')){
  require __DIR__ . '/../../vendor/autoload.php';
}else{
  echo 2;
}

if(file_exists(__DIR__ . '/../vendor/autoload.php')){
  require __DIR__ . '/../vendor/autoload.php';
}else{
  echo 3;
}

die;

$input = file_get_contents('php://stdin');

$configs = unserialize($input);

$server = new \Chenmobuys\LaravelSwoole\Server($configs);

$server->start();
