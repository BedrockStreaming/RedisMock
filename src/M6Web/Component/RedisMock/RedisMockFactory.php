<?php

namespace M6Web\Component\RedisMock;

/**
 * Adapter allowing to setup a Redis Mock inheriting of an arbitrary class
 * 
 * WARNING ! RedisMock doesn't implement all Redis features and commands.
 * The mock can have undesired behavior if your parent class uses unsupported features.
 * 
 * @author Adrien Samson <asamson.externe@m6.fr>
 */
class RedisMockFactory
{
    protected $redisCommands = array(
        'append',
        'auth',
        'bgrewriteaof',
        'bgsave',
        'bitcount',
        'bitop',
        'blpop',
        'brpop',
        'brpoplpush',
        'client',
        'config',
        'dbsize',
        'debug',
        'decr',
        'decrby',
        'del',
        'discard',
        'dump',
        'echo',
        'eval',
        'evalsha',
        'exec',
        'exists',
        'expire',
        'expireat',
        'flushall',
        'flushdb',
        'get',
        'getbit',
        'getrange',
        'getset',
        'hdel',
        'hexists',
        'hget',
        'hgetall',
        'hincrby',
        'hincrbyfloat',
        'hkeys',
        'hlen',
        'hmget',
        'hmset',
        'hset',
        'hsetnx',
        'hvals',
        'incr',
        'incrby',
        'incrbyfloat',
        'info',
        'keys',
        'lastsave',
        'lindex',
        'linsert',
        'llen',
        'lpop',
        'lpush',
        'lpushx',
        'lrange',
        'lrem',
        'lset',
        'ltrim',
        'mget',
        'migrate',
        'monitor',
        'move',
        'mset',
        'msetnx',
        'multi',
        'object',
        'persist',
        'pexpire',
        'pexpireat',
        'ping',
        'psetex',
        'psubscribe',
        'pubsub',
        'pttl',
        'publish',
        'punsubscribe',
        'quit',
        'randomkey',
        'rename',
        'renamenx',
        'restore',
        'rpop',
        'rpoplpush',
        'rpush',
        'rpushx',
        'sadd',
        'save',
        'scard',
        'script',
        'sdiff',
        'sdiffstore',
        'select',
        'set',
        'setbit',
        'setex',
        'setnx',
        'setrange',
        'shutdown',
        'sinter',
        'sinterstore',
        'sismember',
        'slaveof',
        'slowlog',
        'smembers',
        'smove',
        'sort',
        'spop',
        'srandmember',
        'srem',
        'strlen',
        'subscribe',
        'sunion',
        'sunionstore',
        'sync',
        'time',
        'ttl',
        'type',
        'unsubscribe',
        'unwatch',
        'watch',
        'zadd',
        'zcard',
        'zcount',
        'zincrby',
        'zinterstore',
        'zrange',
        'zrangebyscore',
        'zrank',
        'zrem',
        'zremrangebyrank',
        'zremrangebyscore',
        'zrevrange',
        'zrevrangebyscore',
        'zrevrank',
        'zscore',
        'zunionstore',
        'scan',
        'sscan',
        'hscan',
        'zscan',
    );

    protected $classTemplate = <<<'CLASS'
namespace {{namespace}};
class {{class}} extends \{{baseClass}}
{
    protected $mock;
    public function __construct($mock)
    {
        $this->mock = $mock;
    }

    public function __call($method, $args)
    {
        $methodName = strtolower($method);

        if (!method_exists('M6Web\Component\RedisMock\RedisMock', $methodName)) {
            throw new \M6Web\Component\RedisMock\UnsupportedException(sprintf('Redis command `%s` is not supported by RedisMock.', $methodName));
        }

        return call_user_func_array(array($this->mock, $methodName), $args);
    }
{{methods}}
}
CLASS;

    protected $methodTemplate = <<<'METHOD'

    public function {{method}}({{signature}})
    {
        return $this->mock->{{method}}({{args}});
    }

METHOD;


    public function getAdapter($classToExtend, $redisMock)
    {
        $newClassName = sprintf('RedisMock_%s_Adapter', str_replace('\\', '_', $classToExtend));
        $namespace = __NAMESPACE__;
        $class = $namespace . '\\'. $newClassName;

        if (class_exists($namespace . '\\'. $newClassName)) {
            return new $class($redisMock);
        }

        $classCode = $this->getClassCode($namespace, $newClassName, new \ReflectionClass($classToExtend));

        eval($classCode);

        return new $class($redisMock);
    }

    protected function getClassCode($namespace, $newClassName, \ReflectionClass $class)
    {
        $methodsCode = '';

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = strtolower($method->getName());

            if (!method_exists('M6Web\Component\RedisMock\RedisMock', $methodName) && in_array($methodName, $this->redisCommands)) {
                throw new \M6Web\Component\RedisMock\UnsupportedException(sprintf('Redis command `%s` is not supported by RedisMock.', $methodName));
            } elseif (method_exists('M6Web\Component\RedisMock\RedisMock', $methodName)) {
                $methodsCode .= strtr($this->methodTemplate, array(
                    '{{method}}'    => $methodName,
                    '{{signature}}' => $this->getMethodSignature($method),
                    '{{args}}'      => $this->getMethodArgs($method),
                ));
            }
        }

        return strtr($this->classTemplate, array(
            '{{namespace}}' => $namespace,
            '{{class}}'     => $newClassName,
            '{{baseClass}}' => $class->getName(),
            '{{methods}}'   => $methodsCode,
        ));
    }

    protected function getMethodSignature(\ReflectionMethod $method)
    {
        $signatures = array();
        foreach ($method->getParameters() as $parameter) {
            $signature = '';
            // typeHint
            if ($parameter->isArray()) {
                $signature .= 'array ';
            } elseif (method_exists($parameter, 'isCallable') && $parameter->isCallable()) {
                $signature .= 'callable ';
            } elseif ($parameter->getClass()) {
                $signature .= sprintf('\%s ', $parameter->getClass());
            }
            // reference
            if ($parameter->isPassedByReference()) {
                $signature .= '&';
            }
            // paramName
            $signature .= '$' . $parameter->getName();
            // defaultValue
            if ($parameter->isDefaultValueAvailable()) {
                $signature .= ' = ';
                if ($parameter->isDefaultValueConstant()) {
                    $signature .= $parameter->getDefaultValueConstantName();
                } else {
                    $signature .= var_export($parameter->getDefaultValue(), true);
                }
            }

            $signatures[] = $signature;
        }

        return implode(', ', $signatures);
    }

    protected function getMethodArgs(\ReflectionMethod $method)
        {
            $args = array();
            foreach ($method->getParameters() as $parameter) {
                $args[] = '$' . $parameter->getName();
            }

            return implode(', ', $args);
        }
}