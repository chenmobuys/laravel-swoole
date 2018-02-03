<?php

namespace Chenmobuys\LaravelSwoole;

use Exception;
use ReflectionClass;
use Chenmobuys\LaravelSwoole\Contracts\ServerInterface;

class Server
{
    protected $configs;

    public function __construct($configs)
    {
        $this->configs = $configs;

        if (!class_exists($this->configs['wrapper'])) {
            require $this->configs['wrapper_file'];
        }

        $ref = new ReflectionClass($this->configs['wrapper']);
        if(!$ref->implementsInterface(ServerInterface::class)) {
            throw new Exception($this->configs['wrapper']." must be instance of Laravoole\\Wrapper\\ServerInterface", 1);
        }
    }

    public function getWrapper()
    {
        return $this->configs['wrapper'];
    }

    public function start()
    {
        $wrapper = new $this->configs['wrapper']($this->configs);
        $wrapper->start();
    }
}