<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automatic Review Request
    |--------------------------------------------------------------------------
    |
    | Configure automatic email requests for customer reviews after delivery.
    |
    */
    'auto_request' => [
        'enabled' => env('LUNAR_INSPIRATION_AUTO_REQUEST', true),
        'days_after_delivery' => env('LUNAR_INSPIRATION_DAYS_AFTER_DELIVERY', 14),
        'reminder_after_days' => env('LUNAR_INSPIRATION_REMINDER_DAYS', 7),
        'token_expiry_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Requirements
    |--------------------------------------------------------------------------
    |
    | Configure the minimum requirements for uploaded inspiration images.
    |
    */
    'images' => [
        'min_width' => 800,
        'min_height' => 600,
        'max_size_kb' => 10240, // 10MB
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Moderation
    |--------------------------------------------------------------------------
    |
    | Configure moderation settings for inspirations.
    |
    */
    'moderation' => [
        'auto_approve' => false,
        'notify_admin' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Display Settings
    |--------------------------------------------------------------------------
    |
    | Configure how inspirations are displayed on the frontend.
    |
    */
    'display' => [
        'per_page' => 12,
        'show_on_product_page' => true,
        'max_on_product_page' => 6,
    ],
];
