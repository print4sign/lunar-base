<?php

return [
    'label' => 'Supplier Product',
    'plural_label' => 'Supplier Products',

    'table' => [
        'external_id' => [
            'label' => 'External ID',
        ],
        'external_name' => [
            'label' => 'Product Name',
        ],
        'supplier' => [
            'label' => 'Supplier',
        ],
        'active' => [
            'label' => 'Active',
            'badge' => 'Active',
        ],
        'discontinued' => [
            'label' => 'Discontinued',
            'badge' => 'Discontinued',
        ],
        'has_replacement' => [
            'label' => 'Has Replacement',
            'badge' => 'Replaced',
        ],
        'approaching_discontinuation' => [
            'label' => 'Approaching Discontinuation',
        ],
        'active_to' => [
            'label' => 'Active Until',
        ],
        'replaced_by' => [
            'label' => 'Replaced By',
        ],
        'variants_count' => [
            'label' => 'Linked Variants',
        ],
        'last_synced_at' => [
            'label' => 'Last Synced',
        ],
        'actions' => [
            'sync' => [
                'label' => 'Sync',
            ],
        ],
    ],

    'infolist' => [
        'details' => [
            'title' => 'Product Details',
        ],
        'external_id' => [
            'label' => 'External ID',
        ],
        'external_name' => [
            'label' => 'Product Name',
        ],
        'supplier' => [
            'label' => 'Supplier',
        ],
        'lifecycle' => [
            'title' => 'Lifecycle Status',
        ],
        'active' => [
            'label' => 'Active',
        ],
        'active_to' => [
            'label' => 'Active Until',
        ],
        'replaced_by_external_id' => [
            'label' => 'Replaced By (External ID)',
        ],
        'replaced_by_name' => [
            'label' => 'Replaced By (Name)',
        ],
        'linked_variants' => [
            'title' => 'Linked Product Variants',
        ],
        'variant_sku' => [
            'label' => 'SKU',
        ],
        'product_name' => [
            'label' => 'Product',
        ],
        'purchasable' => [
            'label' => 'Purchasable',
        ],
        'sync_info' => [
            'title' => 'Sync Information',
        ],
        'synced' => [
            'label' => 'Synced',
        ],
        'last_synced_at' => [
            'label' => 'Last Synced At',
        ],
        'created_at' => [
            'label' => 'Created At',
        ],
        'updated_at' => [
            'label' => 'Updated At',
        ],
    ],

    'actions' => [
        'check_updates' => [
            'label' => 'Check for Updates',
            'success' => 'Product update check initiated.',
        ],
        'sync' => [
            'label' => 'Sync Product',
            'success' => 'Product synced successfully.',
        ],
        'view_replacement' => [
            'label' => 'View Replacement',
        ],
    ],
];
