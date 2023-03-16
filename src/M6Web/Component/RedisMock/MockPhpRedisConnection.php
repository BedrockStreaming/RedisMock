<?php

namespace M6Web\Component\RedisMock;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Redis;

class MockPhpRedisConnection extends PhpRedisConnection
{
    /**
     * Execute commands in a pipeline.
     *
     * @param callable|null $callback
     *
     * @return Redis|array
     */
    public function pipeline(callable $callback = null): array|Redis
    {
        $pipeline = $this->client()->pipeline();

        return is_null($callback)
            ? $pipeline
            : tap($pipeline, $callback)->exec();
    }
}
