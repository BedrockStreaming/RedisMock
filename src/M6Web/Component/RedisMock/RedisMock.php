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
    protected $data      = array();
    protected $dataTypes = array();
    protected $pipeline  = false;

    public function reset()
    {
        $this->data = array();

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    // Type

    public function type($key)
    {
        if (array_key_exists($key, $this->dataTypes)) {
            return $this->dataTypes[$key];
        } else {
            return 'none';
        }
    }

    // Strings

    public function get($key)
    {
        if (!isset($this->data[$key]) || is_array($this->data[$key])) {
            return $this->pipeline ? $this : null;
        }

         return $this->pipeline ? $this : $this->data[$key];
    }

    public function set($key, $value)
    {
        $this->data[$key]      = $value;
        $this->dataTypes[$key] = 'string';

        return $this->pipeline ? $this : 'OK';
    }

    public function incr($key)
    {
        if (!isset($this->data[$key])) {
            $this->data[$key] = 1;
        } elseif (!is_integer($this->data[$key])) {
            return $this->pipeline ? $this : null;
        } else {
            $this->data[$key]++;
        }

        $this->dataTypes[$key] = 'string';

        return $this->pipeline ? $this : $this->data[$key];
    }

    // Keys

    public function exists($key)
    {
        return $this->pipeline ? $this : array_key_exists($key, $this->data);
    }

    public function del($key)
    {
        if (func_num_args() > 1) {
            throw new UnsupportedException('In RedisMock, `del` command can not remove more than one key at once.');
        }

        if (!isset($this->data[$key])) {
            return $this->pipeline ? $this : 0;
        }

        $deletedItems = count($this->data[$key]);

        unset($this->data[$key]);
        unset($this->dataTypes[$key]);

        return $this->pipeline ? $this : $deletedItems;
    }

    public function keys($pattern)
    {
        $pattern = preg_replace(array('#\*#', '#\?#', '#(\[[^\]]+\])#'), array('.*', '.', '$1+'), $pattern);

        $results = array();
        foreach ($this->data as $key => $value) {
            if (preg_match('#^' . $pattern . '$#', $key)) {
                $results[] = $key;
            }
        }

        return $this->pipeline ? $this : $results;
    }

    // Sets

    public function sadd($key, $member)
    {
        if (func_num_args() > 2) {
            throw new UnsupportedException('In RedisMock, `sadd` command can not set more than one member at once.');
        }

        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return $this->pipeline ? $this : null;
        }

        $isNew = !isset($this->data[$key]) || !in_array($member, $this->data[$key]);

        if ($isNew) {
            $this->data[$key][] = $member;
        }
        $this->dataTypes[$key] = 'set';

        return $this->pipeline ? $this : (int) $isNew;
    }

    public function smembers($key)
    {
        if (!isset($this->data[$key])) {
            return $this->pipeline ? $this : array();
        }

        return $this->pipeline ? $this : $this->data[$key];
    }

    public function srem($key, $member)
    {
        if (func_num_args() > 2) {
            throw new UnsupportedException('In RedisMock, `srem` command can not remove more than one member at once.');
        }

        if (!isset($this->data[$key]) || !in_array($member, $this->data[$key])) {
            return $this->pipeline ? $this : 0;
        }

        $this->data[$key] = array_diff($this->data[$key], array($member));

        if (0 === count($this->data[$key])) {
            unset($this->dataTypes[$key]);
        }

        return $this->pipeline ? $this : 1;
    }

    public function sismember($key, $member)
    {
        if (!isset($this->data[$key]) || !in_array($member, $this->data[$key])) {
            return $this->pipeline ? $this : 0;
        }

        return $this->pipeline ? $this : 1;
    }

    // Hashes

    public function hset($key, $field, $value)
    {
        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return $this->pipeline ? $this : null;
        }

        $isNew = !isset($this->data[$key]) || !isset($this->data[$key][$field]);

        $this->data[$key][$field] = $value;
        $this->dataTypes[$key]    = 'hash';

        return $this->pipeline ? $this : (int) $isNew;
    }

    public function hget($key, $field)
    {
        if (!isset($this->data[$key][$field]))
        {
            return $this->pipeline ? $this : null;
        }

        return $this->pipeline ? $this : $this->data[$key][$field];
    }

    public function hgetall($key)
    {
        if (!isset($this->data[$key]))
        {
            return $this->pipeline ? $this : array();
        }

        return $this->pipeline ? $this : $this->data[$key];
    }

    public function hexists($key, $field)
    {
        return $this->pipeline ? $this : (int) isset($this->data[$key][$field]);
    }

    // Sorted set

    public function zrange($key, $start, $stop, $withscores = false)
    {
        if ($withscores) {
            throw new UnsupportedException('Parameter `withscores` is not supported by RedisMock for `zrange` command.');
        }

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

        return $this->pipeline ? $this : array_slice($set, $start, $length);
    }

    public function zrevrange($key, $start, $stop, $withscores = false)
    {
        if ($withscores) {
            throw new UnsupportedException('Parameter `withscores` is not supported by RedisMock for `zrevrange` command.');
        }

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

        return $this->pipeline ? $this : array_slice($set, $start, $length);
    }

    public function zrangebyscore($key, $min, $max, array $options = array())
    {
        if (!empty($options['withscores'])) {
            throw new UnsupportedException('Parameter `withscores` is not supported by RedisMock for `zrangebyscore` command.');
        }

        if (!isset($this->data[$key]) || !is_array($this->data[$key])) {
            return $this->pipeline ? $this : null;
        }

        if (!isset($options['limit']) || !is_array($options['limit']) || count($options['limit']) != 2) {
            $options['limit'] = array(0, count($this->data[$key]));
        }

        $set = $this->data[$key];
        uksort($this->data[$key], function($a, $b) use ($set) {
            if ($set[$a] < $set[$b]) {
                return -1;
            } elseif ($set[$a] > $set[$b]) {
                return 1;
            } else {
                return strcmp($a, $b);
            }
        });

        if ($min == '-inf' && $max == '+inf') {
            return $this->pipeline ? $this : array_keys(array_slice($this->data[$key], $options['limit'][0], $options['limit'][1], true));
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

        $results = array();
        foreach ($this->data[$key] as $k => $v) {
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

        return $this->pipeline ? $this : array_values(array_slice($results, $options['limit'][0], $options['limit'][1], true));
    }

    public function zrevrangebyscore($key, $max, $min, array $options = array())
    {
        if (!empty($options['withscores'])) {
            throw new UnsupportedException('Parameter `withscores` is not supported by RedisMock for `zrevrangebyscore` command.');
        }

        if (!isset($this->data[$key]) || !is_array($this->data[$key])) {
            return $this->pipeline ? $this : null;
        }

        if (!isset($options['limit']) || !is_array($options['limit']) || count($options['limit']) != 2) {
            $options['limit'] = array(0, count($this->data[$key]));
        }

        $set = $this->data[$key];
        uksort($this->data[$key], function($a, $b) use ($set) {
            if ($set[$a] > $set[$b]) {
                return -1;
            } elseif ($set[$a] < $set[$b]) {
                return 1;
            } else {
                return -strcmp($a, $b);
            }
        });

        if ($min == '-inf' && $max == '+inf') {
            return $this->pipeline ? $this : array_keys(array_slice($this->data[$key], $options['limit'][0], $options['limit'][1], true));
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

        $results = array();
        foreach ($this->data[$key] as $k => $v) {
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

        return $this->pipeline ? $this : array_values(array_slice($results, $options['limit'][0], $options['limit'][1], true));
    }

    public function zadd($key, $score, $member) {
        if (func_num_args() > 3) {
            throw new UnsupportedException('In RedisMock, `zadd` command can not set more than one member at once.');
        }

        if (isset($this->data[$key]) && !is_array($this->data[$key])) {
            return $this->pipeline ? $this : null;
        }

        $isNew = !isset($this->data[$key][$member]);

        $this->data[$key][$member] = (int) $score;
        $this->dataTypes[$key]     = 'zset';

        return $this->pipeline ? $this : (int) $isNew;
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


        return $this->pipeline ? $this : $remNumber;
    }

    public function zrem($key, $member) {
        if (func_num_args() > 2) {
            throw new UnsupportedException('In RedisMock, `zrem` command can not remove more than one member at once.');
        }

        if (isset($this->data[$key]) && !is_array($this->data[$key]) || !isset($this->data[$key][$member])) {
            return $this->pipeline ? $this : 0;
        }

        unset($this->data[$key][$member]);

        if (0 === count($this->data[$key])) {
            unset($this->dataTypes[$key]);
        }

        return $this->pipeline ? $this : 1;
    }

    // Server

    public function flushdb()
    {
        $this->reset();

        return $this->pipeline ? $this : 'OK';
    }

    // Client pipeline

    public function pipeline()
    {
        $this->pipeline = true;

        return $this;
    }

    public function execute()
    {
        $this->pipeline = false;

        return $this;
    }
}