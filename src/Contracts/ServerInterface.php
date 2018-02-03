<?php

namespace Chenmobuys\LaravelSwoole\Contracts;

interface ServerInterface
{
    public function __construct($configs);

    /**
     * @return mixed
     */
    public function start();

    /**
     * event callback
     * @param  string $event start receive shutdown WorkerStart close request
     * @param  callable $callback event handler
     */
    public function on($event, callable $callback);

    /**
     * @param $fd
     * @param $content
     * @return mixed
     */
    public function send($fd, $content);

    /**
     * @param $fd
     * @return mixed
     */
    public function close($fd);

    /**
     * @return mixed
     */
    public function getPid();

    /**
     * @return mixed
     */
    public static function getParams();

}