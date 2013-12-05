# Redis PHP Mock [![Build Status](https://secure.travis-ci.org/M6Web/RedisMock.png)](http://travis-ci.org/M6Web/RedisMock)

PHP 5.4+ library providing a Redis PHP mock for your tests.  

## Installation

Add this line in your `composer.json` :

```json
{
    "require": {
        "m6web/redis-mock": "dev-master"
    }
}
```

Update your vendors :

```
$ composer update m6web/redis-mock
```

## Functions

It currently mocks these Redis functions :

Redis function                                   | Description
-------------------------------------------------|------------
**SET** *key* *value*                            | Set the string value of a key
**GET** *key*                                    | Get the value of a key
**INCR** *key*                                   | Increment the integer value of a key by one
**DEL** *key*                                    | Delete a key
**KEYS** *pattern*                               | Find all keys matching the given pattern
**SADD** *key* *member*                          | Add one member to a set
**SMEMBERS** *key*                               | Get all the members in a set
**SREM** *key* *member*                          | Remove one member from a set
**HSET** *key* *field* *value*                   | Set the string value of a hash field
**HGET** *key* *field*                           | Get the value of a hash field
**HGETALL** *key*                                | Get all the fields and values in a hash
**HEXISTS** *key* *field*                        | Determine if a hash field exists
**ZRANGEBYSCORE** *key* *min* *max* *options*    | Return a range of members in a sorted set, by score
**ZREVRANGEBYSCORE** *key* *min* *max* *options* | Return a range of members in a sorted set, by score, with scores ordered from high to low
**ZADD** *key* *score* *member*                  | Add one member to a sorted set, or update its score if it already exists
**ZREMRANGEBYSCORE** *key* *min* *max*           | Remove all members in a sorted set within the given scores
**ZREM** *key* *member*                          | Remove one membner from a sorted set

It also mocks PIPELINE and EXECUTE functions but without any transaction behaviors, they just make the API fluent.

## Tests

```shell
$ php composer.phar install
$ ./vendor/bin/atoum
```

## Credits

Developped by the [Cytron Team](http://cytron.fr/) of [M6 Web](http://tech.m6web.fr/).  
Tested with [atoum](http://atoum.org).

## License

RedisMock is licensed under the [MIT license](LICENSE).
