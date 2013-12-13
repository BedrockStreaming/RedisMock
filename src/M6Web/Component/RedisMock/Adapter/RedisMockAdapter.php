<?php

namespace M6Web\Component\RedisMock\Adapter;

use M6Web\Component\RedisMock;

/**
 * Adapter allowing to setup a Redis Mock inheriting of an arbitrary class
 */
class RedisMockAdapter
{

    public static $mockNames = array();

    public static function getMock($classToExtend, $mockName = null)
    {
        if (is_null($mockName)) {
            $t = explode('\\', $classToExtend);
            $mockName = end($t);
        }
        if (!in_array($mockName, self::$mockNames)){
            $classz = '
            namespace M6Web\Component\RedisMock;
            class '.$mockName.' extends '.$classToExtend.' {
                static protected $data     = array();
                static protected $pipeline = false;
                use RedisMockTrait;
            }';
            // create the M6Web\Component\RedisMock\$mockName class
            eval($classz);

            self::$mockNames[] = $mockName;
        }
    }
}