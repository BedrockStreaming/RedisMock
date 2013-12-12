<?php

namespace M6Web\Component\RedisMock;

/**
 * trait including all the business logic of Redis Mock
 */
trait RedisMockTrait
{
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
            return self::$pipeline ? $this : null;
        }

         return self::$pipeline ? $this : self::$data[$key];
    }

    public function set($key, $value)
    {
        self::$data[$key] = $value;

        return self::$pipeline ? $this : 'OK';
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

    public function exists($key)
    {
        return self::$pipeline ? $this : array_key_exists($key, self::$data);
    }

    public function del($key)
    {
        if (!isset(self::$data[$key]))
        {
            return self::$pipeline ? $this : 0;
        }

        $deletedItems = count(self::$data[$key]);

        unset(self::$data[$key]);

        return self::$pipeline ? $this : $deletedItems;
    }

    public function keys($pattern)
    {
        $pattern = preg_replace(['#\*#', '#\?#', '#(\[[^\]]+\])#'], ['.*', '.', '$1+'], $pattern);

        $results = [];
        foreach (self::$data as $key => $value) {
            if (preg_match('#^' . $pattern . '$#', $key)) {
                $results[] = $key;
            }
        }

        return self::$pipeline ? $this : $results;
    }

    // Sets

    public function sadd($key, $member)
    {
        $isNew = !isset(self::$data[$key]);

        self::$data[$key][] = $member;

        return self::$pipeline ? $this : $isNew;
    }

    public function smembers($key)
    {
        if (!isset(self::$data[$key]))
        {
            return self::$pipeline ? $this : array();
        }

        return self::$pipeline ? $this : self::$data[$key];
    }

    public function srem($key, $member)
    {
        if (!isset(self::$data[$key]) || !in_array($member, self::$data[$key]))
        {
            return self::$pipeline ? $this : 0;
        }

        self::$data[$key] = array_diff(self::$data[$key], [$member]);

        return self::$pipeline ? $this : 1;
    }

    public function sismember($key, $member)
    {
        if (!isset(self::$data[$key]) || !in_array($member, self::$data[$key]))
        {
            return self::$pipeline ? $this : 0;
        }

        return self::$pipeline ? $this : 1;
    }

    // Hashes

    public function hset($key, $field, $value)
    {
        $isNew = !isset(self::$data[$key][$field]);

        self::$data[$key][$field] = $value;

        return self::$pipeline ? $this : (int) $isNew;
    }

    public function hget($key, $field)
    {
        if (!isset(self::$data[$key][$field]))
        {
            return self::$pipeline ? $this : null;
        }

        return self::$pipeline ? $this : self::$data[$key][$field];
    }

    public function hgetall($key)
    {
        if (!isset(self::$data[$key]))
        {
            return self::$pipeline ? $this : array();
        }

        return self::$pipeline ? $this : self::$data[$key];
    }

    public function hexists($key, $field)
    {
        return self::$pipeline ? $this : (int) isset(self::$data[$key][$field]);
    }

    // Sorted set

    public function zrange($key, $start, $stop)
    {
        $set = $this->zrangebyscore($key, '-inf', '+inf');

        if ($start < 0) {
            if (abs($start) > count($set)) {
                $start = 0;
            } else {
                $start = count($set) + $start;
            }
        }

        if ($stop >= 0) {
            $length = $stop - $start + 1;
        } else {
            if ($stop == -1) {
                $length = NULL;
            } else {
                $length = $stop + 1;
            }
        }

        return self::$pipeline ? $this : array_slice($set, $start, $length);
    }

    public function zrevrange($key, $start, $stop)
    {
        $set = $this->zrevrangebyscore($key, '+inf', '-inf');

        if ($start < 0){
            if (abs($start) > count($set)) {
                $start = 0;
            } else {
                $start = count($set) + $start;
            }
        }

        if ($stop >= 0) {
            $length = $stop - $start + 1;
        } else {
            if ($stop == -1) {
                $length = NULL;
            } else {
                $length = $stop + 1;
            }
        }

        return self::$pipeline ? $this : array_slice($set, $start, $length);
    }

    public function zrangebyscore($key, $min, $max, $options = null)
    {
        if (!isset(self::$data[$key]) || !is_array(self::$data[$key])) {
            return self::$pipeline ? $this : null;
        }

        if (!is_array($options) || !is_array($options['limit']) || count($options['limit']) != 2) {
            $options['limit'] = [0, count(self::$data[$key])];
        }

        $set = self::$data[$key];
        uksort(self::$data[$key], function($a, $b) use ($set) {
            if ($set[$a] < $set[$b]) {
                return -1;
            } elseif ($set[$a] > $set[$b]) {
                return 1;
            } else {
                return strcmp($a, $b);
            }
        });

        if ($min == '-inf' && $max == '+inf') {
            return self::$pipeline ? $this : array_keys(array_slice(self::$data[$key], $options['limit'][0], $options['limit'][1], true));
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

        return self::$pipeline ? $this : array_values(array_slice($results, $options['limit'][0], $options['limit'][1], true));
    }

    public function zrevrangebyscore($key, $max, $min, $options = null)
    {
        if (!isset(self::$data[$key]) || !is_array(self::$data[$key])) {
            return self::$pipeline ? $this : null;
        }

        if (!is_array($options) || !is_array($options['limit']) || count($options['limit']) != 2) {
            $options['limit'] = [0, count(self::$data[$key])];
        }

        $set = self::$data[$key];
        uksort(self::$data[$key], function($a, $b) use ($set) {
            if ($set[$a] > $set[$b]) {
                return -1;
            } elseif ($set[$a] < $set[$b]) {
                return 1;
            } else {
                return -strcmp($a, $b);
            }
        });

        if ($min == '-inf' && $max == '+inf') {
            return self::$pipeline ? $this : array_keys(array_slice(self::$data[$key], $options['limit'][0], $options['limit'][1], true));
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

        return self::$pipeline ? $this : array_values(array_slice($results, $options['limit'][0], $options['limit'][1], true));
    }

    public function zadd($key, $score, $member) {
        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return self::$pipeline ? $this : null;
        }

        $isNew = !isset(self::$data[$key][$member]);

        self::$data[$key][$member] = (int) $score;

        return self::$pipeline ? $this : (int) $isNew;
    }

    public function zremrangebyscore($key, $min, $max) {
        $remNumber = 0;

        if ($toRem = $this->zrangebyscore($key, $min, $max)) {
            foreach ($toRem as $member) {
                if ($this->zrem($key, $member)) {
                    $remNumber++;
                }
            }
        }

        return self::$pipeline ? $this : $remNumber;
    }

    public function zrem($key, $member) {
        if (isset(self::$data[$key]) && !is_array(self::$data[$key]) || !isset(self::$data[$key][$member])) {
            return self::$pipeline ? $this : 0;
        }

        unset(self::$data[$key][$member]);

        return self::$pipeline ? $this : 1;
    }

    // Server

    public function flushdb()
    {
        $this->reset();

        return self::$pipeline ? $this : 'OK';
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