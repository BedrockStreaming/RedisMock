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
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
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
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
            ->variable($redisMock->incr('test'))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(1);
    }

    public function testKeys() {
        $redisMock = new Redis();

        $this->assert
            ->string($redisMock->set('something', 'a'))
                ->isEqualTo('OK')
            ->string($redisMock->set('someting_else', 'b'))
                ->isEqualTo('OK')
            ->string($redisMock->set('others', 'c'))
                ->isEqualTo('OK')
            ->array($redisMock->keys('some'))
                ->isEmpty()
            ->array($redisMock->keys('some*'))
                ->hasSize(2)
                ->containsValues(['something', 'someting_else'])
            ->array($redisMock->keys('*o*'))
                ->hasSize(3)
                ->containsValues(['something', 'someting_else', 'others'])
            ->array($redisMock->keys('*[ra]s*'))
                ->hasSize(1)
                ->containsValues(['others'])
            ->array($redisMock->keys('*[rl]s*'))
                ->hasSize(2)
                ->containsValues(['someting_else', 'others'])
            ->array($redisMock->keys('somet?ing*'))
                ->hasSize(1)
                ->containsValues(['something'])
            ->array($redisMock->keys('somet*ing*'))
                ->hasSize(2)
                ->containsValues(['something', 'someting_else']);
    }

    public function setSAddSMembersSRem()
    {
        $redisMock = new Redis();

        $this->assert
            ->array($redisMock->smembers('test'))
                ->isEmpty()
            ->integer($redisMock->srem('test', 'test1'))
                ->isEqual(0)
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqual(1)
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqual(0)
            ->array($redisMock->smembers('test'))
                ->hasSize(1)
                ->containsValues(['test1'])
            ->integer($redisMock->srem('test', 'test1'))
                ->isEqual(1)
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqual(1)
            ->integer($redisMock->sadd('test', 'test2'))
                ->isEqual(1)
            ->array($redisMock->smembers('test'))
                ->hasSize(2)
                ->containsValues(['test1', 'test2'])
            ->integer($redisMock->del('test'))
                ->isEqual(2);
    }

    public function testZAddZRemZRemRangeByScore()
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
            ->integer($redisMock->zadd('test', 30, 'test2'))
                ->isEqualTo(1)
            ->integer($redisMock->zadd('test', -1, 'test3'))
                ->isEqualTo(1)
            ->integer($redisMock->zremrangebyscore('test', '-3', '(-1'))
                ->isEqualTo(0)
            ->integer($redisMock->zremrangebyscore('test', '-3', '-1'))
                ->isEqualTo(1)
            ->integer($redisMock->zadd('test', -1, 'test3'))
                ->isEqualTo(1)
            ->integer($redisMock->zremrangebyscore('test', '-inf', '+inf'))
                ->isEqualTo(3)
            ->integer($redisMock->del('test'))
                ->isEqualTo(0);
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

    public function testHSetHGetHexistsHGetAll()
    {
        $redisMock = new Redis();

        $this->assert
            ->variable($redisMock->hget('test', 'test1'))
                ->isNull()
            ->array($redisMock->hgetall('test'))
                ->isEmpty()
            ->integer($redisMock->hexists('test', 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->hset('test', 'test1', 'something'))
                ->isEqualTo(1)
            ->string($redisMock->hget('test', 'test1'))
                ->isEqualTo('something')
            ->integer($redisMock->hset('test', 'test1', 'something else'))
                ->isEqualTo(0)
            ->string($redisMock->hget('test', 'test1'))
                ->isEqualTo('something else')
            ->array($redisMock->hgetall('test'))
                ->hasSize(1)
                ->containsValues(['something else'])
            ->integer($redisMock->hset('test', 'test2', 'something'))
                ->isEqualTo(1)
            ->array($redisMock->hgetall('test'))
                ->hasSize(2)
                ->containsValues(['something', 'something else'])
            ->integer($redisMock->hexists('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->hexists('test', 'test3'))
                ->isEqualTo(0)
            ->integer($redisMock->del('test'))
                ->isEqualTo(2);
    }

    public function testPipeline()
    {
        $redisMock = new Redis();

        $this->assert
            ->object(
                $redisMock->pipeline()
                    ->set('test', 'something')
                    ->get('test')
                    ->incr('test')
                    ->keys('test')
                    ->del('test')
                    ->sadd('test', 'test1')
                    ->smembers('test')
                    ->srem('test', 'test1')
                    ->del('test')
                    ->zadd('test', 1, 'test1')
                    ->zrangebyscore('test', '-inf', '+inf')
                    ->zrevrangebyscore('test', '+inf', '-inf')
                    ->zrem('test', 'test1')
                    ->zremrangebyscore('test', '-inf', '+inf')
                    ->del('test')
                    ->hset('test', 'test1', 'something')
                    ->hget('test', 'test1')
                    ->hexists('test', 'test1')
                    ->hgetall('test')
                    ->del('test')
                    ->execute()
            )
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock');
    }
}