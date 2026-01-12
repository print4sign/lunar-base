<?php

return [
    'site' => [
        'label' => 'Scrape Site',
        'label_plural' => 'Scrape Sites',
    ],
    'session' => [
        'label' => 'Scrape Session',
        'label_plural' => 'Scrape Sessions',
    ],
    'status' => [
        'pending' => 'Pending',
        'running' => 'Running',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
    ],
    'type' => [
        'full' => 'Full Scrape',
        'categories' => 'Categories Only',
        'products' => 'Products Only',
        'pages' => 'Pages Only',
        'incremental' => 'Incremental',
    ],
    'form' => [
        'name' => 'Name',
        'handle' => 'Handle',
        'base_url' => 'Base URL',
        'enabled' => 'Enabled',
        'auth_type' => 'Authentication Type',
        'auth_config' => 'Authentication Configuration',
        'category_selectors' => 'Category Selectors',
        'product_selectors' => 'Product Selectors',
        'page_selectors' => 'Page Selectors',
        'delay_between_requests' => 'Delay Between Requests',
        'max_concurrent_pages' => 'Max Concurrent Pages',
        'timeout' => 'Timeout',
        'options' => 'Options',
    ],
    'auth_types' => [
        'none' => 'No Authentication',
        'form' => 'Form Login',
        'basic' => 'Basic Auth',
        'cookie' => 'Cookie Auth',
    ],
    'actions' => [
        'start_scrape' => 'Start Scrape',
        'view_sessions' => 'View Sessions',
        'view_results' => 'View Results',
        'export_json' => 'Export JSON',
        'cancel' => 'Cancel',
    ],
    'messages' => [
        'scrape_started' => 'Scrape job has been queued.',
        'scrape_cancelled' => 'Scrape session has been cancelled.',
        'export_complete' => 'JSON export complete.',
        'no_sites' => 'No scrape sites configured.',
    ],
    'sections' => [
        'basic_info' => 'Basic Information',
        'authentication' => 'Authentication',
        'selectors' => 'Selectors',
        'rate_limiting' => 'Rate Limiting',
        'progress' => 'Progress',
        'results' => 'Results',
        'errors' => 'Errors',
    ],
];
