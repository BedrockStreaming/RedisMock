<?php

namespace M6Web\Component\RedisMock;

/**
* Redis mock class
*
* @author Florent Dubost <fdubost.externe@m6.fr>
* @author Denis Roussel <denis.roussel@m6.fr>
*/
class RedisMock
{
    static protected $data     = array();
    static protected $pipeline = false;

    public function reset()
    {
        self::$data = array();

        return $this;
    }

    public function getData()
    {
        return self::$data;
    }

    // Strings

    public function get($key)
    {
        if (!isset(self::$data[$key]) || is_array(self::$data[$key]))
        {
            return null;
        }

        return self::$data[$key];
    }

    public function set($key, $value)
    {
        self::$data[$key] = $value;

        return $this;
    }

    public function incr($key)
    {
        if (!isset(self::$data[$key]))
        {
            self::$data[$key] = 1;
        }
        elseif (!is_integer(self::$data[$key]))
        {
            return self::$pipeline ? $this : null;
        }
        else
        {
            self::$data[$key]++;
        }

        return self::$pipeline ? $this : self::$data[$key];
    }

    // Keys

    public function del($key)
    {
        if (!isset(self::$data[$key]))
        {
            return 0;
        }

        $deletedItems = count(self::$data[$key]);

        unset(self::$data[$key]);

        return $deletedItems;
    }

    public function keys($pattern)
    {
        $results = [];
        foreach (self::$data as $key => $value) {
            if (preg_match('#' . $pattern . '#', $key)) {
                $results[] = $key;
            }
        }

        return $results;
    }

    // Sets

    public function sadd($key, $value)
    {
        $isNew = !isset(self::$data[$key]);

        self::$data[$key][] = $value;

        return $isNew;
    }

    public function smembers($key)
    {
        if (!isset(self::$data[$key]))
        {
            return array();
        }

        return self::$data[$key];
    }

    public function srem($key, $value)
    {
        if (!isset(self::$data[$key]) || !in_array($value, self::$data[$key]))
        {
            return 0;
        }

        self::$data[$key] = array_diff(self::$data[$key], array($value));

        return 1;
    }

    // Hashes

    public function hset($key, $field, $value)
    {
        $isNew = !isset(self::$data[$key][$field]);
        
        self::$data[$key][$field] = $value;
    
        return $isNew;
    }

    public function hget($key, $field)
    {
        if (!isset(self::$data[$key][$field]))
        {
            return null;
        }

        return self::$data[$key][$field];
    }

    public function hgetall($key)
    {
        if (!isset(self::$data[$key]))
        {
            return null;
        }

        return self::$data[$key];
    }

    public function hexists($key, $field)
    {
        return isset(self::$data[$key][$field]);
    }

    // Sorted set

    public function zrangebyscore($key, $min, $max, $options = null)
    {
        if (!isset(self::$data[$key]) || !is_array(self::$data[$key])) {
            return null;
        }

        if (!is_array($options) || !is_array($options['limit']) || count($options['limit']) != 2) {
            $options['limit'] = [0, count(self::$data[$key])];
        }

        $array = self::$data[$key];
        uksort(self::$data[$key], function($a, $b) use ($array) {
            if ($array[$a] < $array[$b]) {
                return -1;
            } elseif ($array[$a] > $array[$b]) {
                return 1;
            } else {
                return strcmp($a, $b);
            }
        });

        if ($min == '-inf' && $max == '+inf') {
            return array_keys(array_slice(self::$data[$key], $options['limit'][0], $options['limit'][1], true));
        }

        $isInfMax = function($v) use ($max) {
            if (strpos($max, '(') !== false) {
                return $v < (int) substr($max, 1);
            } else {
                return $v <= (int) $max;
            }
        };

        $isSupMin = function($v) use ($min) {
            if (strpos($min, '(') !== false) {
                return $v > (int) substr($min, 1);
            } else {
                return $v >= (int) $min;
            }
        };

        $results = [];
        foreach (self::$data[$key] as $k => $v) {
            if ($min == '-inf' && $isInfMax($v)) {
                $results[] = $k;
            } elseif ($max == '+inf' && $isSupMin($v)) {
                $results[] = $k;
            } elseif ($isSupMin($v) && $isInfMax($v)) {
                $results[] = $k;
            } else {
                continue;
            }
        }

        return array_values(array_slice($results, $options['limit'][0], $options['limit'][1], true));
    }

    public function zrevrangebyscore($key, $max, $min, $options = null)
    {
        if (!isset(self::$data[$key]) || !is_array(self::$data[$key])) {
            return null;
        }

        if (!is_array($options) || !is_array($options['limit']) || count($options['limit']) != 2) {
            $options['limit'] = [0, count(self::$data[$key])];
        }

        $array = self::$data[$key];
        uksort(self::$data[$key], function($a, $b) use ($array) {
            if ($array[$a] > $array[$b]) {
                return -1;
            } elseif ($array[$a] < $array[$b]) {
                return 1;
            } else {
                return -strcmp($a, $b);
            }
        });

        if ($min == '-inf' && $max == '+inf') {
            return array_keys(array_slice(self::$data[$key], $options['limit'][0], $options['limit'][1], true));
        }

        $isInfMax = function($v) use ($max) {
            if (strpos($max, '(') !== false) {
                return $v < (int) substr($max, 1);
            } else {
                return $v <= (int) $max;
            }
        };

        $isSupMin = function($v) use ($min) {
            if (strpos($min, '(') !== false) {
                return $v > (int) substr($min, 1);
            } else {
                return $v >= (int) $min;
            }
        };

        $results = [];
        foreach (self::$data[$key] as $k => $v) {
            if ($min == '-inf' && $isInfMax($v)) {
                $results[] = $k;
            } elseif ($max == '+inf' && $isSupMin($v)) {
                $results[] = $k;
            } elseif ($isSupMin($v) && $isInfMax($v)) {
                $results[] = $k;
            } else {
                continue;
            }
        }

        return array_values(array_slice($results, $options['limit'][0], $options['limit'][1], true));
    }

    public function zadd($key, $score, $member) {
        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return null;
        }

        $isNew = !isset(self::$data[$key][$member]);

        self::$data[$key][$member] = (int) $score;

        return (int) $isNew;
    }

    public function zremrangebyscore($key, $min, $max) {
        $results = [];
        
        if ($toRem = $this->zrangebyscore($key, $min, $max)) {
            foreach ($toRem as $member) {
                if ($this->zrem($key, $member)) {
                    $results[] = $member;
                }
            }
        }

        return $results;
    }

    public function zrem($key, $member) {
        if (isset(self::$data[$key]) && !is_array(self::$data[$key]) || !isset(self::$data[$key][$member])) {
            return 0;
        }

        unset(self::$data[$key][$member]);

        return 1;
    }

    // Mock
    public function pipeline()
    {
        self::$pipeline = true;

        return $this;
    }

    public function execute()
    {
        self::$pipeline = false;

        return $this;
    }
}