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
    | Cacheable Status Codes
    |--------------------------------------------------------------------------
    |
    | Only responses with these HTTP status codes will be stored in the cache.
    |
    */

    'cacheable_status_codes' => [200],

];
