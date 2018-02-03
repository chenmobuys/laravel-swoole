<?php

namespace Chenmobuys\LaravelSwoole\Servers;


use swoole_http_request;
use Illuminate\Events\Dispatcher;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request as IlluminateRequest;
use Chenmobuys\LaravelSwoole\Contracts\ServerInterface;

use Illuminate\Support\Facades\Facade;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

abstract class BaseServer
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Kernel
     */
    protected $kernel;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var ServerInterface
     */
    protected $server;

    /**
     * @var array
     */
    protected $configs;

    /**
     * @var DiactorosFactory
     */
    protected $diactorosFactory;

    /**
     * @var array
     */
    protected $callbacks = [];

    public function __construct($configs)
    {
        $this->configs = $configs;

        $this->prepareStart();
    }

    protected function prepareStart()
    {
        if(file_exists(__DIR__ . '/../../../../vendor/autoload.php')){
            require __DIR__ . '/../../../../vendor/autoload.php';
        }
        
        if(file_exists(__DIR__ . '/../../../vendor/autoload.php')){
            require __DIR__ . '/../../../vendor/autoload.php';
        }

        foreach ($this->callbacks as $callback) {
            $callback($this);
        }

        $this->app = $this->getApp();

        $this->kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);

        $virus = function () {
            // Insert bofore BootProviders
            array_splice($this->bootstrappers, -1, 0, [\Illuminate\Foundation\Bootstrap\SetRequestForConsole::class]);
        };

        $virus = \Closure::bind($virus, $this->kernel, $this->kernel);

        $virus();

        $this->kernel->bootstrap();

        $this->configs = array_merge($this->app['config']->get('chen.swoole', []),$this->configs);

        $this->events = $this->app['events'];

        chdir(public_path());
    }

    /**
     * @param $request
     * @param IlluminateRequest|null $illuminate_request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleRequest($request, IlluminateRequest $illuminate_request = null)
    {
        print_r($request->server);
        clearstatcache();

        $kernel = $this->kernel;

        try {
            ob_start();

            if (!$illuminate_request) {
                if ($request instanceof ServerRequestInterface) {
                    $request = (new HttpFoundationFactory)->createRequest($request);
                    $illuminate_request = IlluminateRequest::createFromBase($request);
                } elseif ($request instanceof swoole_http_request) {
                    $illuminate_request = $this->convertRequest($request);
                } else {
                    $illuminate_request = IlluminateRequest::createFromBase($request);
                }
            }
            
            print_r($illumninate_request);
            
            $this->events->fire('swoole.requesting', [$illuminate_request]);

            $illuminate_response = $kernel->handle($illuminate_request);

            $content = $illuminate_response->getContent();

            if (strlen($content) === 0 && ob_get_length() > 0) {
                $illuminate_response->setContent(ob_get_contents());
            }

            ob_end_clean();

        } catch (\Exception $e) {
            echo '[ERR] ' . $e->getFile() . '(' . $e->getLine() . '): ' . $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
        } catch (\Throwable $e) {
            echo '[ERR] ' . $e->getFile() . '(' . $e->getLine() . '): ' . $e->getMessage() . PHP_EOL;
            echo $e->getTraceAsString() . PHP_EOL;
        } finally {
            if (isset($illuminate_response)) {
                $kernel->terminate($illuminate_request, $illuminate_response);
            }
            $this->events->fire('swoole.requested', [$illuminate_request, $illuminate_response]);

            $this->clean($illuminate_request);
        }

        return $illuminate_response;
    }

    /**
     * @param ServerRequestInterface $psrRequest
     * @return \Psr\Http\Message\ResponseInterface|\Zend\Diactoros\Response|static
     */
    public function onPsrRequest(ServerRequestInterface $psrRequest)
    {
        $illuminate_response = $this->handleRequest($psrRequest);
        if (!$this->diactorosFactory) {
            $this->diactorosFactory = new DiactorosFactory;
        }
        return $this->diactorosFactory->createResponse($illuminate_response);

    }

    /**
     * @param $request
     * @param string $classname
     * @return mixed
     */
    protected function convertRequest($request, $classname = IlluminateRequest::class)
    {
        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $cookie = isset($request->cookie) ? $request->cookie : [];
        $server = isset($request->server) ? $request->server : [];
        $header = isset($request->header) ? $request->header : [];
        $files = isset($request->files) ? $request->files : [];
        // $attr = isset($request->files) ? $request->files : [];

        $content = $request->rawContent() ?: null;

        return new $classname($get, $post, []/* attributes */, $cookie, $files, $server, $content);
    }

    /**
     * @param IlluminateRequest $request
     */
    protected function clean(IlluminateRequest $request)
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            if (is_callable([$session, 'clear'])) {
                $session->clear(); // @codeCoverageIgnore
            } else {
                $session->flush();
            }
        }

        // Clean laravel cookie queue
        $cookies = $this->app->make(CookieJar::class);
        foreach ($cookies->getQueuedCookies() as $name => $cookie) {
            $cookies->unqueue($name);
        }

    }


    /**
     * @return Application
     */
    protected function getApp()
    {
        if (!$this->app) {

            $app = new \Illuminate\Foundation\Application(
                realpath($this->configs['root_path'])
            );

            $app->singleton(
                \Illuminate\Contracts\Http\Kernel::class,
                \App\Http\Kernel::class
            );

            $app->singleton(
                \Illuminate\Contracts\Console\Kernel::class,
                \App\Console\Kernel::class
            );

            $app->singleton(
                \Illuminate\Contracts\Debug\ExceptionHandler::class,
                \App\Exceptions\Handler::class
            );

            return $app;
        }

        return $this->app;
    }


}
