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

            ->string($redisMock->setex('test1', 5, 'something'))
                ->isEqualTo('OK')
            ->string($redisMock->type('test1'))
                ->isEqualTo('string')
            ->boolean($redisMock->exists('test1'))
                ->isTrue()
            ->string($redisMock->get('test1'))
                ->isEqualTo('something')
            ->integer($redisMock->del('test1'))
                ->isEqualTo(1)
            ->variable($redisMock->get('test1'))
                ->isNull()
            ->string($redisMock->type('test1'))
                ->isEqualTo('none')
            ->boolean($redisMock->exists('test1'))
                ->isFalse()

            ->string($redisMock->set('test1', 'something'))
                ->isEqualTo('OK')
            ->string($redisMock->set('test2', 'something else'))
                ->isEqualTo('OK')
            ->integer($redisMock->del('test1', 'test2'))
                ->isEqualTo(2)
            ->string($redisMock->set('test1', 'something'))
                ->isEqualTo('OK')
            ->string($redisMock->set('test2', 'something else'))
                ->isEqualTo('OK')
            ->integer($redisMock->del(array('test1', 'test2')))
                ->isEqualTo(2)

            ->string($redisMock->set('test3', 'something', 1))
                ->isEqualTo('OK')
            ->string($redisMock->setex('test4', 2, 'something else'))
                ->isEqualTo('OK')
            ->integer($redisMock->ttl('test3'))
                ->isEqualTo(1)
            ->integer($redisMock->ttl('test4'))
                ->isEqualTo(2)
            ->string($redisMock->get('test3'))
                ->isEqualTo('something')
            ->string($redisMock->get('test4'))
                ->isEqualTo('something else');
        sleep(3);
        $this->assert
            ->variable($redisMock->get('test3'))
                ->isNull()
            ->variable($redisMock->get('test4'))
                ->isNull()

            ->string($redisMock->set('test', 'something', 1))
                ->isEqualTo('OK')
            ->string($redisMock->type('test'))
                ->isEqualTo('string');
        sleep(2);
        $this->assert
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->string($redisMock->set('test', 'something', 1))
                ->isEqualTo('OK')
            ->boolean($redisMock->exists('test'))
                ->isTrue();
        sleep(2);
        $this->assert
            ->boolean($redisMock->exists('test'))
                ->isFalse();

        //setnx
        $this->assert
            ->integer($redisMock->setnx('test-setnx', 'lala'))
                ->isEqualTo(1)
            ->integer($redisMock->setnx('test-setnx', 'lala2'))
                ->isEqualTo(0)
            ->integer($redisMock->del('test-setnx'))
                ->isEqualTo(1)
            ->integer($redisMock->setnx('test-setnx', 'lala'))
                ->isEqualTo(1)
            ->string($redisMock->type('test-setnx'))
            ->isEqualTo('string');

        //setnx with expire
        $this->assert
            ->integer($redisMock->setnx("test-setnx-expire", "lala"))
                ->isEqualTo(1)
            ->integer($redisMock->expire("test-setnx-expire", 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->setnx("test-setnx-expire", "lala"))
                ->isEqualTo(1);

        //mget/mset test (roughly based on hmset/hmset tests)
        $this->assert
            ->array($redisMock->mget(array('raoul', 'test1')))
               ->isEqualTo(array(
                   null,
                   null,
               ))
            ->string($redisMock->mset(array(
                'test1' => 'somthing',
                'raoul' => 'nothing',
            )))
            ->array($redisMock->mget(array('raoul', 'test1')))
                ->isEqualTo(array(
                    'nothing',
                    'somthing',
                ))
            ->integer($redisMock->expire('raoul', 1))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test1', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->mget(array('raoul', 'test1')))
            ->isEqualTo(array(
                null,
                null,
            ));
    }

    public function testExpireTtl()
    {
        $redisMock = new Redis();

        $this->assert
            ->integer($redisMock->expire('test', 2))
                ->isEqualTo(0)
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(-2)
            ->integer($redisMock->sadd('test', 'one'))
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(-1)
            ->integer($redisMock->expire('test', 2))
                ->isEqualTo(1)
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(2);
        sleep(1);
        $this->assert
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->ttl('test'))
                ->isEqualTo(-2);


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
            ->integer($redisMock->get('test'))
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
                ->isEqualTo('none')
            ->integer($redisMock->incr('test'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->incr('test'))
                ->isEqualTo(1);
    }

    public function testIncrby()
    {
        $redisMock = new Redis();

        $this->assert
            ->variable($redisMock->get('test'))
                ->isNull()
            ->integer($redisMock->incrby('test', 5))
                ->isEqualTo(5)
            ->integer($redisMock->get('test'))
                ->isEqualTo(5)
            ->string($redisMock->type('test'))
                ->isEqualTo('string')
            ->integer($redisMock->incrby('test', 1))
                ->isEqualTo(6)
            ->integer($redisMock->incrby('test', 2))
                ->isEqualTo(8)
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
            ->variable($redisMock->incrby('test', 4))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->integer($redisMock->incrby('test', 2))
                ->isEqualTo(2)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->incrby('test', 3))
                ->isEqualTo(3);
    }

    public function testDecr()
    {
        $redisMock = new Redis();

        $this->assert
            ->variable($redisMock->get('test'))
                ->isNull()
            ->integer($redisMock->decr('test'))
                ->isEqualTo(-1)
            ->integer($redisMock->get('test'))
                ->isEqualTo(-1)
            ->string($redisMock->type('test'))
                ->isEqualTo('string')
            ->integer($redisMock->decr('test'))
                ->isEqualTo(-2)
            ->integer($redisMock->decr('test'))
                ->isEqualTo(-3)
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
            ->variable($redisMock->decr('test'))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->integer($redisMock->decr('test'))
                ->isEqualTo(-1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->decr('test'))
                ->isEqualTo(-1);
    }

    public function testDecrby()
    {
        $redisMock = new Redis();

        $this->assert
            ->variable($redisMock->get('test'))
                ->isNull()
            ->integer($redisMock->decrby('test', 5))
                ->isEqualTo(-5)
            ->integer($redisMock->get('test'))
                ->isEqualTo(-5)
            ->string($redisMock->type('test'))
                ->isEqualTo('string')
            ->integer($redisMock->decrby('test', 1))
                ->isEqualTo(-6)
            ->integer($redisMock->decrby('test', 2))
                ->isEqualTo(-8)
            ->string($redisMock->set('test', 'something'))
                ->isEqualTo('OK')
            ->variable($redisMock->decrby('test', 4))
                ->isNull()
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->integer($redisMock->decrby('test', 2))
                ->isEqualTo(-2)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->decrby('test', 3))
                ->isEqualTo(-3);
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
                ->containsValues(array('something', 'someting_else'))
            ->array($redisMock->keys('*'))
                ->hasSize(3)
                ->containsValues(array('something', 'someting_else', 'others'))
            ->integer($redisMock->expire('others', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->keys('*'))
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
            ->integer($redisMock->sadd('test', array('test3', 'test4')))
                ->isEqualTo(2)
            ->array($redisMock->smembers('test'))
                ->hasSize(4)
            ->integer($redisMock->sadd('test', array('test4', 'test5')))
                ->isEqualTo(1)
            ->integer($redisMock->sadd('test', 'test6', 'test7'))
                 ->isEqualTo(2)
            ->integer($redisMock->sadd('test', 'test7', 'test8'))
                 ->isEqualTo(1)
            ->array($redisMock->smembers('test'))
                ->hasSize(8)
                ->containsValues(array('test1', 'test2', 'test3', 'test4', 'test5', 'test6', 'test7', 'test8'))
            ->integer($redisMock->srem('test', array('test1', 'test2')))
                 ->isEqualTo(2)
            ->integer($redisMock->srem('test', 'test3', 'test4'))
                 ->isEqualTo(2)
            ->integer($redisMock->srem('test', array('test5', 'test55')))
                 ->isEqualTo(1)
            ->integer($redisMock->srem('test', 'test6', 'test66'))
                 ->isEqualTo(1)
            ->array($redisMock->smembers('test'))
                ->hasSize(2)
                ->containsValues(array('test7', 'test8'))
            ->integer($redisMock->del('test'))
                ->isEqualTo(2)
            ->array($redisMock->smembers('test'))
                ->hasSize(0)
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqualTo(1)
            ->array($redisMock->smembers('test'))
                ->hasSize(1)
                ->containsValues(array('test1'))
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->smembers('test'))
                ->isEmpty()
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->sismember('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->sismember('test', 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->sadd('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->srem('test', 'test1'))
                ->isEqualTo(0);
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
            ->integer($redisMock->zremrangebyscore('test', '-100', '100'))
                ->isEqualTo(0)
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
                ->isEqualTo(0)
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->zrem('test', 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->zremrangebyscore('test', '0', '2'))
                ->isEqualTo(0);
    }

    public function testZRange()
    {
        $redisMock = new Redis();

        $this->assert
            ->array($redisMock->zrange('test', -100, 100))
                ->isEmpty();

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
            ->array($redisMock->zrange('test', 1, -3, true))
                ->isEqualTo(array(
                    'test1' => 1,
                    'test4' => 1,
                    'test3' => 2,
                ))
            ->integer($redisMock->del('test'))
                ->isEqualTo(6)
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->array($redisMock->zrange('test', 0, 1))
                ->hasSize(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->zrange('test', 0, 1))
                ->isEmpty();

    }

    public function testZRevRange()
    {
        $redisMock = new Redis();

        $this->assert
            ->array($redisMock->zrevrange('test', -100, 100))
                ->isEmpty();

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
            ->array($redisMock->zrevrange('test', 1, -3, true))
                ->isEqualTo(array(
                    'test2' => 15,
                    'test3' => 2,
                    'test4' => 1,
                ))
            ->integer($redisMock->del('test'))
                ->isEqualTo(6)
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->array($redisMock->zrevrange('test', 0, 1))
                ->hasSize(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->zrange('test', 0, 1))
                ->isEmpty();

    }

    public function testZRangeByScore()
    {
        $redisMock = new Redis();

        $this->assert
            ->array($redisMock->zrangebyscore('test', '-inf', '+inf'))
                ->isEmpty();

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
            ->array($redisMock->zrangebyscore('test', '-inf', '15', array('limit' => array(1, 3), 'withscores' => true)))
                ->isEqualTo(array(
                    'test1' => 1,
                    'test4' => 1,
                    'test3' => 2,
                ))
            ->integer($redisMock->del('test'))
                ->isEqualTo(6)
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->array($redisMock->zrangebyscore('test', '0', '1'))
                ->hasSize(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->zrangebyscore('test', '0', '1'))
                ->isEmpty();
    }

    public function testZRevRangeByScore()
    {
        $redisMock = new Redis();

        $this->assert
            ->array($redisMock->zrevrangebyscore('test', '+inf', '-inf'))
                ->isEmpty();

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
            ->array($redisMock->zrevrangebyscore('test', '15', '-inf', array('limit' => array(1, 3), 'withscores' => true)))
                ->isEqualTo(array(
                    'test3' => 2,
                    'test4' => 1,
                    'test1' => 1,
                ))
            ->integer($redisMock->del('test'))
                ->isEqualTo(6)
            ->integer($redisMock->zadd('test', 1, 'test1'))
                ->isEqualTo(1)
            ->array($redisMock->zrevrangebyscore('test', '1', '0'))
                ->hasSize(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->zrevrangebyscore('test', '1', '0'))
                ->isEmpty();;
    }

    public function testHSetHMSetHGetHDelHExistsHGetAll()
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
                ->isEqualTo(2)
            ->integer($redisMock->hset('test', 'test1', 'something'))
                ->isEqualTo(1)
            ->integer($redisMock->hset('test', 'test2', 'something else'))
                ->isEqualTo(1)
            ->integer($redisMock->hdel('test', 'test2'))
                ->isEqualTo(1)
            ->integer($redisMock->hdel('test', 'test3'))
                ->isEqualTo(0)
            ->integer($redisMock->hdel('raoul', 'test2'))
                ->isEqualTo(0)
            ->string($redisMock->type('test'))
                ->isEqualTo('hash')
            ->integer($redisMock->hdel('test', 'test1'))
                ->isEqualTo(1)
            ->string($redisMock->type('test'))
                ->isEqualTo('none')
            ->string($redisMock->hmset('test', array(
                'test1'  => 'somthing',
                'blabla' => 'anything',
                'raoul'  => 'nothing',
            )))
                ->isEqualTo('OK')
            ->array($redisMock->hgetall('test'))
                ->isEqualTo(array(
                    'test1'  => 'somthing',
                    'blabla' => 'anything',
                    'raoul'  => 'nothing',
                ))
            ->array($redisMock->hmget('test', array('raoul', 'test1')))
                ->isEqualTo(array(
                    'raoul'  => 'nothing',
                    'test1'  => 'somthing',
                ))
            ->array($redisMock->hmget('test', array('raoul', 'oogabooga')))
                ->isEqualTo(array(
                    'raoul'  => 'nothing',
                    'oogabooga'  => null,
                ))
            ->array($redisMock->hmget('oogabooga', array('raoul', 'test1')))
                ->isEqualTo(array(
                    'raoul'  => null,
                    'test1'  => null,
                ))
            ->integer($redisMock->del('test'))
                ->isEqualTo(3)
            ->exception(function () use ($redisMock) {
                $redisMock->hdel('test', 'test1', 'test2');
            })
                ->isInstanceOf('\M6Web\Component\RedisMock\UnsupportedException')
            ->integer($redisMock->hset('test', 'test1', 'something'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->hset('test', 'test1', 'something'))
                ->isEqualTo(1)
            ->string($redisMock->hget('test', 'test1'))
                ->isEqualTo('something')
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->variable($redisMock->hget('test', 'test1'))
                ->isNull()
            ->integer($redisMock->hset('test', 'test1', 'something'))
                ->isEqualTo(1)
            ->integer($redisMock->hexists('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->hexists('test', 'test1'))
                ->isEqualTo(0)
            ->integer($redisMock->hset('test', 'test1', 'something'))
                ->isEqualTo(1)
            ->array($redisMock->hgetall('test'))
                ->hasSize(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->hgetall('test'))
                ->isEmpty()
            ->integer($redisMock->hset('test', 'test1', 'something'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->hdel('test', 'test1'))
                ->isEqualTo(0)
            ->string($redisMock->hmset('test', array(
                'test1'  => 'somthing',
                'blabla' => 'anything',
                'raoul'  => 'nothing',
            )))
                ->isEqualTo('OK')
            ->array($redisMock->hgetall('test'))
                ->isEqualTo(array(
                    'test1'  => 'somthing',
                    'blabla' => 'anything',
                    'raoul'  => 'nothing',
                ))
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->hgetall('test'))
                ->isEmpty()
            ->string($redisMock->hmset('test', array(
                'test1' => 'somthing',
                'raoul' => 'nothing',
            )))
            ->array($redisMock->hmget('test', array('raoul', 'test1')))
                ->isEqualTo(array(
                    'raoul' => 'nothing',
                    'test1' => 'somthing',
                ))
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->array($redisMock->hmget('test', array('raoul', 'test1')))
            ->isEqualTo(array(
                'raoul' => null,
                'test1' => null,
            ));
    }

    public function testLPushRPushLRemLTrim()
    {
        $redisMock = new Redis();

        $this->assert
            ->array($redisMock->getData())
                ->isEmpty()
            ->integer($redisMock->rpush('test', 'blabla'))
                ->isIdenticalTo(1)
            ->integer($redisMock->rpush('test', 'something'))
                ->isIdenticalTo(2)
            ->integer($redisMock->rpush('test', 'raoul'))
                ->isIdenticalTo(3)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('blabla', 'something', 'raoul')))
            ->integer($redisMock->lpush('test', 'raoul'))
                ->isIdenticalTo(4)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('raoul', 'blabla', 'something', 'raoul')))
            ->integer($redisMock->lrem('test', 2, 'blabla'))
                ->isIdenticalTo(1)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('raoul', 'something', 'raoul')))
            ->integer($redisMock->lrem('test', 1, 'raoul'))
                ->isIdenticalTo(1)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('something', 'raoul')))
            ->integer($redisMock->rpush('test', 'raoul'))
                ->isIdenticalTo(3)
            ->integer($redisMock->rpush('test', 'raoul'))
                ->isIdenticalTo(4)
            ->integer($redisMock->lpush('test', 'raoul'))
                ->isIdenticalTo(5)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('raoul', 'something', 'raoul', 'raoul', 'raoul')))
            ->integer($redisMock->lrem('test', -2, 'raoul'))
                ->isIdenticalTo(2)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('raoul', 'something', 'raoul')))
            ->integer($redisMock->rpush('test', 'raoul'))
                ->isIdenticalTo(4)
            ->integer($redisMock->rpush('test', 'raoul'))
                ->isIdenticalTo(5)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('raoul', 'something', 'raoul', 'raoul', 'raoul')))
            ->integer($redisMock->lrem('test', 0, 'raoul'))
                ->isIdenticalTo(4)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('something')))
            ->integer($redisMock->rpush('test', 'blabla'))
                ->isIdenticalTo(2)
            ->integer($redisMock->rpush('test', 'something'))
                ->isIdenticalTo(3)
            ->integer($redisMock->rpush('test', 'raoul'))
                ->isIdenticalTo(4)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('something', 'blabla', 'something', 'raoul')))
            ->string($redisMock->ltrim('test', 0, -1))
                ->isIdenticalTo('OK')
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('something', 'blabla', 'something', 'raoul')))
            ->string($redisMock->ltrim('test', 1, -1))
                ->isIdenticalTo('OK')
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('blabla', 'something', 'raoul')))
            ->string($redisMock->ltrim('test', -2, 2))
                ->isIdenticalTo('OK')
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('something', 'raoul')))
            ->string($redisMock->ltrim('test', 0, 2))
                ->isIdenticalTo('OK')
            ->integer($redisMock->lpush('test', 'raoul'))
                ->isIdenticalTo(3)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('raoul', 'something', 'raoul')))
            ->string($redisMock->ltrim('test', -3, -2))
                ->isIdenticalTo('OK')
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('raoul', 'something')))
            ->string($redisMock->ltrim('test', -1, 0))
                ->isIdenticalTo('OK')
            ->boolean($redisMock->exists('test'))
                ->isIdenticalTo(false)
            ->integer($redisMock->lpush('test', 'raoul'))
                ->isIdenticalTo(1)
            ->array($redisMock->getData())
                ->isEqualTo(array('test' => array('raoul')))
            ->integer($redisMock->del('test'))
                ->isEqualTo(1)
            ->integer($redisMock->rpush('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->rpush('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->lpush('test', 'test1'))
                ->isEqualTo(2)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->lpush('test', 'test1'))
                ->isEqualTo(1)
            ->string($redisMock->ltrim('test', 0, -1))
                ->isEqualTo('OK')
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->string($redisMock->ltrim('test', 0, -1))
                ->isEqualTo('OK')
            ->array($redisMock->getData())
                ->isEmpty()
            ->integer($redisMock->rpush('test', 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->rpush('test', 'test1'))
                ->isEqualTo(2)
            ->integer($redisMock->lrem('test', 1 , 'test1'))
                ->isEqualTo(1)
            ->integer($redisMock->expire('test', 1))
                ->isEqualTo(1);
        sleep(2);
        $this->assert
            ->integer($redisMock->lrem('test', 1 , 'test1'))
                ->isEqualTo(0);
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
                    ->mset(array('test1' => 'something', 'test2' => 'nothing'))
                    ->mget(array('test1', 'test2'))
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
                    ->hmset('test', array('test1' => 'something'))
                    ->hmget('test', array('test1'))
                    ->hexists('test', 'test1')
                    ->hgetall('test')
                    ->del('test')
                    ->lpush('test', 'test1')
                    ->lrange('test', 0, -1)
                    ->ltrim('test', 0, -1)
                    ->lrem('test', 1, 'test1')
                    ->rpush('test', 'test1')
                    ->type('test')
                    ->ttl('test')
                    ->lpop('test')
                    ->rpop('test')
                    ->expire('test', 1)
                    ->setnx("test123", "somethingelse")
                    ->execute()
            )
                ->isInstanceOf('M6Web\Component\RedisMock\RedisMock');
    }

    public function testTransactions()
    {
        $redisMock = new Redis();

        $redisMock->set('test', 'something');

        $this->assert
            // Discard test
            ->string(
                $redisMock
                    ->multi()
                    ->set('test2', '*¨LPLR$`ù^')
                    ->get('test2')
                    ->discard()
            )
                ->isEqualTo('OK')
            // Multi results test
            ->array(
                $redisMock
                    ->multi()
                    ->set('test3', 'AZERTY*%£')
                    ->incr('test4')
                    ->incr('test4')
                    ->set('test5', 'todelete')
                    ->del('test5')
                    ->get('test3')
                    ->exec()
            )
                ->isEqualTo(array(
                    'OK',
                    1,
                    2,
                    'OK',
                    1,
                    'AZERTY*%£',
                ))
            // Exec reset test
            ->array(
                $redisMock
                    ->multi()
                    ->incr('test4')
                    ->exec()
            )
                ->isEqualTo(array(
                    3,
                ));

        // Exec results reset by Discard
        $redisMock->discard();

        $this->assert
            ->array($redisMock->exec())
                ->isEmpty();
    }

    public function testDbsize()
    {
        $redisMock = new Redis();

        $redisMock->set('test', 'something');

        $this->assert
            ->integer($redisMock->dbsize())
            ->isEqualTo(1);

        $redisMock->set('test2', 'raoul');

        $this->assert
            ->integer($redisMock->dbsize())
            ->isEqualTo(2);

        $redisMock->expire('test2', 1);
        sleep(2);

        $this->assert
            ->integer($redisMock->dbsize())
            ->isEqualTo(1);

        $redisMock->flushdb();

        $this->assert
            ->integer($redisMock->dbsize())
            ->isEqualTo(0);
    }

    public function testLpopRpop()
    {
        $redisMock = new Redis();
        $key       = uniqid();

        $this->assert
            ->variable($redisMock->rpop($key))
                ->isNull()
            ->variable($redisMock->lpop($key))
                ->isNull()
            ->integer($redisMock->lpush($key, 'foo'))
                ->isIdenticalTo(1)
            ->integer($redisMock->lpush($key, 'bar'))
                ->isIdenticalTo(2)
            ->string($redisMock->lpop($key))
                ->isIdenticalTo('bar')
            ->integer($redisMock->rpush($key, 'redis'))
                ->isIdenticalTo(2)
            ->string($redisMock->rpop($key))
                ->isIdenticalTo('redis')
            ->string($redisMock->rpop($key))
                ->isIdenticalTo('foo')
            ->variable($redisMock->rpop($key))
                ->isNull()
            ->variable($redisMock->lpop($key))
                ->isNull()
        ;

        $lKey = uniqid();
        $rKey = uniqid();

        $redisMock->lpush($lKey, uniqid());
        $redisMock->rpush($rKey, uniqid());

        $redisMock->expire($lKey, 1);
        $redisMock->expire($rKey, 1);
        sleep(2);

        $this->assert
            ->variable($redisMock->rpop($rKey))
                ->isNull()
            ->variable($redisMock->lpop($lKey))
                ->isNull()
        ;
    }

    public function testLrange()
    {
        $redisMock = new Redis();
        $key       = uniqid();

        $this
            ->array($redisMock->lrange($key, 1, 1))
                ->isEmpty()
        ;

        $redisMock->lpush($key, 'foo');
        $redisMock->lpush($key, 'bar');
        $redisMock->lpush($key, 'other');
        $redisMock->lpush($key, 'none');

        $this
            ->array($redisMock->lrange($key, 1, 2))
                ->isEqualTo(array('other', 'bar'))
            ->array($redisMock->lrange($key, 1, 100))
                ->isEqualTo(array('other', 'bar', 'foo'))
            ->array($redisMock->lrange($key, -100, 100))
                ->isEqualTo(array('none', 'other', 'bar', 'foo'))
        ;

        $redisMock->expire($key, 1);
        sleep(2);

        $this
            ->array($redisMock->lrange($key, 1, 1))
                ->isEmpty()
        ;
    }
}
