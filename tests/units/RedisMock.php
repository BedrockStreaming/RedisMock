<?php

namespace M6Web\Component\RedisMock\tests\units;

use mageekguy\atoum\test;
use M6Web\Component\RedisMock\RedisMock as Redis;

/**
 * Redis mock test
 */
class RedisMock extends test
{
    public function testSetGetDel()
    {
        $redisMock = new Redis();

        $this->assert
            ->variable($redisMock->get('test'))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(0)
            ->object($redisMock->set('test', 'something'))
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock')
            ->string($redisMock->get('test'))
                ->isEqualTo('something')
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->variable($redisMock->get('test'))
                ->isNull();
    }

    public function testIncr()
    {
        $redisMock = new Redis();

        $this->assert
            ->variable($redisMock->get('test'))
                ->isNull()
            ->integer($redisMock->incr('test'))
                ->isEqualTo(1)
            ->integer($redisMock->incr('test'))
                ->isEqualTo(2)
            ->integer($redisMock->incr('test'))
                ->isEqualTo(3)
            ->object($redisMock->set('test', 'something'))
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock')
            ->variable($redisMock->incr('test'))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(1);
    }

    public function testKeys() {
        $redisMock = new Redis();

        $this->assert
            ->object($redisMock->set('something', 'a'))
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock')
            ->object($redisMock->set('someting_else', 'b'))
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock')
            ->object($redisMock->set('others', 'c'))
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock')
            ->array($redisMock->keys('some'))
                ->isEmpty()
            ->array($redisMock->keys('some*'))
                ->containsValues(['something', 'someting_else'])
            ->array($redisMock->keys('*o*'))
                ->containsValues(['something', 'someting_else', 'others'])
            ->array($redisMock->keys('*[ra]s*'))
                ->containsValues(['others'])
            ->array($redisMock->keys('*[rl]s*'))
                ->containsValues(['someting_else', 'others'])
            ->array($redisMock->keys('somet?ing*'))
                ->containsValues(['something'])
            ->array($redisMock->keys('somet*ing*'))
                ->containsValues(['something', 'someting_else']);
    }

    public function testZaddZrem()
    {
        $redisMock = new Redis();

        $this->assert
            ->integer($redisMock->zrem('test', 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->zadd('test', 2, 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->zrem('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->zadd('test', 1, 'test2'))
                ->isEqualTo(1)
            ->integer($redisMock->del('test'))
                ->isEqualTo(2);
    }

    public function testZRangeByScore()
    {
        $redisMock = new Redis();

        $redisMock->zadd('test', 1, 'test4');
        $redisMock->zadd('test', 15, 'test2');
        $redisMock->zadd('test', 2, 'test3');
        $redisMock->zadd('test', 1, 'test1');
        $redisMock->zadd('test', 30, 'test5');
        $redisMock->zadd('test', 0, 'test6');

        $this->assert
            ->array($redisMock->zrangebyscore('test', '-inf', '+inf'))
                ->isEqualTo(array(
                    'test6',
                    'test1',
                    'test4',
                    'test3',
                    'test2',
                    'test5',
                ))
            ->array($redisMock->zrangebyscore('test', '-inf', '15'))
                ->isEqualTo(array(
                    'test6',
                    'test1',
                    'test4',
                    'test3',
                    'test2',
                ))
            ->array($redisMock->zrangebyscore('test', '-inf', '(15'))
                ->isEqualTo(array(
                    'test6',
                    'test1',
                    'test4',
                    'test3',
                ))
            ->array($redisMock->zrangebyscore('test', '2', '+inf'))
                ->isEqualTo(array(
                    'test3',
                    'test2',
                    'test5',
                ))
            ->array($redisMock->zrangebyscore('test', '(2', '+inf'))
                ->isEqualTo(array(
                    'test2',
                    'test5',
                ))
            ->array($redisMock->zrangebyscore('test', '2', '15'))
                ->isEqualTo(array(
                    'test3',
                    'test2',
                ))
            ->array($redisMock->zrangebyscore('test', '(1', '15'))
                ->isEqualTo(array(
                    'test3',
                    'test2',
                ))
            ->array($redisMock->zrangebyscore('test', '-inf', '15', ['limit' => [0, 2]]))
                ->isEqualTo(array(
                    'test6',
                    'test1',
                ))
            ->array($redisMock->zrangebyscore('test', '-inf', '15', ['limit' => [1, 2]]))
                ->isEqualTo(array(
                    'test1',
                    'test4',
                ))
             ->array($redisMock->zrangebyscore('test', '-inf', '15', ['limit' => [1, 3]]))
                ->isEqualTo(array(
                    'test1',
                    'test4',
                    'test3',
                ))
            ->integer($redisMock->del('test'))
                ->isEqualTo(6);
    }

    public function testZRevRangeByScore()
    {
        $redisMock = new Redis();

        $redisMock->zadd('test', 1, 'test4');
        $redisMock->zadd('test', 15, 'test2');
        $redisMock->zadd('test', 2, 'test3');
        $redisMock->zadd('test', 1, 'test1');
        $redisMock->zadd('test', 30, 'test5');
        $redisMock->zadd('test', 0, 'test6');

        $this->assert
            ->array($redisMock->zrevrangebyscore('test', '+inf', '-inf'))
                ->isEqualTo(array(
                    'test5',
                    'test2',
                    'test3',
                    'test4',
                    'test1',
                    'test6',
                ))
            ->array($redisMock->zrevrangebyscore('test', '15', '-inf'))
                ->isEqualTo(array(
                    'test2',
                    'test3',
                    'test4',
                    'test1',
                    'test6',
                ))
            ->array($redisMock->zrevrangebyscore('test', '(15', '-inf'))
                ->isEqualTo(array(
                    'test3',
                    'test4',
                    'test1',
                    'test6',
                ))
            ->array($redisMock->zrevrangebyscore('test', '+inf', '2'))
                ->isEqualTo(array(
                    'test5',
                    'test2',
                    'test3',
                ))
            ->array($redisMock->zrevrangebyscore('test', '+inf', '(2'))
                ->isEqualTo(array(
                    'test5',
                    'test2',
                ))
            ->array($redisMock->zrevrangebyscore('test', '15', '2'))
                ->isEqualTo(array(
                    'test2',
                    'test3',
                ))
            ->array($redisMock->zrevrangebyscore('test', '15', '(1'))
                ->isEqualTo(array(
                    'test2',
                    'test3',
                ))
            ->array($redisMock->zrevrangebyscore('test', '15', '-inf', ['limit' => [0, 2]]))
                ->isEqualTo(array(
                    'test2',
                    'test3',
                ))
            ->array($redisMock->zrevrangebyscore('test', '15', '-inf', ['limit' => [1, 2]]))
                ->isEqualTo(array(
                    'test3',
                    'test4',
                ))
            ->array($redisMock->zrevrangebyscore('test', '15', '-inf', ['limit' => [1, 3]]))
                ->isEqualTo(array(
                    'test3',
                    'test4',
                    'test1',
                ))
            ->integer($redisMock->del('test'))
                ->isEqualTo(6);
    }
}