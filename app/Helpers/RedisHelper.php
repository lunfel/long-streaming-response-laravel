<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Redis;

class RedisHelper
{
    /**
     * @param string $connectionName
     * @return resource
     */
    public static function redisConnectionToStreamContext(string $connectionName = 'default')
    {
        $connectionInfo = config(sprintf('database.redis.%s', $connectionName));


    }
}