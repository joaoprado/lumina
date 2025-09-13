<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | Supported: "roadrunner", "swoole", "frankenphp"
    |
    */

    'server' => env('OCTANE_SERVER', 'roadrunner'),

    /*
    |--------------------------------------------------------------------------
    | Hot Code Reloading
    |--------------------------------------------------------------------------
    */

    'watch' => [
        'app',
        'bootstrap',
        'config',
        'routes',
        'resources/views',
    ],

    'watch_directories_exclusions' => [
        'vendor',
        'node_modules',
        'storage',
        'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Execution Time
    |--------------------------------------------------------------------------
    */

    'max_execution_time' => 30,

    /*
    |--------------------------------------------------------------------------
    | Octane Listeners
    |--------------------------------------------------------------------------
    | No custom listeners by default. You can add listeners like clearing
    | caches or resetting state between requests if you adopt stateful singletons.
    */

    'listeners' => [
        // \Laravel\Octane\Events\RequestHandled::class => [
        //     // Listeners...
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Garbage Collection
    |--------------------------------------------------------------------------
    */

    'garbage' => [
        'schedule' => 50,
        'limit' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Workers
    |--------------------------------------------------------------------------
    */

    'workers' => [
        'count' => env('OCTANE_WORKER_COUNT', 1),
        'max_requests' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Swoole / RoadRunner Options
    |--------------------------------------------------------------------------
    */

    'swoole' => [
        'options' => [
            'package_max_length' => 10 * 1024 * 1024,
        ],
    ],

    'roadrunner' => [
        'rpc' => [
            'listen' => env('OCTANE_RR_RPC', 'tcp://127.0.0.1:6001'),
        ],
        'http' => [
            'address' => env('OCTANE_RR_HTTP', '0.0.0.0:8000'),
        ],
    ],

];
