<?php

namespace M6Web\Component\RedisMock\Adapter;

use M6Web\Component\RedisMock;

/**
 * Adapter allowing to setup a Redis Mock inheriting of an arbitrary class
 */
class RedisMockAdapter
{
    public static function getMock($classToExtend)
    {
        $classz = '
            namespace M6Web\Component\RedisMock;
            class pseudoRedisMock extends '.$classToExtend.' {
                static protected $data     = array();
                static protected $pipeline = false;
                use RedisMockTrait;
        }';
        // create the pseudoRedisMock class
        eval($classz);
    }
}