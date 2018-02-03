<?php

namespace Chenmobuys\LaravelSwoole\Providers;


use Illuminate\Support\ServiceProvider;

class SwooleProvider extends ServiceProvider
{
    protected $commands = [
        \Chenmobuys\LaravelSwoole\Consoles\SwooleCommand::class,
    ];

    public function boot()
    {

    }

    public function register()
    {
        $this->commands($this->commands);
    }

}