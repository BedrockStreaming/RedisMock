<?php

namespace M6Web\Component\RedisMock\Adapter\tests\units;

use M6Web\Component\RedisMock\Adapter\RedisMockAdapter as Adapter;
use M6Web\Component\RedisMock\RedisMock;
use mageekguy\atoum\test;

/**
 * test class for RedisMockAdapter
 */
class RedisMockAdapter extends test
{
    /**
     * test the mock
     * @return void
     */
    public function testMock()
    {
        $adapter = new Adapter();
        $mockClass = $adapter->getAdapter('StdClass');
        $this->assert
            ->string($mockClass)
                ->isEqualTo('M6Web\Component\RedisMock\Adapter\RedisMock_StdClass')
            ->class($mockClass)
                ->extends('StdClass');

        $mock = new $mockClass(new RedisMock());
        $this->assert
            ->string($mock->set('test', 'data'))
                ->isEqualTo('OK')
            ->string($mock->get('test'))
                ->isEqualTo('data')
            ->integer($mock->del('test'))
                ->isEqualTo(1)
            ->integer($mock->sadd('test', 'test1'))
                ->isEqualTo(1)
            ->integer($mock->sAdd('test', 'test2'))
                ->isEqualTo(1)
            ->array($mock->sMembers('test'))
                ->isEqualTo(['test1', 'test2'])
            ->integer($mock->sRem('test', 'test1'))
                ->isEqualTo(1)
            ->integer($mock->sRem('test', 'test2'))
                ->isEqualTo(1)
            ->integer($mock->del('test'))
                ->isEqualTo(0)
            ->exception(function() use ($mock) {
                $mock->punsubscribe();
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException');
    }

    /**
     * test the mock with a complex base class
     * @return void
     */
    public function testMockComplex()
    {
        $adapter = new Adapter();
        $mockClass = $adapter->getAdapter('M6Web\Component\RedisMock\Adapter\tests\units\RedisWithMethods');
        $this->assert
            ->string($mockClass)
                ->isEqualTo('M6Web\Component\RedisMock\Adapter\RedisMock_M6Web_Component_RedisMock_Adapter_tests_units_RedisWithMethods')
            ->class($mockClass)
                ->extends('M6Web\Component\RedisMock\Adapter\tests\units\RedisWithMethods');

        $mock = new $mockClass(new RedisMock());
        $this->assert
            ->string($mock->set('test', 'data'))
                ->isEqualTo('OK')
            ->string($mock->get('test'))
                ->isEqualTo('data')
            ->integer($mock->del('test'))
                ->isEqualTo(1)
            ->integer($mock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->integer($mock->zadd('test', 30, 'test2'))
                ->isEqualTo(1)
            ->integer($mock->zadd('test', 15, 'test3'))
                ->isEqualTo(1)
            ->array($mock->zrangebyscore('test', '-inf', '+inf'))
                ->isEqualTo(array(
                    'test1',
                    'test3',
                    'test2'
                ))
            ->array($mock->zRangeByScore('test', '-inf', '+inf', ['limit' => [1, 2]]))
                ->isEqualTo(array(
                    'test3',
                    'test2'
                ))
            ->array($mock->zrevrangebyscore('test', '+inf', '-inf', ['limit' => [1, 2]]))
                ->isEqualTo(array(
                    'test3',
                    'test1'
                ));
    }

    public function testUnsupportedMock()
    {
        $adapter = new Adapter();
        $this->assert
            ->exception(function() use ($adapter) {
                $adapter->getAdapter('M6Web\Component\RedisMock\Adapter\tests\units\RedisWithUnsupportedMethods');
            });
    }
}

class RedisWithMethods
{
    public function aNoRedisMethod()
    {

    }

    public function set($key, $data)
    {
        throw new \Exception('Not mocked');
    }

    public function get($key)
    {
        throw new \Exception('Not mocked');
    }

    public function zRangeByScore($key, $min, $max, array $options = [])
    {
        throw new \Exception('Not mocked');
    }
}

class RedisWithUnsupportedMethods
{
    public function set($key, $data)
    {
        throw new \Exception('Not mocked');
    }

    public function punsubscribe($pattern = null)
    {
        throw new \Exception('Not mocked');
    }
}