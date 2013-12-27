<?php

namespace M6Web\Component\RedisMock\tests\units;

use mageekguy\atoum\test;
use M6Web\Component\RedisMock\RedisMock as Redis;

/**
 * Redis mock test
 */
class RedisMock extends test
{
    public function testSetGetDelExists()
    {
        $redisMock = new Redis();

        $this->assert
            ->boolean($redisMock->exists('test'))
                ->isFalse()
            ->variable($redisMock->get('test'))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(0)
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
            ->string($redisMock->type('test'))
                ->isEqualTo('string')
            ->boolean($redisMock->exists('test'))
                ->isTrue()
            ->string($redisMock->get('test'))
                ->isEqualTo('something')
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->variable($redisMock->get('test'))
                ->isNull()
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->boolean($redisMock->exists('test'))
                ->isFalse()
            ->string($redisMock->set('test1', 'something'))
                ->isEqualTo('OK')
            ->string($redisMock->set('test2', 'something else'))
                ->isEqualTo('OK')
            ->exception(function() use ($redisMock) {
                $redisMock->del('test1', 'test2');
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
            ->integer($redisMock->del('test1'))
                ->isEqualTo(1)
            ->integer($redisMock->del('test2'))
                ->isEqualTo(1);
    }

    public function testTtl()
    {
        $redisMock = new Redis();

        $this->assert
            ->string($redisMock->set('test', 'something', 1))
                ->isEqualTo('OK');
        sleep(2); // epic !
        $this->assert
            ->boolean($redisMock->exists('test'))
                ->isFalse()
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(-1);

        $this->assert
            ->string($redisMock->set('test', 'something', 10))
                ->isEqualTo('OK')
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(10)
            ->integer($redisMock->ttl('raoul'))
                ->isEqualTo(-2)
            ->variable($redisMock->set('test2', 'something'))
            ->integer($redisMock->ttl('test2'))
                ->isEqualTo(-1);

        $this->assert
            ->variable($redisMock->del('test'))
            ->integer($redisMock->sadd('test', 'one'))
                ->isEqualTo(1)
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(-1);
    }

    public function testExpire()
    {
        $redisMock = new Redis();

        $this->assert
            ->integer($redisMock->sadd('test', 'one'))
            ->integer($redisMock->expire('test', 2))
                ->isEqualTo(0)
            ->variable($redisMock->del('test'));

        $this->assert
            ->string($redisMock->set('test', 'something', 10))
                ->isEqualTo('OK')
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(10)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1)
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->expire('test', 10))
                ->isEqualTo(0);
    }

