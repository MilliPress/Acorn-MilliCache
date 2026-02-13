<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Controls whether the StoreResponse middleware is active and which
    | middleware groups it is appended to (typically 'web').
    |
    */

    'middleware' => [
        'enabled' => true,
        'groups' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Cache Clearing
    |--------------------------------------------------------------------------
    |
    | Maps Artisan commands to MilliCache flag patterns. When a listed
    | command runs, all cache entries matching its flag pattern are cleared.
    |
    | Use 'route*' to clear all Acorn route caches, a specific flag like
    | 'route:products:index' for a single route, or '*' to clear everything.
    |
    */

    'clear' => [
        'optimize:clear' => 'route*',
        'route:clear' => 'route*',
        'route:cache' => 'route*',
    ],

];
