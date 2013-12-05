# Redis PHP Mock [![Build Status](https://secure.travis-ci.org/M6Web/RedisMock.png)](http://travis-ci.org/M6Web/RedisMock)

This PHP 5.4+ library provides a Redis PHP mock for your tests.  

## Installation

Add this line in your `composer.json` :

```json
{
    "require": {
        "m6web/redis-php-mock": "dev-master"
    }
}
```

Update your vendors :

```
$ composer update m6web/redis-php-mock
```

## Running the tests

```shell
$ php composer.phar install --dev
$ ./vendor/bin/atoum -d tests -bf tests/bootstrap.php
```

## Credits

Developped by the [Cytron Team](http://cytron.fr/) of [M6 Web](http://tech.m6web.fr/).  
Tested with [atoum](http://atoum.org).

## License

RedisPhpMock is licensed under the [MIT license](LICENSE).
