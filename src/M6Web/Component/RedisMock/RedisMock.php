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
        protected $data = array();
        protected $dataTypes = array();
        protected $dataTtl = array();
        protected $pipeline = false;
        protected $savedPipeline = false;
        protected $pipedInfo = array();

        public function reset()
        {
            $this->data = array();

            return $this;
        }

        public function getData()
        {
            return $this->data;
        }

        public function getDataTtl()
        {
            return $this->dataTtl;
        }

        public function getDataTypes()
        {
            return $this->dataTypes;
        }

        // Strings

        public function get($key)
        {
            if (!isset($this->data[$key]) || is_array($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(null);
            }

            return $this->returnPipedInfo($this->data[$key]);
        }

        public function set($key, $value, $seconds = null)
        {
            $this->data[$key]      = $value;
            $this->dataTypes[$key] = 'string';

            if (!is_null($seconds)) {
                $this->dataTtl[$key] = time() + $seconds;
            }

            return $this->returnPipedInfo('OK');
        }

        public function setex($key, $seconds, $value)
        {
            return $this->set($key, $value, $seconds);
        }

        public function ttl($key)
        {
            if (!array_key_exists($key, $this->data) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(-2);
            }

            if (!array_key_exists($key, $this->dataTtl)) {
                return $this->returnPipedInfo(-1);
            }

            return $this->returnPipedInfo($this->dataTtl[$key] - time());
        }

        public function expire($key, $seconds)
        {
            if (!array_key_exists($key, $this->data) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(0);
            }

            $this->dataTtl[$key] = time() + $seconds;

            return $this->returnPipedInfo(1);
        }

        public function incr($key)
        {
            $this->deleteOnTtlExpired($key);

            if (!isset($this->data[$key])) {
                $this->data[$key] = 1;
            } elseif (!is_integer($this->data[$key])) {
                return $this->returnPipedInfo(null);
            } else {
                $this->data[$key]++;
            }

            $this->dataTypes[$key] = 'string';

            return $this->returnPipedInfo($this->data[$key]);
        }

        // Keys

        public function type($key)
        {
            if ($this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo('none');
            }

            if (array_key_exists($key, $this->dataTypes)) {
                return $this->returnPipedInfo($this->dataTypes[$key]);
            } else {
                return $this->returnPipedInfo('none');
            }
        }

        public function exists($key)
        {
            if ($this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(false);
            }

            return $this->returnPipedInfo(array_key_exists($key, $this->data));
        }

        public function del($key)
        {
            if (is_array($key)) {
                $keys = $key;
            } else {
                $keys = func_get_args();
            }

            $deletedKeyCount = 0;
            foreach ($keys as $k) {
                if (isset($this->data[$k])) {
                    $deletedKeyCount += count($this->data[$k]);
                    unset($this->data[$k]);
                    unset($this->dataTypes[$k]);
                    if (array_key_exists($k, $this->dataTtl)) {
                        unset($this->dataTtl[$k]);
                    }
                }
            }

            return $this->returnPipedInfo($deletedKeyCount);
        }

        public function keys($pattern)
        {
            $pattern = preg_replace(array('#\*#', '#\?#', '#(\[[^\]]+\])#'), array('.*', '.', '$1+'), $pattern);

            $results = array();
            foreach ($this->data as $key => $value) {
                if (preg_match('#^' . $pattern . '$#', $key) and !$this->deleteOnTtlExpired($key)) {
                    $results[] = $key;
                }
            }

            return $this->returnPipedInfo($results);
        }

        // Sets

        public function scard($key)
        {
            if (!isset($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return 0;
            }
            return count($this->data[$key]);
        }

        public function sadd($key, $member)
        {
            if (func_num_args() > 2) {
                throw new UnsupportedException('In RedisMock, `sadd` command can not set more than one member at once.');
            }

            $this->deleteOnTtlExpired($key);

            if (isset($this->data[$key]) && !is_array($this->data[$key])) {
                return $this->returnPipedInfo(null);
            }

            $isNew = !isset($this->data[$key]) || !in_array($member, $this->data[$key]);

            if ($isNew) {
                $this->data[$key][] = $member;
            }

            $this->dataTypes[$key] = 'set';

            if (array_key_exists($key, $this->dataTtl)) {
                unset($this->dataTtl[$key]);
            }

            return $this->returnPipedInfo((int)$isNew);
        }

        public function smembers($key)
        {
            if (!isset($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(array());
            }

            return $this->returnPipedInfo($this->data[$key]);
        }

        public function srem($key, $member)
        {
            if (func_num_args() > 2) {
                throw new UnsupportedException('In RedisMock, `srem` command can not remove more than one member at once.');
            }

            if (!isset($this->data[$key]) || !in_array($member, $this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(0);
            }

            $this->data[$key] = array_diff($this->data[$key], array($member));

            if (0 === count($this->data[$key])) {
                unset($this->dataTypes[$key]);
            }

            return $this->returnPipedInfo(1);
        }

        public function sismember($key, $member)
        {
            if (!isset($this->data[$key]) || !in_array($member, $this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(0);
            }

            return $this->returnPipedInfo(1);
        }

        // Lists

        public function lrem($key, $count, $value)
        {
            if (!isset($this->data[$key]) || !in_array($value, $this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(0);
            }

            $arr      = $this->data[$key];
            $reversed = false;

            if ($count < 0) {
                $arr      = array_reverse($arr);
                $count    = abs($count);
                $reversed = true;
            } else {
                if ($count == 0) {
                    $count = count($arr);
                }
            }

            $arr = array_filter(
              $arr,
              function ($curValue) use (&$count, $value) {
                  if ($count && ($curValue == $value)) {
                      $count--;
                      return false;
                  }

                  return true;
              }
            );

            $deletedItems = count($this->data[$key]) - count($arr);

            if ($reversed) {
                $arr = array_reverse($arr);
            }

            $this->data[$key] = array_values($arr);

            return $this->returnPipedInfo($deletedItems);
        }

        public function lpush($key, $value)
        {
            if ($this->deleteOnTtlExpired($key) || !isset($this->data[$key])) {
                $this->data[$key] = array();
            }

            if (isset($this->data[$key]) && !is_array($this->data[$key])) {
                return $this->returnPipedInfo(null);
            }

            array_unshift($this->data[$key], $value);

            return $this->returnPipedInfo(count($this->data[$key]));
        }

        public function rpush($key, $value)
        {
            if ($this->deleteOnTtlExpired($key) || !isset($this->data[$key])) {
                $this->data[$key] = array();
            }

            if (isset($this->data[$key]) && !is_array($this->data[$key])) {
                return $this->returnPipedInfo(null);
            }

            array_push($this->data[$key], $value);

            return $this->returnPipedInfo(count($this->data[$key]));
        }

        public function ltrim($key, $start, $stop)
        {
            $this->deleteOnTtlExpired($key);

            if (isset($this->data[$key]) && !is_array($this->data[$key])) {
                return $this->returnPipedInfo(null);
            } elseif (!isset($this->data[$key])) {
                return $this->returnPipedInfo('OK');
            }

            if ($start < 0) {
                if (abs($start) > count($this->data[$key])) {
                    $start = 0;
                } else {
                    $start = count($this->data[$key]) + $start;
                }
            }

            if ($stop >= 0) {
                $length = $stop - $start + 1;
            } else {
                if ($stop == -1) {
                    $length = null;
                } else {
                    $length = $stop + 1;
                }
            }

            $this->data[$key] = array_slice($this->data[$key], $start, $length);

            if (!count($this->data[$key])) {
                $this->stopPipeline();
                $this->del($key);
                $this->restorePipeline();
            }

            return $this->returnPipedInfo('OK');
        }

        // Hashes

        public function hset($key, $field, $value)
        {
            $this->deleteOnTtlExpired($key);

            if (isset($this->data[$key]) && !is_array($this->data[$key])) {
                return $this->returnPipedInfo(null);
            }

            $isNew = !isset($this->data[$key]) || !isset($this->data[$key][$field]);

            $this->data[$key][$field] = $value;
            $this->dataTypes[$key]    = 'hash';
            if (array_key_exists($key, $this->dataTtl)) {
                unset($this->dataTtl[$key]);
            }

            return $this->returnPipedInfo((int)$isNew);
        }

        public function hmset($key, $pairs)
        {
            $this->deleteOnTtlExpired($key);

            if (isset($this->data[$key]) && !is_array($this->data[$key])) {
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
            if (!isset($this->data[$key][$field]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(null);
            }

            return $this->returnPipedInfo($this->data[$key][$field]);
        }

        public function hdel($key, $field)
        {
            if (func_num_args() > 2) {
                throw new UnsupportedException('In RedisMock, `hdel` command can not delete more than one entry at once.');
            }

            if (isset($this->data[$key]) && !is_array($this->data[$key])) {
                return $this->returnPipedInfo(null);
            }

            if (!array_key_exists($key, $this->data) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(0);
            }

            if (array_key_exists($field, $this->data[$key])) {
                unset($this->data[$key][$field]);
                if (0 === count($this->data[$key])) {
                    unset($this->dataTypes[$key]);
                }

                return $this->returnPipedInfo(1);
            } else {
                return $this->returnPipedInfo(0);
            }
        }

        public function hgetall($key)
        {
            if (!isset($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(array());
            }

            return $this->returnPipedInfo($this->data[$key]);
        }

        public function hexists($key, $field)
        {
            $this->deleteOnTtlExpired($key);

            return $this->returnPipedInfo((int)isset($this->data[$key][$field]));
        }

        // Sorted set

        public function zcard($key)
        {
            if (!isset($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return 0;
            }
            return count($this->data[$key]);
        }

        public function zrange($key, $start, $stop, $withscores = false)
        {
            if ($withscores) {
                throw new UnsupportedException('Parameter `withscores` is not supported by RedisMock for `zrange` command.');
            }

            if (!isset($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(array());
            }

            $this->stopPipeline();
            $set = $this->zrangebyscore($key, '-inf', '+inf');
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
                    $length = null;
                } else {
                    $length = $stop + 1;
                }
            }

            return $this->returnPipedInfo(array_slice($set, $start, $length));
        }

        public function zrevrange($key, $start, $stop, $withscores = false)
        {
            if ($withscores) {
                throw new UnsupportedException('Parameter `withscores` is not supported by RedisMock for `zrevrange` command.');
            }

            if (!isset($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(array());
            }

            $this->stopPipeline();
            $set = $this->zrevrangebyscore($key, '+inf', '-inf');
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
                    $length = null;
                } else {
                    $length = $stop + 1;
                }
            }

            return $this->returnPipedInfo(array_slice($set, $start, $length));
        }

        public function zrangebyscore($key, $min, $max, array $options = array())
        {
            if (!empty($options['withscores'])) {
                throw new UnsupportedException('Parameter `withscores` is not supported by RedisMock for `zrangebyscore` command.');
            }

            if (!isset($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(array());
            }

            if (!is_array($this->data[$key])) {
                return $this->returnPipedInfo(null);
            }

            if (!isset($options['limit']) || !is_array($options['limit']) || count($options['limit']) != 2) {
                $options['limit'] = array(0, count($this->data[$key]));
            }

            $set = $this->data[$key];
            uksort(
              $this->data[$key],
              function ($a, $b) use ($set) {
                  if ($set[$a] < $set[$b]) {
                      return -1;
                  } elseif ($set[$a] > $set[$b]) {
                      return 1;
                  } else {
                      return strcmp($a, $b);
                  }
              }
            );

            if ($min == '-inf' && $max == '+inf') {
                return $this->returnPipedInfo(
                  array_keys(array_slice($this->data[$key], $options['limit'][0], $options['limit'][1], true))
                );
            }

            $isInfMax = function ($v) use ($max) {
                if (strpos($max, '(') !== false) {
                    return $v < (int)substr($max, 1);
                } else {
                    return $v <= (int)$max;
                }
            };

            $isSupMin = function ($v) use ($min) {
                if (strpos($min, '(') !== false) {
                    return $v > (int)substr($min, 1);
                } else {
                    return $v >= (int)$min;
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

            return $this->returnPipedInfo(
              array_values(array_slice($results, $options['limit'][0], $options['limit'][1], true))
            );
        }

        public function zrevrangebyscore($key, $max, $min, array $options = array())
        {
            if (!empty($options['withscores'])) {
                throw new UnsupportedException('Parameter `withscores` is not supported by RedisMock for `zrevrangebyscore` command.');
            }

            if (!isset($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
                return $this->returnPipedInfo(array());
            }

            if (!is_array($this->data[$key])) {
                return $this->returnPipedInfo(null);
            }

            if (!isset($options['limit']) || !is_array($options['limit']) || count($options['limit']) != 2) {
                $options['limit'] = array(0, count($this->data[$key]));
            }

            $set = $this->data[$key];
            uksort(
              $this->data[$key],
              function ($a, $b) use ($set) {
                  if ($set[$a] > $set[$b]) {
                      return -1;
                  } elseif ($set[$a] < $set[$b]) {
                      return 1;
                  } else {
                      return -strcmp($a, $b);
                  }
              }
            );

            if ($min == '-inf' && $max == '+inf') {
                return $this->returnPipedInfo(
                  array_keys(array_slice($this->data[$key], $options['limit'][0], $options['limit'][1], true))
                );
            }

            $isInfMax = function ($v) use ($max) {
                if (strpos($max, '(') !== false) {
                    return $v < (int)substr($max, 1);
                } else {
                    return $v <= (int)$max;
                }
            };

            $isSupMin = function ($v) use ($min) {
                if (strpos($min, '(') !== false) {
                    return $v > (int)substr($min, 1);
                } else {
                    return $v >= (int)$min;
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

            return $this->returnPipedInfo(
              array_values(array_slice($results, $options['limit'][0], $options['limit'][1], true))
            );
        }

        public function zadd($key, $score, $member)
        {
            if (func_num_args() > 3) {
                throw new UnsupportedException('In RedisMock, `zadd` command can not set more than one member at once.');
            }

            $this->deleteOnTtlExpired($key);

            if (isset($this->data[$key]) && !is_array($this->data[$key])) {
                return $this->returnPipedInfo(null);
            }

            $isNew = !isset($this->data[$key][$member]);

            $this->data[$key][$member] = (int)$score;
            $this->dataTypes[$key]     = 'zset';
            if (array_key_exists($key, $this->dataTtl)) {
                unset($this->dataTtl[$key]);
            }

            return $this->returnPipedInfo((int)$isNew);
        }

        public function zremrangebyscore($key, $min, $max)
        {
            if (!isset($this->data[$key]) || $this->deleteOnTtlExpired($key)) {
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

        public function zrem($key, $member)
        {
            if (func_num_args() > 2) {
                throw new UnsupportedException('In RedisMock, `zrem` command can not remove more than one member at once.');
            }

            if (isset($this->data[$key]) && !is_array(
                $this->data[$key]
              ) || !isset($this->data[$key][$member]) || $this->deleteOnTtlExpired($key)
            ) {
                return $this->returnPipedInfo(0);
            }

            unset($this->data[$key][$member]);

            if (0 === count($this->data[$key])) {
                unset($this->dataTypes[$key]);
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
            if (array_key_exists($key, $this->dataTtl) and (time() > $this->dataTtl[$key])) {
                // clean datas
                $this->stopPipeline();
                $this->del($key);
                $this->restorePipeline();

                return true;
            }

            return false;
        }
    }