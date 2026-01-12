<?php

return [
    'label' => 'Product Variant',
    'plural_label' => 'Product Variants',
    'pages' => [
        'edit' => [
            'title' => 'Basic Information',
        ],
        'media' => [
            'title' => 'Media',
            'form' => [
                'no_selection' => [
                    'label' => 'You do not currently have an image selected for this variant.',
                ],
                'no_media_available' => [
                    'label' => 'There is currently no media available on this product.',
                ],
                'images' => [
                    'label' => 'Primary Image',
                    'helper_text' => 'Select the product image which represents this variant.',
                ],
            ],
        ],
        'identifiers' => [
            'title' => 'Identifiers',
        ],
        'inventory' => [
            'title' => 'Inventory',
        ],
        'shipping' => [
            'title' => 'Shipping',
        ],
        'upload' => [
            'label' => 'Upload Requirements',
        ],
    ],
    'form' => [
        'sku' => [
            'label' => 'SKU',
        ],
        'gtin' => [
            'label' => 'Global Trade Item Number (GTIN)',
        ],
        'mpn' => [
            'label' => 'Manufacturer Part Number (MPN)',
        ],
        'ean' => [
            'label' => 'UPC/EAN',
        ],
        'stock' => [
            'label' => 'In Stock',
        ],
        'backorder' => [
            'label' => 'On Backorder',
        ],
        'purchasable' => [
            'label' => 'Purchasability',
            'options' => [
                'always' => 'Always',
                'in_stock' => 'In Stock',
                'in_stock_or_on_backorder' => 'In Stock or On Backorder',
            ],
        ],
        'unit_quantity' => [
            'label' => 'Unit Quantity',
            'helper_text' => 'How many individual items make up 1 unit.',
        ],
        'min_quantity' => [
            'label' => 'Minimum Quantity',
            'helper_text' => 'The minimum quantity of a product variant that can be bought in a single purchase.',
        ],
        'quantity_increment' => [
            'label' => 'Quantity Increment',
            'helper_text' => 'The product variant must be purchased in multiples of this quantity.',
        ],
        'tax_class_id' => [
            'label' => 'Tax Class',
        ],
        'shippable' => [
            'label' => 'Shippable',
        ],
        'length_value' => [
            'label' => 'Length',
        ],
        'length_unit' => [
            'label' => 'Length Unit',
        ],
        'width_value' => [
            'label' => 'Width',
        ],
        'width_unit' => [
            'label' => 'Width Unit',
        ],
        'height_value' => [
            'label' => 'Height',
        ],
        'height_unit' => [
            'label' => 'Height Unit',
        ],
        'weight_value' => [
            'label' => 'Weight',
        ],
        'weight_unit' => [
            'label' => 'Weight Unit',
        ],
    ],
    'fulfillment' => [
        'actions' => [
            'ai_match' => 'Match with AI',
            'link_supplier' => 'Link Supplier',
            'set_margin' => 'Set Margin',
            'enable_dynamic' => 'Enable Dynamic Pricing',
            'disable_dynamic' => 'Disable Dynamic Pricing',
            'configure' => 'Configure',
            'refresh_price' => 'Refresh Price',
            'unlink' => 'Unlink Supplier',
        ],
        'ai_match' => [
            'title' => 'AI Product Matching',
            'description' => 'Our AI will analyze this product variant and find the best matching supplier products based on specifications, dimensions, and attributes.',
            'filter_suppliers' => 'Filter by Suppliers',
            'filter_help' => 'Hold Ctrl/Cmd to select multiple suppliers, or leave empty to search all.',
            'analyzing' => 'Analyzing product and searching catalog...',
            'error_title' => 'Matching Failed',
            'error' => 'Failed to find matches: :message',
            'no_matches' => 'No matching supplier products found. Try adjusting your supplier filter or check if supplier products are available in the catalog.',
            'recommended' => 'Recommended',
            'concerns' => 'Concerns',
            'select' => 'Select',
            'refresh' => 'Refresh Matches',
            'linked_success' => 'Supplier product linked successfully!',
            'link_error' => 'Failed to link supplier product',
        ],
        'dynamic' => [
            'title' => 'Dynamic Pricing',
            'info' => 'Prices are automatically fetched from the supplier based on configuration and quantity.',
        ],
        'margin' => 'Margin',
        'no_margin_set' => 'No margin configured',
        'no_supplier' => 'No supplier linked',
        'supplier' => 'Supplier',
        'supplier_info' => 'The supplier that will fulfill this product',
        'supplier_product' => 'Supplier Product',
        'supplier_product_info' => 'The specific product from the supplier catalog',
        'supplier_product_id' => 'Product ID',
        'supplier_product_name' => 'Product Name',
    ],
];
