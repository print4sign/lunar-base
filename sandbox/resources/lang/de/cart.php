<?php

return [
    // Cart sidebar
    'title' => 'Warenkorb',
    'close' => 'Warenkorb schließen',
    'browse_products' => 'Produkte durchsuchen',
    'checkout_button' => 'Zur Kasse',

    // Empty cart
    'empty' => [
        'title' => 'Ihr Warenkorb ist leer',
        'description' => 'Beginnen Sie mit dem Einkaufen, um Artikel hinzuzufügen.',
        'browse_products' => 'Produkte durchsuchen',
    ],

    // Cart item
    'item' => [
        'remove' => 'Entfernen',
        'sku' => 'Artikelnr.',
        'qty' => 'Menge',
        'update_qty' => 'Menge aktualisieren',
    ],

    // Totals
    'totals' => [
        'subtotal' => 'Zwischensumme',
        'tax' => 'MwSt.',
        'shipping' => 'Versand',
        'total' => 'Gesamt',
        'shipping_calculated' => 'Versand wird an der Kasse berechnet.',
        'free_shipping' => 'Kostenloser Versand',
    ],

    // Actions
    'actions' => [
        'checkout' => 'Zur Kasse',
        'continue_shopping' => 'Weiter einkaufen',
        'update_cart' => 'Warenkorb aktualisieren',
        'clear_cart' => 'Warenkorb leeren',
    ],

    // Checkout
    'checkout' => [
        'title' => 'Kasse',
        'steps' => [
            'shipping' => 'Versand',
            'billing' => 'Rechnung',
            'payment' => 'Zahlung',
        ],
        'shipping_info' => 'Versandinformationen',
        'billing_info' => 'Rechnungsinformationen',
        'same_as_shipping' => 'Wie Versandadresse',
        'continue_to_billing' => 'Weiter zur Rechnung',
        'continue_to_payment' => 'Weiter zur Zahlung',
        'back' => 'Zurück',
        'place_order' => 'Bestellung aufgeben',
        'processing' => 'Wird verarbeitet...',
        'order_summary' => 'Bestellübersicht',
    ],

    // Form fields
    'form' => [
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'company' => 'Firma',
        'address_line_1' => 'Adresszeile 1',
        'address_line_2' => 'Adresszeile 2 (optional)',
        'city' => 'Stadt',
        'state' => 'Bundesland',
        'postal_code' => 'Postleitzahl',
        'country' => 'Land',
    ],

    // Payment
    'payment' => [
        'title' => 'Zahlungsmethode',
        'pay_on_delivery' => 'Zahlung bei Lieferung / Rechnung',
        'pay_on_delivery_desc' => 'Bezahlen Sie bei Erhalt Ihrer Bestellung oder per Rechnung',
        'ideal' => 'iDEAL',
        'credit_card' => 'Kreditkarte',
        'bank_transfer' => 'Banküberweisung',
    ],

    // Success
    'success' => [
        'title' => 'Vielen Dank für Ihre Bestellung!',
        'subtitle' => 'Ihre Bestellung wurde bestätigt und wird in Kürze bearbeitet.',
        'order_number' => 'Bestellnummer',
        'confirmation_email' => 'Sie erhalten eine Bestätigungs-E-Mail an :email.',
        'order_details' => 'Bestelldetails',
        'continue_shopping' => 'Weiter einkaufen',
        'shipping_address' => 'Versandadresse',
        'billing_address' => 'Rechnungsadresse',
    ],

    // Notifications
    'notifications' => [
        'added' => 'Produkt zum Warenkorb hinzugefügt',
        'removed' => 'Produkt aus dem Warenkorb entfernt',
        'updated' => 'Warenkorb aktualisiert',
        'error' => 'Etwas ist schief gelaufen',
    ],
];
