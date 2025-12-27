<?php

return [
    'label' => 'Supplier',
    'plural_label' => 'Suppliers',
    'form' => [
        'name' => [
            'label' => 'Name',
        ],
        'handle' => [
            'label' => 'Handle',
        ],
        'driver' => [
            'label' => 'Driver',
            'helper_text' => 'Select the driver to use for this supplier integration.',
        ],
        'enabled' => [
            'label' => 'Enabled',
            'helper_text' => 'Enable or disable this supplier.',
        ],
        'priority' => [
            'label' => 'Priority',
            'helper_text' => 'Higher priority suppliers are preferred when routing orders.',
        ],
        'credentials' => [
            'label' => 'Credentials',
            'helper_text' => 'API credentials for this supplier. These are stored encrypted.',
            'key_label' => 'Key',
            'value_label' => 'Value',
            'add_label' => 'Add Credential',
        ],
        'capabilities' => [
            'label' => 'Capabilities',
            'helper_text' => 'Select which capabilities this supplier supports.',
            'options' => [
                'catalog_sync' => 'Catalog Sync',
                'pricing' => 'Dynamic Pricing',
                'ordering' => 'Order Placement',
                'file_upload' => 'File Upload',
                'tracking' => 'Order Tracking',
            ],
        ],
    ],
    'table' => [
        'name' => [
            'label' => 'Name',
        ],
        'handle' => [
            'label' => 'Handle',
        ],
        'driver' => [
            'label' => 'Driver',
        ],
        'priority' => [
            'label' => 'Priority',
        ],
        'enabled' => [
            'label' => 'Enabled',
            'badge' => 'Enabled',
        ],
        'disabled' => [
            'badge' => 'Disabled',
        ],
        'products_count' => [
            'label' => 'Products',
        ],
        'actions' => [
            'sync' => [
                'label' => 'Sync Catalog',
            ],
        ],
    ],
    'configurator' => [
        'helloprint' => [
            'title' => 'HelloPrint Configurator',
            'description' => 'Configure your HelloPrint product options and specifications.',
            'coming_soon' => 'HelloPrint configurator is coming soon. This feature is currently under development.',
        ],
    ],
];
