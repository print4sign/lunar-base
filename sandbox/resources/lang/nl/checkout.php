<?php

return [
    // Progress steps
    'steps' => [
        'order' => 'Bestelling',
        'finalize' => 'Afronden',
        'confirmation' => 'Bevestiging',
    ],

    // Cart contents section
    'cart' => [
        'title' => 'Inhoud winkelwagen',
        'hide_composition' => 'Verberg samenstelling',
        'show_composition' => 'Toon samenstelling',
        'product_reference' => 'Productreferentie (optioneel)',
        'start_upload' => 'Start met uploaden',
        'files_uploaded' => 'Bestanden geüpload',
        'change_files' => 'Wijzigen',
        'deliver_later_selected' => 'Lever later (+€15)',
        'delivery_specs' => 'Aanleverspecificaties',
        'upload_coming_soon' => 'Bestand uploaden komt binnenkort beschikbaar',
        'online_designer' => 'Online Ontwerpen',
        'design_ready' => 'Ontwerp klaar',
    ],

    // Shipping address section
    'address' => [
        'title' => 'Bezorgadres',
        'company' => 'Bedrijfsnaam',
        'first_name' => 'Voornaam',
        'last_name' => 'Achternaam',
        'street' => 'Straat',
        'house_number' => 'Huisnummer',
        'postal_code' => 'Postcode',
        'city' => 'Plaats',
        'country' => 'Land',
        'phone' => 'Telefoonnummer',
        'email' => 'E-mailadres',
        'use_default' => 'Gebruik standaard bezorgadres',
        'enter_new' => 'Nieuw adres invoeren',
        'choose_from_book' => 'Kies uit het adresboek',
        'save_address' => 'Dit adres opslaan',
        'login_prompt' => 'Log in om je opgeslagen adressen te gebruiken',
        'login' => 'Inloggen',
        'guest_checkout' => 'Verder als gast',
    ],

    // Delivery options
    'delivery' => [
        'title' => 'Levering',
        'new_delivery' => 'Nieuwe levering',
        'combine' => 'Combineren',
        'no_deliveries' => 'Geen leveringen',
        'from' => 'Vanaf',
    ],

    // Delivery date section
    'date' => [
        'title' => 'Leverdatum',
        'no_rush_fee' => 'Geen spoedkosten',
        'rush_fee' => 'Spoedkosten',
        'show_more' => 'Toon meer datums',
        'show_less' => 'Toon minder datums',
    ],

    // Shipping method section
    'shipping' => [
        'title' => 'Verzendmethode',
        'delivery' => 'Bezorgen',
        'pickup' => 'Afhalen',
        'most_reliable' => 'Meest betrouwbaar',
        'excl_packaging' => 'Excl. :price verpakking',
        'incl_packaging' => 'Incl. verpakking',
        'time_window' => ':from - :to uur',
        'select_method' => 'Maak je keuze',
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
        'title' => 'Samenvatting',
        'subtotal' => 'Subtotaal',
        'items' => ':count artikel|:count artikelen',
        'packaging' => 'Verpakkingskosten',
        'shipping' => 'Verzendkosten',
        'total' => 'Totaal',
        'total_incl_tax' => 'Totaal incl. btw',
        'total_excl_tax' => 'Totaal excl. btw',
        'continue' => 'Ik ga door met bestellen',
        'clear_cart' => 'Winkelwagen legen',
        'save_for_later' => 'Bewaren voor later',
        'upload_required' => 'Upload je bestanden om door te gaan.',
        'upload_files' => 'Upload je bestanden om door te gaan.',
        'select_shipping' => 'Selecteer een verzendmethode',
        'select_date' => 'Selecteer een leverdatum',
        'enter_address' => 'Voer een bezorgadres in',
    ],

    // Carriers
    'carriers' => [
        'onb' => 'ONB',
        'onb_standard' => 'ONB',
        'onb_express' => 'ONB Express',
        'onb_saturday' => 'ONB Zaterdag',
        'onb_volume' => 'ONB Volume',
        'dhl' => 'DHL',
        'dhl_standard' => 'DHL',
        'dhl_express' => 'DHL Express',
        'dhl_saturday' => 'DHL Zaterdag',
        'dhl_letterbox' => 'DHL Brievenbus',
        'dhl_for_you' => 'DHL (particulier)',
        'dhl_for_you_evening' => 'DHL avondlevering (particulier)',
        'postnl' => 'PostNL',
        'postnl_standard' => 'PostNL',
        'postnl_express' => 'PostNL Express',
        'postnl_international' => 'PostNL Internationaal',
        'ups' => 'UPS',
        'ups_standard' => 'UPS',
    ],

    // Validation messages
    'validation' => [
        'address_required' => 'Vul een bezorgadres in',
        'date_required' => 'Selecteer een leverdatum',
        'shipping_required' => 'Selecteer een verzendmethode',
    ],

    // Notifications
    'notifications' => [
        'quantity_updated' => 'Aantal bijgewerkt',
        'item_removed' => 'Artikel verwijderd',
        'item_duplicated' => 'Artikel gedupliceerd',
        'cart_cleared' => 'Winkelwagen geleegd',
        'address_saved' => 'Adres opgeslagen',
        'uploads_confirmed' => 'Bestanden succesvol bevestigd',
        'error' => 'Er is iets misgegaan',
    ],

    // Save for later
    'save_for_later' => [
        'title' => 'Bewaren voor later',
        'description' => 'Bewaar je winkelwagen om deze op een later moment te bestellen.',
        'reference' => 'Referentie',
        'placeholder' => 'Geef je winkelwagen een naam',
        'save' => 'Bewaar',
        'success' => 'Winkelwagen opgeslagen',
        'empty_cart' => 'Je winkelwagen is leeg',
        'login_required' => 'Log in om je winkelwagen te bewaren',
        'saved_title' => 'Je winkelwagen is bewaard!',
        'saved_description' => 'Je kunt je winkelwagen terugvinden in het overzicht en op een later moment bestellen.',
        'to_overview' => 'Naar overzicht',
        'to_home' => 'Naar homepagina',
    ],
];