    public function testIncr()
    {
        $redisMock = new Redis();

        $this->assert
            ->variable($redisMock->get('test'))
                ->isNull()
            ->integer($redisMock->incr('test'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('string')
            ->integer($redisMock->incr('test'))
                ->isEqualTo(2)
            ->integer($redisMock->incr('test'))
                ->isEqualTo(3)
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
            ->variable($redisMock->incr('test'))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('none');
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
                ->containsValues(array('something', 'someting_else'))
            ->array($redisMock->keys('*o*'))
                ->hasSize(3)
                ->containsValues(array('something', 'someting_else', 'others'))
            ->array($redisMock->keys('*[ra]s*'))
                ->hasSize(1)
                ->containsValues(array('others'))
            ->array($redisMock->keys('*[rl]s*'))
                ->hasSize(2)
                ->containsValues(array('someting_else', 'others'))
            ->array($redisMock->keys('somet?ing*'))
                ->hasSize(1)
                ->containsValues(array('something'))
            ->array($redisMock->keys('somet*ing*'))
                ->hasSize(2)
                ->containsValues(array('something', 'someting_else'));
    }

    public function testSAddSMembersSIsMemberSRem()
    {
        $redisMock = new Redis();

        $this->assert
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
            ->variable($redisMock->sadd('test', 'test1'))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->array($redisMock->smembers('test'))
                ->isEmpty()
            ->integer($redisMock->sismember('test', 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->srem('test', 'test1'))
                ->isEqualTo(0)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('set')
            ->exception(function() use ($redisMock) {
                $redisMock->sadd('test', 'test3', 'test4');
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
            ->integer($redisMock->sismember('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqualTo(0)
            ->array($redisMock->smembers('test'))
                ->hasSize(1)
                ->containsValues(array('test1'))
            ->integer($redisMock->srem('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->sismember('test', 'test1'))
                ->isEqualTo(0)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->sadd('test', 'test2'))
                ->isEqualTo(1)
            ->array($redisMock->smembers('test'))
                ->hasSize(2)
                ->containsValues(array('test1', 'test2'))
            ->exception(function() use ($redisMock) {
                $redisMock->srem('test', 'test1', 'test2');
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
            ->integer($redisMock->del('test'))
                ->isEqualTo(2);
    }

    public function testZAddZRemZRemRangeByScore()
    {
        $redisMock = new Redis();

        $this->assert
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
            ->variable($redisMock->zadd('test', 1, 'test1'))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->integer($redisMock->zrem('test', 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('zset')
            ->exception(function() use ($redisMock) {
                $redisMock->zadd('test', 2, 'test1', 30, 'test2');
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
            ->integer($redisMock->zadd('test', 2, 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->zrem('test', 'test1'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
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
            ->exception(function() use ($redisMock) {
                $redisMock->zrem('test', 'test1', 'test2', 'test3');
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
            ->integer($redisMock->zremrangebyscore('test', '-inf', '+inf'))
                ->isEqualTo(3)
            ->integer($redisMock->del('test'))
                ->isEqualTo(0);
    }

    public function testZRange()
    {
        $redisMock = new Redis();

        $redisMock->zadd('test', 1, 'test4');
        $redisMock->zadd('test', 15, 'test2');
        $redisMock->zadd('test', 2, 'test3');
        $redisMock->zadd('test', 1, 'test1');
        $redisMock->zadd('test', 30, 'test5');
        $redisMock->zadd('test', 0, 'test6');

        $this->assert
            ->array($redisMock->zrange('test', 0, 2))
                ->isEqualTo(array(
                    'test6',
                    'test1',
                    'test4',
                ))
            ->array($redisMock->zrange('test', 8, 2))
                ->isEmpty()
            ->array($redisMock->zrange('test', -1, 2))
                ->isEmpty()
            ->array($redisMock->zrange('test', -3, 4))
                ->isEqualTo(array(
                    'test3',
                    'test2',
                ))
            ->array($redisMock->zrange('test', -20, 4))
                ->isEqualTo(array(
                    'test6',
                    'test1',
                    'test4',
                    'test3',
                    'test2',
                ))
            ->array($redisMock->zrange('test', -2, 20))
                ->isEqualTo(array(
                    'test2',
                    'test5',
                ))
            ->array($redisMock->zrange('test', 1, -1))
                ->isEqualTo(array(
                    'test1',
                    'test4',
                    'test3',
                    'test2',
                    'test5',
                ))
            ->array($redisMock->zrange('test', 1, -3))
                ->isEqualTo(array(
                    'test1',
                    'test4',
                    'test3',
                ))
            ->array($redisMock->zrange('test', -2, -1))
                ->isEqualTo(array(
                    'test2',
                    'test5',
                ))
            ->exception(function() use ($redisMock) {
                $redisMock->zrange('test', 1, -3, true);
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
            ->integer($redisMock->del('test'))
                ->isEqualTo(6);

    }

    public function testZRevRange()
    {
        $redisMock = new Redis();

        $redisMock->zadd('test', 1, 'test4');
        $redisMock->zadd('test', 15, 'test2');
        $redisMock->zadd('test', 2, 'test3');
        $redisMock->zadd('test', 1, 'test1');
        $redisMock->zadd('test', 30, 'test5');
        $redisMock->zadd('test', 0, 'test6');

        $this->assert
            ->array($redisMock->zrevrange('test', 0, 2))
                ->isEqualTo(array(
                    'test5',
                    'test2',
                    'test3',
                ))
            ->array($redisMock->zrevrange('test', 8, 2))
                ->isEmpty()
            ->array($redisMock->zrevrange('test', -1, 2))
                ->isEmpty()
            ->array($redisMock->zrevrange('test', -3, 4))
                ->isEqualTo(array(
                    'test4',
                    'test1',
                ))
            ->array($redisMock->zrevrange('test', -20, 4))
                ->isEqualTo(array(
                    'test5',
                    'test2',
                    'test3',
                    'test4',
                    'test1',
                ))
            ->array($redisMock->zrevrange('test', -2, 20))
                ->isEqualTo(array(
                    'test1',
                    'test6',
                ))
            ->array($redisMock->zrevrange('test', 1, -1))
                ->isEqualTo(array(
                    'test2',
                    'test3',
                    'test4',
                    'test1',
                    'test6',
                ))
            ->array($redisMock->zrevrange('test', 1, -3))
                ->isEqualTo(array(
                    'test2',
                    'test3',
                    'test4',
                ))
            ->array($redisMock->zrevrange('test', -2, -1))
                ->isEqualTo(array(
                    'test1',
                    'test6',
                ))
            ->exception(function() use ($redisMock) {
                $redisMock->zrevrange('test', -2, -1, true);
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
            ->integer($redisMock->del('test'))
                ->isEqualTo(6);

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
            ->array($redisMock->zrangebyscore('test', '-inf', '15', array('limit' => array(0, 2))))
                ->isEqualTo(array(
                    'test6',
                    'test1',
                ))
            ->array($redisMock->zrangebyscore('test', '-inf', '15', array('limit' => array(1, 2))))
                ->isEqualTo(array(
                    'test1',
                    'test4',
                ))
            ->array($redisMock->zrangebyscore('test', '-inf', '15', array('limit' => array(1, 3))))
                ->isEqualTo(array(
                    'test1',
                    'test4',
                    'test3',
                ))
            ->exception(function() use ($redisMock) {
                $redisMock->zrangebyscore('test', '-inf', '15', array('limit' => array(1, 3), 'withscores' => true));
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
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
            ->array($redisMock->zrevrangebyscore('test', '15', '-inf', array('limit' => array(0, 2))))
                ->isEqualTo(array(
                    'test2',
                    'test3',
                ))
            ->array($redisMock->zrevrangebyscore('test', '15', '-inf', array('limit' => array(1, 2))))
                ->isEqualTo(array(
                    'test3',
                    'test4',
                ))
            ->array($redisMock->zrevrangebyscore('test', '15', '-inf', array('limit' => array(1, 3))))
                ->isEqualTo(array(
                    'test3',
                    'test4',
                    'test1',
                ))
            ->exception(function() use ($redisMock) {
                $redisMock->zrevrangebyscore('test', '15', '-inf', array('limit' => array(1, 3), 'withscores' => true));
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
            ->integer($redisMock->del('test'))
                ->isEqualTo(6);
    }

    public function testHSetHGetHexistsHGetAll()
    {
        $redisMock = new Redis();

        $this->assert
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
            ->variable($redisMock->hset('test', 'test1', 'something'))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->variable($redisMock->hget('test', 'test1'))
                ->isNull()
            ->array($redisMock->hgetall('test'))
                ->isEmpty()
            ->integer($redisMock->hexists('test', 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->hset('test', 'test1', 'something'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('hash')
            ->string($redisMock->hget('test', 'test1'))
                ->isEqualTo('something')
            ->integer($redisMock->hset('test', 'test1', 'something else'))
                ->isEqualTo(0)
            ->string($redisMock->hget('test', 'test1'))
                ->isEqualTo('something else')
            ->array($redisMock->hgetall('test'))
                ->hasSize(1)
                ->containsValues(array('something else'))
            ->integer($redisMock->hset('test', 'test2', 'something'))
                ->isEqualTo(1)
            ->array($redisMock->hgetall('test'))
                ->hasSize(2)
                ->containsValues(array('something', 'something else'))
            ->integer($redisMock->hexists('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->hexists('test', 'test3'))
                ->isEqualTo(0)
            ->integer($redisMock->del('test'))
                ->isEqualTo(2);
    }

    public function testFlushDb()
    {
        $redisMock = new Redis();

        $this->assert
            ->string($redisMock->set('test', 'a'))
                ->isEqualTo('OK')
            ->boolean($redisMock->exists('test'))
                ->isTrue()
            ->string($redisMock->flushdb())
                ->isEqualTo('OK')
            ->boolean($redisMock->exists('test'))
                ->isFalse();
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
                    ->sismember('test', 'test1')
                    ->srem('test', 'test1')
                    ->del('test')
                    ->zadd('test', 1, 'test1')
                    ->zrange('test', 0, 1)
                    ->zrangebyscore('test', '-inf', '+inf')
                    ->zrevrange('test', 0, 1)
                    ->zrevrangebyscore('test', '+inf', '-inf')
                    ->zrem('test', 'test1')
                    ->zremrangebyscore('test', '-inf', '+inf')
                    ->del('test')
                    ->hset('test', 'test1', 'something')
                    ->hget('test', 'test1')
                    ->hexists('test', 'test1')
                    ->hgetall('test')
                    ->del('test')
                    ->type('test')
                    ->execute()
            )
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock');
    }
}