<?php

namespace M6Web\Component\RedisMock\Adapter\tests\units;

use M6Web\Component\RedisMock\Adapter\RedisMockAdapter as Base;
use mageekguy\atoum\test;

/**
 * test class for RedisMockAdapter
 */
class RedisMockAdapter extends test
{
    public function testMock()
    {
        Base::getMock('\StdClass');
        $mock = new \mock\M6Web\Component\RedisMock\pseudoRedisMock;
        $this->assert
            ->object($mock)
                ->isInstanceOf('\StdClass')
            ->class('\mock\M6Web\Component\RedisMock\pseudoRedisMock')
                ->hasMethod('reset')
                ->hasMethod('set')
                ->hasMethod('get')
                ->hasMethod('zrevrangebyscore');
    }
}