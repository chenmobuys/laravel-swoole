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
    protected $signature = 'chen:swoole 
                            {action : start | stop | reload | reload_task | restart | quit}
                            {--port=9501 : port}';

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

        switch ($action) {

            case 'start':
                $this->start();
                break;
            case 'restart':
                $pid = $this->sendSignal(SIGTERM);
                $time = 0;
                while (posix_getpgid($pid) && $time <= 10) {
                    usleep(100000);
                    $time++;
                }
                if ($time > 100) {
                    echo 'timeout' . PHP_EOL;
                    exit(1);
                }
                $this->start();
                break;
            case 'stop':
            case 'quit':
            case 'reload':
            case 'reload_task':

                $map = [
                    'stop' => SIGTERM,
                    'quit' => SIGQUIT,
                    'reload' => SIGUSR1,
                    'reload_task' => SIGUSR2,
                ];
                $this->sendSignal($map[$action]);
                break;
        }
    }

    /**
     * @param $sig
     */
    protected function sendSignal($sig)
    {
        if ($pid = $this->getPid()) {

            posix_kill($pid, $sig);
        } else {

            echo "not running!" . PHP_EOL;
            exit(1);
        }
    }

    protected function start()
    {
        if ($this->getPid()) {
            echo 'already running' . PHP_EOL;
            exit(1);
        }

        $host = '127.0.0.1';
        $port = $this->option('port');

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
            'pid_file' => storage_path('swoole/swoole_http_'.$port.'.pid'),
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
        $pid_file = storage_path('swoole/swoole_http_'.$this->option('port').'.pid');

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
