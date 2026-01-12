<?php

return [
    'label' => 'Cart',
    'plural_label' => 'Carts',
    'breadcrumb' => [
        'view' => 'View Cart',
    ],
    'title' => 'Cart #:id',
    'guest' => 'Guest',

    'status' => [
        'active' => 'Active',
        'idle' => 'Idle',
        'inactive' => 'Inactive',
        'abandoned' => 'Abandoned',
        'completed' => 'Completed',
    ],

    'table' => [
        'id' => [
            'label' => 'ID',
        ],
        'status' => [
            'label' => 'Status',
        ],
        'customer' => [
            'label' => 'Customer',
        ],
        'email' => [
            'label' => 'Email',
        ],
        'items' => [
            'label' => 'Items',
        ],
        'quantity' => [
            'label' => 'Qty',
        ],
        'created_at' => [
            'label' => 'Created',
        ],
        'updated_at' => [
            'label' => 'Last Activity',
        ],
    ],

    'filter' => [
        'has_user' => [
            'label' => 'Customer Type',
            'all' => 'All',
            'registered' => 'Registered',
            'guest' => 'Guest',
        ],
    ],

    'infolist' => [
        'summary' => [
            'label' => 'Cart Summary',
        ],
        'id' => [
            'label' => 'Cart ID',
        ],
        'status' => [
            'label' => 'Status',
        ],
        'currency' => [
            'label' => 'Currency',
        ],
        'coupon' => [
            'label' => 'Coupon Code',
        ],
        'created_at' => [
            'label' => 'Created At',
        ],
        'updated_at' => [
            'label' => 'Last Activity',
        ],
        'customer' => [
            'label' => 'Customer',
        ],
        'customer_name' => [
            'label' => 'Name',
        ],
        'customer_email' => [
            'label' => 'Email',
        ],
        'customer_account' => [
            'label' => 'Customer Account',
        ],
        'addresses' => [
            'label' => 'Addresses',
        ],
        'address_type' => [
            'label' => 'Type',
        ],
        'address_name' => [
            'label' => 'Name',
        ],
        'address_line' => [
            'label' => 'Address',
        ],
        'address_city' => [
            'label' => 'City',
        ],
        'address_postcode' => [
            'label' => 'Postcode',
        ],
        'address_country' => [
            'label' => 'Country',
        ],
        'lines' => [
            'label' => 'Cart Items',
        ],
        'line_product' => [
            'label' => 'Product',
        ],
        'line_sku' => [
            'label' => 'SKU',
        ],
        'line_quantity' => [
            'label' => 'Qty',
        ],
        'line_meta' => [
            'label' => 'Options',
        ],
        'meta' => [
            'label' => 'Additional Info',
        ],
    ],

    'action' => [
        'view_order' => [
            'label' => 'View Order',
        ],
        'delete' => [
            'label' => 'Delete Cart',
        ],
    ],
];
