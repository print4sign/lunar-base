<?php

return [
    // Cart sidebar
    'title' => 'Shopping Cart',
    'close' => 'Close cart',
    'browse_products' => 'Browse Products',
    'checkout_button' => 'Checkout',

    // Empty cart
    'empty' => [
        'title' => 'Your cart is empty',
        'description' => 'Start shopping to add items to your cart.',
        'browse_products' => 'Browse Products',
    ],

    // Cart item
    'item' => [
        'remove' => 'Remove',
        'sku' => 'SKU',
        'qty' => 'Qty',
        'update_qty' => 'Update quantity',
    ],

    // Totals
    'totals' => [
        'subtotal' => 'Subtotal',
        'tax' => 'Tax',
        'shipping' => 'Shipping',
        'total' => 'Total',
        'shipping_calculated' => 'Shipping calculated at checkout.',
        'free_shipping' => 'Free shipping',
    ],

    // Actions
    'actions' => [
        'checkout' => 'Checkout',
        'continue_shopping' => 'Continue Shopping',
        'update_cart' => 'Update cart',
        'clear_cart' => 'Clear cart',
    ],

    // Checkout
    'checkout' => [
        'title' => 'Checkout',
        'steps' => [
            'shipping' => 'Shipping',
            'billing' => 'Billing',
            'payment' => 'Payment',
        ],
        'shipping_info' => 'Shipping Information',
        'billing_info' => 'Billing Information',
        'same_as_shipping' => 'Same as shipping address',
        'continue_to_billing' => 'Continue to Billing',
        'continue_to_payment' => 'Continue to Payment',
        'back' => 'Back',
        'place_order' => 'Place Order',
        'processing' => 'Processing...',
        'order_summary' => 'Order Summary',
    ],

    // Form fields
    'form' => [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'company' => 'Company',
        'address_line_1' => 'Address Line 1',
        'address_line_2' => 'Address Line 2 (optional)',
        'city' => 'City',
        'state' => 'State/Province',
        'postal_code' => 'Postal Code',
        'country' => 'Country',
    ],

    // Payment
    'payment' => [
        'title' => 'Payment Method',
        'pay_on_delivery' => 'Pay on Delivery / Invoice',
        'pay_on_delivery_desc' => 'Pay when you receive your order or by invoice',
        'ideal' => 'iDEAL',
        'credit_card' => 'Credit Card',
        'bank_transfer' => 'Bank Transfer',
    ],

    // Success
    'success' => [
        'title' => 'Thank you for your order!',
        'subtitle' => 'Your order has been confirmed and will be processed shortly.',
        'order_number' => 'Order number',
        'confirmation_email' => 'You will receive a confirmation email at :email.',
        'order_details' => 'Order Details',
        'continue_shopping' => 'Continue Shopping',
        'shipping_address' => 'Shipping Address',
        'billing_address' => 'Billing Address',
    ],

    // Notifications
    'notifications' => [
        'added' => 'Product added to cart',
        'removed' => 'Product removed from cart',
        'updated' => 'Cart updated',
        'error' => 'Something went wrong',
    ],
];
