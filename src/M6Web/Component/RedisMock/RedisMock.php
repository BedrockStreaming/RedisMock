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
    protected static $data      = array();
    protected static $dataTypes = array();
    protected static $dataTtl   = array();
    protected $pipeline         = false;
    protected $savedPipeline    = false;
    protected $pipedInfo        = array();

    public function reset()
    {
        self::$data = array();

        return $this;
    }

    public function getData()
    {
        return self::$data;
    }

    public function getDataTtl()
    {
        return self::$dataTtl;
    }

    public function getDataTypes()
    {
        return self::$dataTypes;
    }

    // Strings

    public function get($key)
    {
        if (!isset(self::$data[$key]) || is_array(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(null);
        }

        return $this->returnPipedInfo(self::$data[$key]);
    }

    public function set($key, $value, $seconds = null)
    {
        self::$data[$key]      = $value;
        self::$dataTypes[$key] = 'string';

        if (!is_null($seconds)) {
            self::$dataTtl[$key] = time() + $seconds;
        }

        return $this->returnPipedInfo('OK');
    }

    //mset/mget (built on set and get above)
    public function mset($pairs)
    {
        $this->stopPipeline();
        foreach ($pairs as $key => $value) {
            $this->set($key, $value);
        }
        $this->restorePipeline();

        return $this->returnPipedInfo('OK');
    }

    public function mget($fields)
    {
        $this->stopPipeline();
        foreach ($fields as $field) {
            $result[] = $this->get($field);
        }
        $this->restorePipeline();

        return $this->returnPipedInfo($result);
    }

    public function setex($key, $seconds, $value)
    {
        return $this->set($key, $value, $seconds);
    }

    public function setnx($key, $value)
    {
        if (!$this->get($key)) {
            $this->set($key, $value);
            return $this->returnPipedInfo(1);
        }
        return $this->returnPipedInfo(0);
    }

    public function ttl($key)
    {
        if (!array_key_exists($key, self::$data) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(-2);
        }

        if (!array_key_exists($key, self::$dataTtl)) {
            return $this->returnPipedInfo(-1);
        }

        return $this->returnPipedInfo(self::$dataTtl[$key] - time());
    }

    public function expire($key, $seconds)
    {
        if (!array_key_exists($key, self::$data) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(0);
        }

        self::$dataTtl[$key] = time() + $seconds;

        return $this->returnPipedInfo(1);
    }

    public function incr($key)
    {
        return $this->incrby($key, 1);
    }

    public function incrby($key, $increment)
    {
        $this->deleteOnTtlExpired($key);

        if (!isset(self::$data[$key])) {
            self::$data[$key] = (int) $increment;
        } elseif (!is_integer(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        } else {
            self::$data[$key] += (int) $increment;
        }

        self::$dataTypes[$key] = 'string';

        return $this->returnPipedInfo(self::$data[$key]);
    }

    public function decr($key)
    {
        return $this->decrby($key, 1);
    }

    public function decrby($key, $decrement)
    {
        $this->deleteOnTtlExpired($key);

        if (!isset(self::$data[$key])) {
            self::$data[$key] = 0;
        } elseif (!is_integer(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        }

        self::$data[$key] -= (int) $decrement;

        self::$dataTypes[$key] = 'string';

        return $this->returnPipedInfo(self::$data[$key]);
    }

    // Keys

    public function type($key)
    {
        if ($this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo('none');
        }

        if (array_key_exists($key, self::$dataTypes)) {
            return $this->returnPipedInfo(self::$dataTypes[$key]);
        } else {
            return $this->returnPipedInfo('none');
        }
    }

    public function exists($key)
    {
        if ($this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(false);
        }

        return $this->returnPipedInfo(array_key_exists($key, self::$data));
    }

    public function del($key)
    {
        if ( is_array($key) ) {
            $keys = $key;
        } else {
            $keys = func_get_args();
        }

        $deletedKeyCount = 0;
        foreach ( $keys as $k ) {
            if ( isset(self::$data[$k]) ) {
                $deletedKeyCount += count(self::$data[$k]);
                unset(self::$data[$k]);
                unset(self::$dataTypes[$k]);
                if (array_key_exists($k, self::$dataTtl)) {
                    unset(self::$dataTtl[$k]);
                }
            }
        }

        return $this->returnPipedInfo($deletedKeyCount);
    }

    public function keys($pattern)
    {
        $pattern = preg_replace(array('#\*#', '#\?#', '#(\[[^\]]+\])#'), array('.*', '.', '$1+'), $pattern);

        $results = array();
        foreach (self::$data as $key => $value) {
            if (preg_match('#^' . $pattern . '$#', $key) and !$this->deleteOnTtlExpired($key)) {
                $results[] = $key;
            }
        }

        return $this->returnPipedInfo($results);
    }

    // Sets

    public function sadd($key, $members)
    {
        // Check if members are passed as simple arguments
        // If so convert to an array
        if (func_num_args() > 2) {
            $arg_list = func_get_args();
            $members  = array_slice($arg_list, 1);
        }
        // convert single argument to array
        if ( !is_array($members) ) {
          $members = array($members);
        }

        $this->deleteOnTtlExpired($key);

        // Check if key is defined
        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        }

        if ( !isset(self::$data[$key]) ) {
          self::$data[$key] = array();
        }

        // Calculate new members
        $newMembers = array_diff($members, self::$data[$key]);

        // Insert new members (based on diff above, these should be unique)
        self::$data[$key] = array_merge(self::$data[$key], $newMembers);

        self::$dataTypes[$key] = 'set';

        if (array_key_exists($key, self::$dataTtl)) {
            unset(self::$dataTtl[$key]);
        }

        // return number of new members inserted
        return $this->returnPipedInfo(sizeof($newMembers));

    }

    public function smembers($key)
    {
        if (!isset(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(array());
        }

        return $this->returnPipedInfo(self::$data[$key]);
    }

    public function srem($key, $members)
    {
        // Check if members are passed as simple arguments
        // If so convert to an array
        if (func_num_args() > 2) {
            $arg_list = func_get_args();
            $members  = array_slice($arg_list, 1);
        }
        // convert single argument to array
        if ( !is_array($members) ) {
          $members = array($members);
        }

        if (!isset(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(0);
        }

        // Calcuale intersection to we know how many members were removed
        $remMembers = array_intersect($members, self::$data[$key]);
        // Remove members
        self::$data[$key] = array_diff(self::$data[$key], $members);

        // Unset key is set empty
        if (0 === count(self::$data[$key])) {
            unset(self::$dataTypes[$key]);
        }

        // return number of members removed
        return $this->returnPipedInfo(sizeof($remMembers));
    }

    public function sismember($key, $member)
    {
        if (!isset(self::$data[$key]) || !in_array($member, self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(0);
        }

        return $this->returnPipedInfo(1);
    }

    // Lists

    public function lrem($key, $count, $value)
    {
        if (!isset(self::$data[$key]) || !in_array($value, self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(0);
        }

        $arr      = self::$data[$key];
        $reversed = false;

        if ($count < 0) {
            $arr      = array_reverse($arr);
            $count    = abs($count);
            $reversed = true;
        } else if ($count == 0) {
            $count = count($arr);
        }

        $arr = array_filter($arr, function ($curValue) use (&$count, $value) {
            if ($count && ($curValue == $value)) {
                $count--;
                return false;
            }

            return true;
        });

        $deletedItems = count(self::$data[$key]) - count($arr);

        if ($reversed) {
            $arr = array_reverse($arr);
        }

        self::$data[$key] = array_values($arr);

        return $this->returnPipedInfo($deletedItems);
    }

    public function lpush($key, $value)
    {
        if ($this->deleteOnTtlExpired($key) || !isset(self::$data[$key])) {
            self::$data[$key] = array();
        }

        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        }

        array_unshift(self::$data[$key], $value);

        return $this->returnPipedInfo(count(self::$data[$key]));
    }

    public function rpush($key, $value)
    {
        if ($this->deleteOnTtlExpired($key) || !isset(self::$data[$key])) {
            self::$data[$key] = array();
        }

        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        }

        array_push(self::$data[$key], $value);

        return $this->returnPipedInfo(count(self::$data[$key]));
    }

    public function lpop($key)
    {
        if (!isset(self::$data[$key]) || !is_array(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(null);
        }

        return $this->returnPipedInfo(array_shift(self::$data[$key]));
    }

    public function rpop($key)
    {
        if (!isset(self::$data[$key]) || !is_array(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(null);
        }

        return $this->returnPipedInfo(array_pop(self::$data[$key]));
    }

    public function ltrim($key, $start, $stop)
    {
        $this->deleteOnTtlExpired($key);

        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        } elseif (!isset(self::$data[$key])) {
            return $this->returnPipedInfo('OK');
        }

        if ($start < 0) {
            if (abs($start) > count(self::$data[$key])) {
                $start = 0;
            } else {
                $start = count(self::$data[$key]) + $start;
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

        self::$data[$key] = array_slice(self::$data[$key], $start, $length);

        if (!count(self::$data[$key])) {
            $this->stopPipeline();
            $this->del($key);
            $this->restorePipeline();
        }

        return $this->returnPipedInfo('OK');
    }

    public function lrange($key, $start, $stop)
    {
        $this->deleteOnTtlExpired($key);

        if (!isset(self::$data[$key]) || !is_array(self::$data[$key])) {
            return $this->returnPipedInfo(array());
        }

        if ($start < 0) {
            if (abs($start) > count(self::$data[$key])) {
                $start = 0;
            } else {
                $start = count(self::$data[$key]) + $start;
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

        $data = array_slice(self::$data[$key], $start, $length);

        return $this->returnPipedInfo($data);
    }

    // Hashes

    public function hset($key, $field, $value)
    {
        $this->deleteOnTtlExpired($key);

        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        }

        $isNew = !isset(self::$data[$key]) || !isset(self::$data[$key][$field]);

        self::$data[$key][$field] = $value;
        self::$dataTypes[$key]    = 'hash';
        if (array_key_exists($key, self::$dataTtl)) {
            unset(self::$dataTtl[$key]);
        }

        return $this->returnPipedInfo((int) $isNew);
    }

    public function hmset($key, $pairs)
    {
        $this->deleteOnTtlExpired($key);

        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        }

        $this->stopPipeline();
        foreach ($pairs as $field => $value) {
            $this->hset($key, $field, $value);
        }
        $this->restorePipeline();

        return $this->returnPipedInfo('OK');
    }


    public function hget($key, $field)
    {
        if (!isset(self::$data[$key][$field]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(null);
        }

        return $this->returnPipedInfo(self::$data[$key][$field]);
    }

    public function hmget($key, $fields)
    {
        foreach ($fields as $field) {
            if (!isset(self::$data[$key][$field]) || $this->deleteOnTtlExpired($key)) {
                $result[$field] = null;
            } else {
                $result[$field] = self::$data[$key][$field];
            }
        }

        return $this->returnPipedInfo($result);
    }

    public function hdel($key, $field)
    {
        if (func_num_args() > 2) {
            throw new UnsupportedException('In RedisMock, `hdel` command can not delete more than one entry at once.');
        }

        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        }

        if (!array_key_exists($key, self::$data) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(0);
        }

        if (array_key_exists($field, self::$data[$key])) {
            unset(self::$data[$key][$field]);
            if (0 === count(self::$data[$key])) {
                unset(self::$dataTypes[$key]);
            }

            return $this->returnPipedInfo(1);
        } else {
            return $this->returnPipedInfo(0);
        }
    }

    public function hgetall($key)
    {
        if (!isset(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(array());
        }

        return $this->returnPipedInfo(self::$data[$key]);
    }

    public function hexists($key, $field)
    {
        $this->deleteOnTtlExpired($key);

        return $this->returnPipedInfo((int) isset(self::$data[$key][$field]));
    }

    // Sorted set

    public function zrange($key, $start, $stop, $withscores = false)
    {
        if (!isset(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(array());
        }

        $this->stopPipeline();
        $set = $this->zrangebyscore($key, '-inf', '+inf', array('withscores' => $withscores));
        $this->restorePipeline();

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

        return $this->returnPipedInfo(array_slice($set, $start, $length));
    }

    public function zrevrange($key, $start, $stop, $withscores = false)
    {
        if (!isset(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(array());
        }

        $this->stopPipeline();
        $set = $this->zrevrangebyscore($key, '+inf', '-inf', array('withscores' => $withscores));
        $this->restorePipeline();

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

        return $this->returnPipedInfo(array_slice($set, $start, $length));
    }

    protected function zrangebyscoreHelper($key, $min, $max, array $options = array(), $rev = false)
    {
        if (!isset(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(array());
        }

        if (!is_array(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        }

        if (!isset($options['limit']) || !is_array($options['limit']) || count($options['limit']) != 2) {
            $options['limit'] = array(0, count(self::$data[$key]));
        }

        $set = self::$data[$key];
        uksort(self::$data[$key], function($a, $b) use ($set, $rev) {
            if ($set[$a] > $set[$b]) {
                return $rev ? -1 : 1;
            } elseif ($set[$a] < $set[$b]) {
                return $rev ? 1 : -1;
            } else {
                return $rev ? -strcmp($a, $b) : strcmp($a, $b);
            }
        });

        if ($min == '-inf' && $max == '+inf') {
            $slice = array_slice(self::$data[$key], $options['limit'][0], $options['limit'][1], true);
            if (isset($options['withscores']) && $options['withscores']) {
                return $this->returnPipedInfo($slice);
            } else {
                return $this->returnPipedInfo(array_keys($slice));
            }
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
        foreach (self::$data[$key] as $k => $v) {
            if ($min == '-inf' && $isInfMax($v)) {
                $results[$k] = $v;
            } elseif ($max == '+inf' && $isSupMin($v)) {
                $results[$k] = $v;
            } elseif ($isSupMin($v) && $isInfMax($v)) {
                $results[$k] = $v;
            } else {
                continue;
            }
        }

        $slice = array_slice($results, $options['limit'][0], $options['limit'][1], true);
        if (isset($options['withscores']) && $options['withscores']) {
            return $this->returnPipedInfo($slice);
        } else {
            return $this->returnPipedInfo(array_keys($slice));
        }
    }



    public function zrangebyscore($key, $min, $max, array $options = array())
    {
        return $this->zrangebyscoreHelper($key, $min, $max, $options, false);
    }

    public function zrevrangebyscore($key, $max, $min, array $options = array())
    {
        return $this->zrangebyscoreHelper($key, $min, $max, $options, true);
    }


    public function zadd($key, $score, $member) {
        if (func_num_args() > 3) {
            throw new UnsupportedException('In RedisMock, `zadd` command can not set more than one member at once.');
        }

        $this->deleteOnTtlExpired($key);

        if (isset(self::$data[$key]) && !is_array(self::$data[$key])) {
            return $this->returnPipedInfo(null);
        }

        $isNew = !isset(self::$data[$key][$member]);

        self::$data[$key][$member] = (int) $score;
        self::$dataTypes[$key]     = 'zset';
        if (array_key_exists($key, self::$dataTtl))
        {
            unset(self::$dataTtl[$key]);
        }

        return $this->returnPipedInfo((int) $isNew);
    }

    public function zremrangebyscore($key, $min, $max) {
        if (!isset(self::$data[$key]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(0);
        }

        $remNumber = 0;

        $this->stopPipeline();

        if ($toRem = $this->zrangebyscore($key, $min, $max)) {
            foreach ($toRem as $member) {
                if ($this->zrem($key, $member)) {
                    $remNumber++;
                }
            }
        }

        $this->restorePipeline();

        return $this->returnPipedInfo($remNumber);
    }

    public function zrem($key, $member) {
        if (func_num_args() > 2) {
            throw new UnsupportedException('In RedisMock, `zrem` command can not remove more than one member at once.');
        }

        if (isset(self::$data[$key]) && !is_array(self::$data[$key]) || !isset(self::$data[$key][$member]) || $this->deleteOnTtlExpired($key)) {
            return $this->returnPipedInfo(0);
        }

        unset(self::$data[$key][$member]);

        if (0 === count(self::$data[$key])) {
            unset(self::$dataTypes[$key]);
        }

        return $this->returnPipedInfo(1);
    }

    // Server

    public function dbsize()
    {
        foreach ($this->getData() as $key => $value) {
            $this->deleteOnTtlExpired($key);
        }
        return $this->returnPipedInfo(count($this->getData()));
    }

    public function flushdb()
    {
        $this->reset();

        return $this->returnPipedInfo('OK');
    }

    // Transactions

    public function multi()
    {
        $this->pipeline  = true;
        $this->pipedInfo = array();

        return $this;
    }

    public function discard()
    {
        $this->pipeline  = false;
        $this->pipedInfo = array();

        return 'OK';
    }

    public function exec()
    {
        $pipedInfo = $this->pipedInfo;

        $this->discard();

        return $pipedInfo;
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

    // Protected methods

    protected function stopPipeline()
    {
        $this->savedPipeline = $this->pipeline;
        $this->pipeline      = false;
    }

    protected function restorePipeline()
    {
        $this->pipeline = $this->savedPipeline;
    }

    protected function returnPipedInfo($info)
    {
        if (!$this->pipeline) {
            return $info;
        }

        $this->pipedInfo[] = $info;

        return $this;
    }

    protected function deleteOnTtlExpired($key)
    {
        if (array_key_exists($key, self::$dataTtl) and (time() > self::$dataTtl[$key])) {
            // clean datas
            $this->stopPipeline();
            $this->del($key);
            $this->restorePipeline();

            return true;
        }

        return false;
    }
}
