<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | Configure your Redis connection settings for MilliCache storage backend.
    | These settings are used by the Predis client to connect to your Redis
    | instance or compatible in-memory store (ValKey, Dragonfly, KeyDB).
    |
    */

    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD'),
        'database' => env('REDIS_CACHE_DB', 0),
        'timeout' => env('REDIS_TIMEOUT', 1),
        'read_timeout' => env('REDIS_READ_TIMEOUT', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Time-To-Live (TTL)
    |--------------------------------------------------------------------------
    |
    | Default cache duration in seconds. This determines how long cached
    | pages are stored before they expire and need to be regenerated.
    |
    | Default: 3600 (1 hour)
    |
    */

    'ttl' => env('MILLICACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Cache Grace Period
    |--------------------------------------------------------------------------
    |
    | Grace period in seconds. During this time, stale cache can be served
    | while fresh content is being regenerated in the background. This prevents
    | cache stampedes and ensures consistent performance.
    |
    | Set to 0 to disable. Default: 600 (10 minutes)
    |
    */

    'grace' => env('MILLICACHE_GRACE', 600),

    /*
    |--------------------------------------------------------------------------
    | Compression
    |--------------------------------------------------------------------------
    |
    | Enable gzip compression for cached content. This reduces storage size
    | and bandwidth but adds slight CPU overhead for compression/decompression.
    |
    | Requires PHP zlib extension.
    |
    */

    'compression' => env('MILLICACHE_COMPRESSION', true),

    /*
    |--------------------------------------------------------------------------
    | Advanced Cache Drop-in Path
    |--------------------------------------------------------------------------
    |
    | Path to the WordPress advanced-cache.php drop-in file. This is typically
    | in the wp-content directory. For Bedrock, this should be web/app/.
    |
    */

    'dropin_path' => env('MILLICACHE_DROPIN_PATH', base_path('web/app/advanced-cache.php')),

];
