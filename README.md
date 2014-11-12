# Redis PHP Mock [![Build Status](https://secure.travis-ci.org/M6Web/RedisMock.png?branch=master)](http://travis-ci.org/M6Web/RedisMock)

PHP 5.3 library providing a Redis PHP mock for your tests.

## Installation

Add this line in your `composer.json` :

```json
{
    "require": {
        "m6web/redis-mock": "~2.0"
    }
}
```

Update your vendors :

```
$ composer update m6web/redis-mock
```

## Functions

It currently mocks these Redis commands :

Redis command                                    | Description
-------------------------------------------------|------------
**DEL** *key* *[key ...]*                        | Deletes one or more keys
**EXISTS** *key*                                 | Determines if a key exists
**EXPIRE** *key* *seconds*                       | Sets a key's time to live in seconds
**KEYS** *pattern*                               | Finds all keys matching the given pattern
**TTL** *key*                                    | Gets the time to live for a key
**TYPE** *key*                                   | Returns the string representation of the type of the value stored at key.
**GET** *key*                                    | Gets the value of a key
**INCR** *key*                                   | Increments the integer value of a key by one
**INCRBY** *key* *increment*                     | Increments the integer value of a key by `increment` value
**DECR** *key*                                   | Decrements the integer value of a key by one
**DECRBY** *key* *decrement*                     | Decrements the integer value of a key by `decrement` value
**SET** *key* *value*                            | Sets the string value of a key
**SETEX** *key* *seconds* *value*                | Sets the value and expiration of a key
**SETNX** *key* *value*                          | Sets key to hold value if key does not exist
**SADD** *key* *member* *[member ...]*           | Adds one or more members to a set
**SISMEMBER** *key* *member*                     | Determines if a member is in a set
**SMEMBERS** *key*                               | Gets all the members in a set
**SREM** *key* *member* *[member ...]*           | Removes one or more members from a set
**HDEL** *key* *field*                           | Delete one hash fields
**HEXISTS** *key* *field*                        | Determines if a hash field exists
**HMGET** *key* *array\<field\>*                 | Gets the values of multiple hash fields
**HGET** *key* *field*                           | Gets the value of a hash field
**HGETALL** *key*                                | Gets all the fields and values in a hash
**HMSET** *key* *array\<field, value\>*          | Sets each value in the corresponding field
**HSET** *key* *field* *value*                   | Sets the string value of a hash field
**LPUSH** *key* *value*                          | Pushs values at the head of a list
**LPOP** *key*                                   | Pops values at the head of a list
**LREM** *key* *count* *value*                   | Removes `count` instances of `value` from the head of a list
**LTRIM** *key* *start* *stop*                   | Removes the values of the `key` list which are outside the range `start`...`stop`
**LRANGE** *key* *start* *stop*                  | Gets a range of elements from a list
**MGET** *array\<field\>*                        | Gets the values of multiple keys
**MSET** *array\<field, value\>*                 | Sets the string values of multiple keys
**RPUSH** *key* *value*                          | Pushs values at the tail of a list
**RPOP** *key*                                   | Pops values at the tail of a list
**ZADD** *key* *score* *member*                  | Adds one member to a sorted set, or update its score if it already exists
**ZRANGE** *key* *start* *stop* *[withscores]*   | Returns the specified range of members in a sorted set
**ZRANGEBYSCORE** *key* *min* *max* *options*    | Returns a range of members in a sorted set, by score
**ZREM** *key* *member*                          | Removes one membner from a sorted set
**ZREMRANGEBYSCORE** *key* *min* *max*           | Removes all members in a sorted set within the given scores
**ZREVRANGE** *key* *start* *stop* *[withscores]*| Returns the specified range of members in a sorted set, with scores ordered from high to low
**ZREVRANGEBYSCORE** *key* *min* *max* *options* | Returns a range of members in a sorted set, by score, with scores ordered from high to low
**DBSIZE**                                       | Returns the number of keys in the selected database
**FLUSHDB**                                      | Flushes the database

It mocks **MULTI**, **DISCARD** and **EXEC** commands but without any transaction behaviors, they just make the interface fluent and return each command results.
**PIPELINE** and **EXECUTE** pseudo commands (client pipelining) are also mocked.  

## Usage

RedisMock library provides a factory able to build a mocked class of your Redis library that can be directly injected in your application :

```php
$factory          = new \M6Web\Component\RedisMock\RedisMockFactory();
$myRedisMockClass = $factory->getAdapterClass('My\Redis\Library');
$myRedisMock      = new $myRedisMockClass($myParameters);
```

In a simpler way, if you don't need to instanciate the mocked class with custom parameters (e.g. to easier inject the mock using Symfony config file), you can use `getAdapter` instead of `getAdapterClass` to directly create the adapter :

```php
$factory     = new \M6Web\Component\RedisMock\RedisMockFactory();
$myRedisMock = $factory->getAdapter('My\Redis\Library');
```

**WARNING !**

  * *RedisMock doesn't implement all Redis features and commands. The mock can have undesired behavior if your parent class uses unsupported features.*
  * *Storage is static and therefore shared by all instances.*

*Note : the factory will throw an exception by default if your parent class implements unsupported commands. If you want even so partially use the mock, you can specify the second parameter when you build it `$factory->getAdapter('My\Redis\Library', true)`. The exception will then thrown only when the command is called.*

## Tests

```shell
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar install
$ ./vendor/bin/atoum
```

## Credits

Developped by the [Cytron Team](http://cytron.fr/) of [M6 Web](http://tech.m6web.fr/).
Tested with [atoum](http://atoum.org).  

## License

RedisMock is licensed under the [MIT license](LICENSE).
