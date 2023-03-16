<?php

namespace tests;

use Illuminate\Support\Facades\Redis;
use M6Web\Component\RedisMock\MockPhpRedisConnection;
use Orchestra\Testbench\TestCase;

class RedisMockTest extends TestCase
{

    use EnvironmentSetUp;


    public function testRedisConnectionInstance()
    {

        $this->assertInstanceOf(MockPhpRedisConnection::class, Redis::connection());

    }

    public function testSetAndGet()
    {

        Redis::set('key', 'test');
        $this->assertEquals('test', Redis::get('key'));

    }

    public function testPipeline()
    {

        Redis::pipeline(function ($pipe) {
            $pipe->set('key1', 'test1');
            $pipe->set('key2', 'test2');
        });

        $this->assertEquals('test2', Redis::get('key2'));
    }

}



