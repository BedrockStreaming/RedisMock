<?php

namespace M6Web\Component\RedisMock\tests\units;

use M6Web\Component\RedisMock\RedisMockFactory as Factory;
use M6Web\Component\RedisMock\RedisMock as Mock;
use mageekguy\atoum\test;

/**
 * Test class for RedisMockFactory
 */
class RedisMockFactory extends test
{
    /**
     * Test the mock
     * 
     * @return void
     */
    public function testMock()
    {
        $factory = new Factory();
        $mock    = $factory->getAdapter('StdClass');

        $this->assert
            ->object($mock)
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock_StdClass_Adapter')
            ->class(get_class($mock))
                ->extends('StdClass')
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
                ->isEqualTo(array('test1', 'test2'))
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

        $mock2 = $factory->getAdapter('StdClass');

        $this->assert
            ->object($mock2)
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock_StdClass_Adapter')
            ->class(get_class($mock))
                ->extends('StdClass');
    }

    /**
     * Test the mock with a complex base class
     * 
     * @return void
     */
    public function testMockComplex()
    {
        $factory = new Factory();
        $mock    = $factory->getAdapter('M6Web\Component\RedisMock\tests\units\RedisWithMethods');

        $this->assert
            ->object($mock)
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock_M6Web_Component_RedisMock_tests_units_RedisWithMethods_Adapter')
            ->class(get_class($mock))
                ->extends('M6Web\Component\RedisMock\tests\units\RedisWithMethods')
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
            ->array($mock->zRangeByScore('test', '-inf', '+inf', array('limit' => array(1, 2))))
                ->isEqualTo(array(
                    'test3',
                    'test2'
                ))
            ->array($mock->zrevrangebyscore('test', '+inf', '-inf', array('limit' => array(1, 2))))
                ->isEqualTo(array(
                    'test3',
                    'test1'
                ));
    }

    /**
     * Test the mock with a base class that implement unsupported Redis commands
     * 
     * @return void
     */
    public function testUnsupportedMock()
    {
        $factory = new Factory();
        $this->assert
            ->exception(function() use ($factory) {
                $factory->getAdapter('M6Web\Component\RedisMock\tests\units\RedisWithUnsupportedMethods');
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException');
    }

    /**
     * Test method getAdpaterClass
     * 
     * @return void
     */
    public function testGetAdapterClass()
    {
        $factory = new Factory();
        $this->assert
            ->string($class = $factory->getAdapterClass('M6Web\Component\RedisMock\tests\units\RedisWithNativeConstructor'))
                ->isEqualTo('M6Web\Component\RedisMock\RedisMock_M6Web_Component_RedisMock_tests_units_RedisWithNativeConstructor_Adapter_NativeConstructor')
            ->class($class)
                ->extends('M6Web\Component\RedisMock\tests\units\RedisWithNativeConstructor')
            ->when(function() use ($class) {
                $mock = new $class();
            })
                ->error()
                    ->exists()
            ->when(function() use ($class) {
                $mock = new $class(null);
            })
                ->error()
                    ->notExists();
    }

    public function testMockInterface()
    {
        $factory = new Factory();
        $mock    = $factory->getAdapter('M6Web\Component\RedisMock\tests\units\RedisWithAnInterface');

        $this->assert
            ->object($mock)
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock_M6Web_Component_RedisMock_tests_units_RedisWithAnInterface_Adapter')
            ->class(get_class($mock))
                ->extends('M6Web\Component\RedisMock\tests\units\RedisWithAnInterface')
                ->hasInterface('M6Web\Component\RedisMock\tests\units\AnInterface');

        $mock    = $factory->getAdapter('M6Web\Component\RedisMock\tests\units\RedisWithTwoInterfaces');

        $this->assert
            ->class(get_class($mock))
                ->hasInterface('M6Web\Component\RedisMock\tests\units\AnInterface')
                ->hasInterface('M6Web\Component\RedisMock\tests\units\AnotherInterface');
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

    public function zRangeByScore($key, $min, $max, array $options = array())
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

class RedisWithNativeConstructor
{
    public function __construct($param)
    {

    }
}

class RedisWithAnInterface implements AnInterface
{

    public function get($key) {

        return 'raoul';
    }
}

class RedisWithTwoInterfaces implements AnInterface, AnotherInterface
{

    public function get($key) {

        return 'raoul ^';
    }
}

interface AnInterface {

    public function get($key);
}

interface AnotherInterface {

}