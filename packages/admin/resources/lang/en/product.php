<?php

return [

    'label' => 'Product',

    'plural_label' => 'Products',

    'tabs' => [
        'all' => 'All',
        'published' => 'Published',
        'draft' => 'Draft',
    ],

    'status' => [
        'unpublished' => [
            'content' => 'Currently in draft status, this product is hidden across all channels and customer groups.',
        ],
        'availability' => [
            'customer_groups' => 'This product is currently unavailable for all customer groups.',
            'channels' => 'This product is currently unavailable for all channels.',
        ],
    ],

    'table' => [
        'status' => [
            'label' => 'Status',
            'states' => [
                'deleted' => 'Deleted',
                'draft' => 'Draft',
                'published' => 'Published',
            ],
        ],
        'name' => [
            'label' => 'Name',
        ],
        'brand' => [
            'label' => 'Brand',
        ],
        'sku' => [
            'label' => 'SKU',
        ],
        'stock' => [
            'label' => 'Stock',
        ],
        'producttype' => [
            'label' => 'Product Type',
        ],
    ],

    'actions' => [
        'create' => [
            'label' => 'Create Product',
        ],
        'create_manual' => [
            'label' => 'Create Manual Product',
        ],
        'create_from_supplier' => [
            'label' => 'Create from Supplier Product',
        ],
        'add_supplier_variant' => [
            'label' => 'Add Supplier Variant',
            'sku_helper' => 'Leave empty to use the supplier product ID as SKU.',
            'sku_placeholder' => 'Auto-generated from supplier',
            'success' => 'Supplier variant added successfully.',
        ],
        'edit_status' => [
            'label' => 'Update Status',
            'heading' => 'Update Status',
        ],
    ],

    'form' => [
        'name' => [
            'label' => 'Name',
        ],
        'brand' => [
            'label' => 'Brand',
        ],
        'sku' => [
            'label' => 'SKU',
        ],
        'producttype' => [
            'label' => 'Product Type',
        ],
        'supplier_id' => [
            'label' => 'Supplier',
        ],
        'supplier_product_id' => [
            'label' => 'Supplier Product',
        ],
        'status' => [
            'label' => 'Status',
            'options' => [
                'published' => [
                    'label' => 'Published',
                    'description' => 'This product will be available across all enabled customer groups and channels',
                ],
                'draft' => [
                    'label' => 'Draft',
                    'description' => 'This product will be hidden across all channels and customer groups',
                ],
            ],
        ],
        'tags' => [
            'label' => 'Tags',
            'helper_text' => 'Separate tags by pressing Enter, Tab or comma (,)',
        ],
        'collections' => [
            'label' => 'Collections',
            'select_collection' => 'Select a collection',
        ],
    ],

    'pages' => [
        'availability' => [
            'label' => 'Availability',
        ],
        'edit' => [
            'title' => 'Basic Information',
        ],
        'identifiers' => [
            'label' => 'Product Identifiers',
        ],
        'inventory' => [
            'label' => 'Inventory',
        ],
        'pricing' => [
            'form' => [
                'tax_class_id' => [
                    'label' => 'Tax Class',
                ],
                'tax_ref' => [
                    'label' => 'Tax Reference',
                    'helper_text' => 'Optional, for integration with 3rd party systems.',
                ],
            ],
        ],
        'shipping' => [
            'label' => 'Shipping',
        ],
        'upload' => [
            'label' => 'Upload Requirements',
        ],
        'fulfillment' => [
            'label' => 'Fulfillment',
        ],
        'variants' => [
            'label' => 'Variants',
        ],
        'collections' => [
            'label' => 'Collections',
            'select_collection' => 'Select a collection',
        ],
        'associations' => [
            'label' => 'Product Associations',
        ],
    ],

    'configurator' => [
        'title' => 'Product Configurator',
        'loading' => 'Loading configurator...',
        'select_option' => 'Select an option',
        'quantity' => 'Quantity',
        'dimensions' => 'Dimensions',
        'size_presets' => 'Quick sizes',
        'custom_size' => 'Custom size',
        'next_step' => 'Next step',
        'pricing' => 'Pricing',
        'cost_price' => 'Cost Price',
        'sell_price' => 'Sell Price',
        'breakdown' => 'Price Breakdown',
        'shipping_options' => 'Shipping Options',
        'complete_config_for_price' => 'Complete the configuration to see pricing.',
        'current_selections' => 'Current Selections',
        'selections_made' => 'options selected',
        'actions' => [
            'reset' => 'Reset',
            'create_variant' => 'Create Variant',
            'link_variant' => 'Save Configuration',
            'unlink' => 'Unlink',
            'configure_probo' => 'Configure',
            'configure' => 'Configure',
            'edit_configuration' => 'Edit Configuration',
        ],
        'notifications' => [
            'incomplete' => 'Please complete all required options.',
            'select_variant' => 'Please select a variant to link.',
            'variant_exists' => 'A variant with this configuration already exists.',
            'variant_created' => 'Variant created successfully.',
            'variant_linked' => 'Configuration saved successfully.',
            'variant_not_found' => 'Selected variant not found.',
            'no_configuration' => 'This variant has no configuration to unlink.',
            'unlinked' => 'Configuration unlinked successfully.',
            'error' => 'An error occurred.',
        ],
        'modal' => [
            'title' => 'Product Configurator',
            'title_with_product' => 'Product Configurator: :product',
            'select_supplier' => 'Select Supplier',
            'select_product' => 'Select Supplier Product',
            'width' => 'Width',
            'height' => 'Height',
            'quantity' => 'Quantity',
            'sku_helper' => 'Leave empty to auto-generate from dimensions',
            'unsupported_driver' => 'No configurator available for supplier driver ":driver".',
        ],
        'cross_sell' => [
            'no_accessories' => 'No accessories',
            'amount' => 'Amount',
            'continue' => 'Continue',
        ],
    ],

];
