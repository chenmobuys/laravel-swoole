<?php

namespace Chenmobuys\LaravelSwoole\Servers;

use swoole_server;
use Chenmobuys\LaravelSwoole\Contracts\ServerInterface;

class SwooleServer extends BaseServer implements ServerInterface
{
    /**
     * @return mixed
     */
    public function start()
    {
        $callbacks = array_merge([
            'Start' => [$this, 'onServerStart'],
            'Shutdown' => [$this, 'onServerShutdown'],
            'WorkerStart' => [$this, 'onWorkerStart'],
        ], $this->callbacks);

        foreach ($callbacks as $on => $method) {
            $this->server->on($on, $method);
        }
        return $this->server->start();
    }

    /**
     * @codeCoverageIgnore
     */
    public function onServerStart()
    {
        // put pid
        file_put_contents(
            $this->configs['pid_file'],
            $this->getPid()
        );
    }

    public function onWorkerStart($serv, $worker_id)
    {
        $server = $this->server;
        $this->app->singleton('chen.server', function ($app) use ($server) {
            return $server;
        });
    }

    /**
     * @codeCoverageIgnore
     */
    public function onServerShutdown($serv)
    {
        @unlink($this->configs['pid_file']);
    }
    
    /**
     * event callback
     * @param  string $event start receive shutdown WorkerStart close request
     * @param  callable $callback event handler
     */
    public function on($event, callable $callback)
    {
        return $this->server->on($event, $callback);
    }

    /**
     * @param $fd
     * @param $content
     * @return mixed
     */
    public function send($fd, $content)
    {
        return $this->server->send($fd, $content);
    }

    /**
     * @param $fd
     * @return mixed
     */
    public function close($fd)
    {
        return $this->server->close($fd);
    }

    /**
     * @return mixed
     */
    public function getPid()
    {
        return $this->server->master_pid;
    }

    /**
     * @return array
     */
    public static function getParams()
    {
        return [
            'reactor_num',
            'worker_num',
            'max_request' => 2000,
            'max_conn',
            'task_worker_num',
            'task_ipc_mode',
            'task_max_request',
            'task_tmpdir',
            'dispatch_mode',
            'message_queue_key',
            'daemonize' => 1,
            'backlog',
            'log_file' => [self::class, 'getLogFile'],
            'log_level',
            'heartbeat_check_interval',
            'heartbeat_idle_time',
            'open_eof_check',
            'open_eof_split',
            'package_eof',
            'open_length_check',
            'package_length_type',
            'package_max_length',
            'open_cpu_affinity',
            'cpu_affinity_ignore',
            'open_tcp_nodelay',
            'tcp_defer_accept',
            'ssl_cert_file',
            'ssl_method',
            'user',
            'group',
            'chroot',
            'pipe_buffer_size',
            'buffer_output_size',
            'enable_unsafe_event',
            'discard_timeout_request',
            'enable_reuse_port',
        ];
    }
}
