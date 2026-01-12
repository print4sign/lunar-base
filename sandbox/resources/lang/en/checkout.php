<?php

return [
    // Progress steps
    'steps' => [
        'order' => 'Order',
        'finalize' => 'Finalize',
        'confirmation' => 'Confirmation',
    ],

    // Cart contents section
    'cart' => [
        'title' => 'Cart contents',
        'hide_composition' => 'Hide composition',
        'show_composition' => 'Show composition',
        'product_reference' => 'Product reference (optional)',
        'start_upload' => 'Start upload',
        'files_uploaded' => 'Files uploaded',
        'change_files' => 'Change',
        'deliver_later_selected' => 'Deliver later (+€15)',
        'delivery_specs' => 'Delivery specifications',
        'upload_coming_soon' => 'File upload coming soon',
        'online_designer' => 'Online Designer',
        'design_ready' => 'Design ready',
    ],

    // Shipping address section
    'address' => [
        'title' => 'Delivery address',
        'company' => 'Company name',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'street' => 'Street',
        'house_number' => 'House number',
        'postal_code' => 'Postal code',
        'city' => 'City',
        'country' => 'Country',
        'phone' => 'Phone number',
        'email' => 'Email address',
        'use_default' => 'Use default delivery address',
        'enter_new' => 'Enter new address',
        'choose_from_book' => 'Choose from address book',
        'save_address' => 'Save this address',
        'login_prompt' => 'Log in to use your saved addresses',
        'login' => 'Log in',
        'guest_checkout' => 'Continue as guest',
    ],

    // Delivery options
    'delivery' => [
        'title' => 'Delivery',
        'new_delivery' => 'New delivery',
        'combine' => 'Combine',
        'no_deliveries' => 'No deliveries',
        'from' => 'From',
    ],

    // Delivery date section
    'date' => [
        'title' => 'Delivery date',
        'no_rush_fee' => 'No rush fee',
        'rush_fee' => 'Rush fee',
        'show_more' => 'Show more dates',
        'show_less' => 'Show fewer dates',
    ],

    // Shipping method section
    'shipping' => [
        'title' => 'Shipping method',
        'delivery' => 'Delivery',
        'pickup' => 'Pickup',
        'most_reliable' => 'Most reliable',
        'excl_packaging' => 'Excl. :price packaging',
        'incl_packaging' => 'Incl. packaging',
        'time_window' => ':from - :to',
        'select_method' => 'Make your choice',
    ],

    // Pickup locations
    'pickup' => [
        'dokkum' => 'Dokkum',
        'tilburg' => 'Tilburg',
        'aalsmeer' => 'Aalsmeer',
        'apeldoorn' => 'Apeldoorn',
        'bornem' => 'Bornem (BE)',
    ],

    // Summary sidebar
    'summary' => [
        'title' => 'Summary',
        'subtotal' => 'Subtotal',
        'items' => ':count item|:count items',
        'packaging' => 'Packaging costs',
        'shipping' => 'Shipping costs',
        'total' => 'Total',
        'total_incl_tax' => 'Total incl. VAT',
        'total_excl_tax' => 'Total excl. VAT',
        'continue' => 'Continue to order',
        'clear_cart' => 'Clear cart',
        'save_for_later' => 'Save for later',
        'upload_required' => 'Upload your files to continue.',
        'upload_files' => 'Upload your files to continue.',
        'select_shipping' => 'Select a shipping method',
        'select_date' => 'Select a delivery date',
        'enter_address' => 'Enter a delivery address',
    ],

    // Carriers
    'carriers' => [
        'onb' => 'ONB',
        'onb_standard' => 'ONB',
        'onb_express' => 'ONB Express',
        'onb_saturday' => 'ONB Saturday',
        'onb_volume' => 'ONB Volume',
        'dhl' => 'DHL',
        'dhl_standard' => 'DHL',
        'dhl_express' => 'DHL Express',
        'dhl_saturday' => 'DHL Saturday',
        'dhl_letterbox' => 'DHL Letterbox',
        'dhl_for_you' => 'DHL (residential)',
        'dhl_for_you_evening' => 'DHL evening delivery (residential)',
        'postnl' => 'PostNL',
        'postnl_standard' => 'PostNL',
        'postnl_express' => 'PostNL Express',
        'postnl_international' => 'PostNL International',
        'ups' => 'UPS',
        'ups_standard' => 'UPS',
    ],

    // Validation messages
    'validation' => [
        'address_required' => 'Please enter a delivery address',
        'date_required' => 'Please select a delivery date',
        'shipping_required' => 'Please select a shipping method',
    ],

    // Notifications
    'notifications' => [
        'quantity_updated' => 'Quantity updated',
        'item_removed' => 'Item removed',
        'item_duplicated' => 'Item duplicated',
        'cart_cleared' => 'Cart cleared',
        'address_saved' => 'Address saved',
        'uploads_confirmed' => 'Files confirmed successfully',
        'error' => 'Something went wrong',
    ],

    // Save for later
    'save_for_later' => [
        'title' => 'Save for later',
        'description' => 'Save your cart to order at a later time.',
        'reference' => 'Reference',
        'placeholder' => 'Give your cart a name',
        'save' => 'Save',
        'success' => 'Cart saved',
        'empty_cart' => 'Your cart is empty',
        'login_required' => 'Log in to save your cart',
        'saved_title' => 'Your cart has been saved!',
        'saved_description' => 'You can find your cart in the overview and order at a later time.',
        'to_overview' => 'Go to overview',
        'to_home' => 'Go to homepage',
    ],
];
