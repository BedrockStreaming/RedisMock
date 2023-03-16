<?php

namespace M6Web\Component\RedisMock;

use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connectors\PhpRedisConnector;
use Illuminate\Support\Arr;

class MockPhpRedisConnector extends PhpRedisConnector
{
    /**
     * Create a new clustered PhpRedis connection.
     *
     * @param array $config
     * @param array $options
     *
     * @return PhpRedisConnector
     */
    public function connect(array $config, array $options)
    {
        $formattedOptions = array_merge(
            ['timeout' => 10.0], $options, Arr::pull($config, 'options', [])
        );


        $factory = new RedisMockFactory();
        $redisMockClass = $factory->getAdapter('Redis', true);

        return new MockPhpRedisConnector(new $redisMockClass($config, $formattedOptions));
    }

    /**
     * Create a new clustered PhpRedis connection.
     *
     * @param array $config
     * @param array $clusterOptions
     * @param array $options
     *
     * @return PhpRedisClusterConnection
     */
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $clusterSpecificOptions = Arr::pull($config, 'options', []);

        $factory = new RedisMockFactory();
        $redisMockClass = $factory->getAdapter('Redis', true);

        return new MockPhpRedisConnector(new $redisMockClass(array_values($config), array_merge(
            $options, $clusterOptions, $clusterSpecificOptions
        )));
    }

}
