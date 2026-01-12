<?php

return [
    // Cart sidebar
    'title' => 'Winkelwagen',
    'close' => 'Winkelwagen sluiten',
    'browse_products' => 'Producten bekijken',
    'checkout_button' => 'Afrekenen',

    // Empty cart
    'empty' => [
        'title' => 'Uw winkelwagen is leeg',
        'description' => 'Begin met winkelen om artikelen aan uw winkelwagen toe te voegen.',
        'browse_products' => 'Producten bekijken',
    ],

    // Cart item
    'item' => [
        'remove' => 'Verwijderen',
        'sku' => 'SKU',
        'qty' => 'Aantal',
        'update_qty' => 'Aantal bijwerken',
    ],

    // Totals
    'totals' => [
        'subtotal' => 'Subtotaal',
        'tax' => 'BTW',
        'shipping' => 'Verzending',
        'total' => 'Totaal',
        'shipping_calculated' => 'Verzending wordt berekend bij afrekenen.',
        'free_shipping' => 'Gratis verzending',
    ],

    // Actions
    'actions' => [
        'checkout' => 'Afrekenen',
        'continue_shopping' => 'Verder winkelen',
        'update_cart' => 'Winkelwagen bijwerken',
        'clear_cart' => 'Winkelwagen legen',
    ],

    // Checkout
    'checkout' => [
        'title' => 'Afrekenen',
        'steps' => [
            'shipping' => 'Verzending',
            'billing' => 'Facturatie',
            'payment' => 'Betaling',
        ],
        'shipping_info' => 'Verzendgegevens',
        'billing_info' => 'Factuurgegevens',
        'same_as_shipping' => 'Hetzelfde als verzendadres',
        'continue_to_billing' => 'Verder naar facturatie',
        'continue_to_payment' => 'Verder naar betaling',
        'back' => 'Terug',
        'place_order' => 'Bestelling plaatsen',
        'processing' => 'Verwerken...',
        'order_summary' => 'Besteloverzicht',
    ],

    // Form fields
    'form' => [
        'first_name' => 'Voornaam',
        'last_name' => 'Achternaam',
        'email' => 'E-mail',
        'phone' => 'Telefoon',
        'company' => 'Bedrijf',
        'address_line_1' => 'Adresregel 1',
        'address_line_2' => 'Adresregel 2 (optioneel)',
        'city' => 'Plaats',
        'state' => 'Provincie',
        'postal_code' => 'Postcode',
        'country' => 'Land',
    ],

    // Payment
    'payment' => [
        'title' => 'Betaalmethode',
        'pay_on_delivery' => 'Betalen bij levering / Factuur',
        'pay_on_delivery_desc' => 'Betaal wanneer u uw bestelling ontvangt of via factuur',
        'ideal' => 'iDEAL',
        'credit_card' => 'Creditcard',
        'bank_transfer' => 'Bankoverschrijving',
    ],

    // Success
    'success' => [
        'title' => 'Bedankt voor uw bestelling!',
        'subtitle' => 'Uw bestelling is bevestigd en wordt binnenkort verwerkt.',
        'order_number' => 'Bestelnummer',
        'confirmation_email' => 'U ontvangt een bevestigingsmail op :email.',
        'order_details' => 'Bestelgegevens',
        'continue_shopping' => 'Verder winkelen',
        'shipping_address' => 'Verzendadres',
        'billing_address' => 'Factuuradres',
    ],

    // Notifications
    'notifications' => [
        'added' => 'Product toegevoegd aan winkelwagen',
        'removed' => 'Product verwijderd uit winkelwagen',
        'updated' => 'Winkelwagen bijgewerkt',
        'error' => 'Er is iets misgegaan',
    ],
];
