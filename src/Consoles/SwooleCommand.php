<?php

namespace Chenmobuys\LaravelSwoole\Consoles;


use Illuminate\Console\Command;

class SwooleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chen:swoole {action : start | stop | reload | reload_task | restart | quit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'swoole mode';

    /**
     * @var array
     */
    protected $actions = ['start', 'stop', 'reload', 'reload_task', 'restart', 'quit'];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $action = $this->argument('action');

        if(!in_array($action,$this->actions)){
            exit($this->getHelp());
        }

        $this->$action();
    }

    protected function isWin()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
    }

    protected function start()
    {
        if ($this->getPid()) {
            echo 'already running' . PHP_EOL;
            exit(1);
        }

        $host = '127.0.0.1';
        $port = '9501';

        $socket = @stream_socket_server("tcp://{$host}:{$port}");

        if (!$socket) {
            throw new \Exception("Address {$host}:{$port} already in use", 1);
        } else {
            fclose($socket);
        }

        $wrapper = \Chenmobuys\LaravelSwoole\Servers\HttpServer::class;
        $wrapper_file = (new \ReflectionClass($wrapper))->getFileName();

        $configs = [
            'wrapper' => $wrapper,
            'wrapper_file' => $wrapper_file,
            'host' => $host,
            'port' => $port,
            'root_path' => base_path(),
            'environment_path' => base_path(),
            'pid_file' => storage_path('swoole/swoole.pid'),
        ];

        $handle = popen(PHP_BINARY . ' ' . __DIR__ . '/../Entry.php', 'w');
        fwrite($handle, serialize($configs));
        fclose($handle);
    }

    /**
     * @return bool|string
     */
    protected function getPid()
    {
        $pid_file = storage_path('swoole/swoole.pid');

        if (file_exists($pid_file)) {
            $pid = (int) file_get_contents($pid_file);
            if (posix_getpgid($pid)) {
                return $pid;
            } else {
                unlink($pid_file);
            }
        }

        return false;
    }


}
