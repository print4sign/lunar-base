<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Supplier Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default supplier driver that will be used when
    | a supplier doesn't specify a driver.
    |
    */

    'default' => env('LUNAR_DEFAULT_SUPPLIER', 'internal'),

    /*
    |--------------------------------------------------------------------------
    | Supplier Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the supplier drivers for your application.
    | Each driver should specify a class that implements the
    | SupplierDriverInterface.
    |
    */

    'drivers' => [
        'internal' => [
            'class' => \Lunar\Drivers\Suppliers\InternalDriver::class,
        ],
        'probo' => [
            'class' => \Lunar\Drivers\Suppliers\ProboDriver::class,
            'api_key' => env('PROBO_API_KEY'),
            'sandbox' => env('PROBO_SANDBOX', false),
        ],
        'helloprint' => [
            'class' => \Lunar\Drivers\Suppliers\HelloPrintDriver::class,
            'api_key' => env('HELLOPRINT_API_KEY'),
            'sandbox' => env('HELLOPRINT_SANDBOX', false),
        ],
        'printcom' => [
            'class' => \Lunar\Drivers\Suppliers\PrintComDriver::class,
            'api_key' => env('PRINTCOM_API_KEY'),
            'sandbox' => env('PRINTCOM_SANDBOX', false),
        ],
        'offline' => [
            'class' => \Lunar\Drivers\Suppliers\OfflineDriver::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing Configuration
    |--------------------------------------------------------------------------
    |
    | These options control how orders are routed to suppliers when multiple
    | suppliers are available for the same product.
    |
    */

    'routing' => [
        'default_strategy' => 'cheapest', // cheapest, fastest, preferred
        'cache_prices' => true,
        'cache_ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Matching Configuration
    |--------------------------------------------------------------------------
    |
    | These options control how supplier products are matched to canonical
    | products using AI or rule-based matching.
    |
    */

    'matching' => [
        'auto_confirm_threshold' => 0.95,
        'min_confidence' => 0.5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    |
    | These options control how supplier catalogs are synchronized.
    |
    */

    'sync' => [
        'schedule' => 'daily',
        'queue' => 'suppliers',
    ],

];
