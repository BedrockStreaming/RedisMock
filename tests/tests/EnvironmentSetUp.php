<?php

namespace tests;

use M6Web\Component\RedisMock\Providers\RedisMockServiceProvider;

trait EnvironmentSetUp
{

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', true);
        $app['config']->set('database.redis.client', 'mock');

        $app->register(RedisMockServiceProvider::class);
    }
}
