<?php

namespace M6Web\Component\RedisMock\Providers;

use Illuminate\Support\ServiceProvider;
use M6Web\Component\RedisMock\MockPhpRedisConnector;

class RedisMockServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->make('redis')->extend('mock', function () {
            return new MockPhpRedisConnector();
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }


}
