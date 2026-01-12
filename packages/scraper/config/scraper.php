<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where scraped data will be stored as JSON files.
    |
    */
    'storage' => [
        'disk' => env('SCRAPER_STORAGE_DISK', 'local'),
        'path' => env('SCRAPER_STORAGE_PATH', 'scraper'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Node.js Scripts Path
    |--------------------------------------------------------------------------
    |
    | The path to the Playwright scripts directory.
    |
    */
    'scripts_path' => env('SCRAPER_SCRIPTS_PATH', __DIR__.'/../scripts'),

    /*
    |--------------------------------------------------------------------------
    | Default Scrape Settings
    |--------------------------------------------------------------------------
    |
    | Default settings applied to all scrape operations unless overridden.
    |
    */
    'defaults' => [
        'timeout' => 30000,
        'delay_between_requests' => 1000,
        'max_concurrent_pages' => 3,
        'headless' => true,
        'viewport' => [
            'width' => 1920,
            'height' => 1080,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which queue connection and queue name to use for scraping jobs.
    |
    */
    'queue' => [
        'connection' => env('SCRAPER_QUEUE_CONNECTION', null),
        'queue' => env('SCRAPER_QUEUE_NAME', 'scraper'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Selectors
    |--------------------------------------------------------------------------
    |
    | Default CSS selectors used for extracting data from pages.
    | These can be overridden per-site in the admin panel.
    |
    */
    'selectors' => [
        'category' => [
            'list' => '.category-list a, nav.categories a, .nav-categories a',
            'name' => 'h1, .category-title, .collection-title',
            'description' => '.category-description, .collection-description',
            'image' => '.category-image img, .collection-image img',
        ],
        'product' => [
            'list' => '.product-list .product, .products-grid .product-item, .product-card',
            'link' => 'a.product-link, a[href*="/product"], a[href*="/products/"]',
            'name' => 'h1.product-title, .product-name, [data-product-title]',
            'price' => '.price, .product-price, [data-price]',
            'sale_price' => '.sale-price, .product-sale-price, [data-sale-price]',
            'description' => '.product-description, #description, [data-product-description]',
            'short_description' => '.short-description, .product-excerpt',
            'sku' => '.sku, [data-sku], .product-sku',
            'images' => '.product-images img, .gallery img, [data-product-images] img',
            'attributes' => '.product-attributes tr, .specifications li, .product-meta li',
            'variants' => '.product-variants option, .variant-options input',
            'availability' => '.availability, .stock-status, [data-availability]',
        ],
        'page' => [
            'title' => 'title, h1',
            'content' => 'main, article, .content, #content',
            'meta_description' => 'meta[name="description"]',
            'meta_keywords' => 'meta[name="keywords"]',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Selectors
    |--------------------------------------------------------------------------
    |
    | Default selectors for form-based authentication.
    |
    */
    'auth' => [
        'selectors' => [
            'username' => 'input[name="email"], input[name="username"], #email, #username',
            'password' => 'input[name="password"], input[type="password"], #password',
            'submit' => 'button[type="submit"], input[type="submit"], .login-button',
            'success_indicator' => '.dashboard, .account, .logged-in',
            'error_indicator' => '.error, .alert-danger, .login-error',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Probo.nl Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials and settings for scraping Probo.nl
    |
    */
    'probo' => [
        'username' => env('PROBO_USERNAME'),
        'password' => env('PROBO_PASSWORD'),
        'base_url' => 'https://www.probo.nl',
        'login_url' => 'https://www.probo.nl/customer/account/login',
        'default_category' => 'https://www.probo.nl/quapro-flag',
        'selectors' => [
            'username' => '#email',
            'password' => '#pass',
            'submit' => '#send2',
            'success_indicator' => '.customer-welcome',
            'product_name' => '.page-title span, h1.product-name',
            'product_price' => '.price-wrapper .price, .product-info-price .price',
            'product_sku' => '.product.attribute.sku .value',
            'product_images' => '.gallery-placeholder img, .fotorama__img',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Content Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for AI-powered content rewriting and image generation.
    |
    */
    'claude_api_key' => env('ANTHROPIC_API_KEY'),

    'folio_pages_path' => env(
        'SCRAPER_FOLIO_PAGES_PATH',
        null // Defaults to resource_path('views/pages/products') in VoltPageGenerator
    ),

    /*
    |--------------------------------------------------------------------------
    | Image Download Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for downloading and storing product images locally.
    |
    */
    'images' => [
        'disk' => env('SCRAPER_IMAGES_DISK', 'public'),
        'directory' => env('SCRAPER_IMAGES_DIRECTORY', 'scraped-products'),
    ],

    'image_generation' => [
        'enabled' => env('SCRAPER_IMAGE_GENERATION', false),
        'gemini_api_key' => env('GEMINI_API_KEY'),
        'banana_api_key' => env('BANANA_API_KEY'),
        'banana_model_key' => env('BANANA_MODEL_KEY'),
    ],
];
