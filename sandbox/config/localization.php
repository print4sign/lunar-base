<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | The locales that are supported by the application. The first locale
    | in this array will be used as the default locale.
    |
    */
    'supported_locales' => ['nl', 'en', 'de', 'es'],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale to use when no locale is specified in the URL.
    |
    */
    'default_locale' => 'nl',

    /*
    |--------------------------------------------------------------------------
    | Hide Default Locale
    |--------------------------------------------------------------------------
    |
    | When set to true, the default locale will not be shown in the URL.
    | For example: /products instead of /nl/products
    |
    */
    'hide_default_locale' => false,

    /*
    |--------------------------------------------------------------------------
    | Locale Detection Order
    |--------------------------------------------------------------------------
    |
    | The order in which locales should be detected. Options are:
    | 'url', 'session', 'cookie', 'browser'
    |
    */
    'detection_order' => ['url', 'session', 'cookie', 'browser'],

    /*
    |--------------------------------------------------------------------------
    | Session Key
    |--------------------------------------------------------------------------
    |
    | The session key used to store the current locale.
    |
    */
    'session_key' => 'app_locale',

    /*
    |--------------------------------------------------------------------------
    | Cookie Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the locale cookie.
    |
    */
    'cookie' => [
        'name' => 'locale',
        'lifetime' => 43200, // 30 days in minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Translated Route Segments
    |--------------------------------------------------------------------------
    |
    | Define translated URL segments for each locale. These are used for
    | SEO-friendly URLs like /nl/producten vs /en/products.
    |
    */
    'route_segments' => [
        'nl' => [
            'products' => 'producten',
            'all-products' => 'alle-producten',
            'collections' => 'collecties',
            'search' => 'zoeken',
            'checkout' => 'afrekenen',
            'cart' => 'winkelwagen',
            'about' => 'over-ons',
            'contact' => 'contact',
            'articles' => 'artikelen',
            'dashboard' => 'dashboard',
            'orders' => 'bestellingen',
            'addresses' => 'adressen',
            'saved-carts' => 'opgeslagen-winkelmanden',
            'invoices' => 'facturen',
            'login' => 'inloggen',
            'register' => 'registreren',
            'forgot-password' => 'wachtwoord-vergeten',
            'reset-password' => 'wachtwoord-reset',
            'delivery-specs' => 'aanleverspecificaties',
        ],
        'en' => [
            'products' => 'products',
            'all-products' => 'all-products',
            'collections' => 'collections',
            'search' => 'search',
            'checkout' => 'checkout',
            'cart' => 'cart',
            'about' => 'about-us',
            'contact' => 'contact',
            'articles' => 'articles',
            'dashboard' => 'dashboard',
            'orders' => 'orders',
            'addresses' => 'addresses',
            'saved-carts' => 'saved-carts',
            'invoices' => 'invoices',
            'login' => 'login',
            'register' => 'register',
            'forgot-password' => 'forgot-password',
            'reset-password' => 'reset-password',
            'delivery-specs' => 'file-specifications',
        ],
        'de' => [
            'products' => 'produkte',
            'all-products' => 'alle-produkte',
            'collections' => 'kollektionen',
            'search' => 'suche',
            'checkout' => 'kasse',
            'cart' => 'warenkorb',
            'about' => 'ueber-uns',
            'contact' => 'kontakt',
            'articles' => 'artikel',
            'dashboard' => 'dashboard',
            'orders' => 'bestellungen',
            'addresses' => 'adressen',
            'saved-carts' => 'gespeicherte-warenkoerbe',
            'invoices' => 'rechnungen',
            'login' => 'anmelden',
            'register' => 'registrieren',
            'forgot-password' => 'passwort-vergessen',
            'reset-password' => 'passwort-zuruecksetzen',
            'delivery-specs' => 'dateispezifikationen',
        ],
        'es' => [
            'products' => 'productos',
            'all-products' => 'todos-productos',
            'collections' => 'colecciones',
            'search' => 'buscar',
            'checkout' => 'pagar',
            'cart' => 'carrito',
            'about' => 'sobre-nosotros',
            'contact' => 'contacto',
            'articles' => 'articulos',
            'dashboard' => 'panel',
            'orders' => 'pedidos',
            'addresses' => 'direcciones',
            'saved-carts' => 'carritos-guardados',
            'invoices' => 'facturas',
            'login' => 'iniciar-sesion',
            'register' => 'registrarse',
            'forgot-password' => 'recuperar-contrasena',
            'reset-password' => 'restablecer-contrasena',
            'delivery-specs' => 'especificaciones-de-archivos',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Display Names
    |--------------------------------------------------------------------------
    |
    | The display names for each locale in their native language.
    |
    */
    'locale_names' => [
        'nl' => 'Nederlands',
        'en' => 'English',
        'de' => 'Deutsch',
        'es' => 'Español',
    ],

    /*
    |--------------------------------------------------------------------------
    | Browser Locale Mapping
    |--------------------------------------------------------------------------
    |
    | Map browser locale codes to application locale codes.
    |
    */
    'browser_locale_mapping' => [
        'nl' => 'nl',
        'nl-NL' => 'nl',
        'nl-BE' => 'nl',
        'en' => 'en',
        'en-US' => 'en',
        'en-GB' => 'en',
        'en-AU' => 'en',
        'de' => 'de',
        'de-DE' => 'de',
        'de-AT' => 'de',
        'de-CH' => 'de',
        'es' => 'es',
        'es-ES' => 'es',
        'es-MX' => 'es',
        'es-AR' => 'es',
    ],
];
