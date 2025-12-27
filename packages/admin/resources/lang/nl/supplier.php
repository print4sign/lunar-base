<?php

return [
    'label' => 'Leverancier',
    'plural_label' => 'Leveranciers',
    'form' => [
        'name' => [
            'label' => 'Naam',
        ],
        'handle' => [
            'label' => 'Handle',
        ],
        'driver' => [
            'label' => 'Driver',
            'helper_text' => 'Selecteer de driver voor deze leveranciersintegratie.',
        ],
        'enabled' => [
            'label' => 'Ingeschakeld',
            'helper_text' => 'Schakel deze leverancier in of uit.',
        ],
        'priority' => [
            'label' => 'Prioriteit',
            'helper_text' => 'Leveranciers met hogere prioriteit worden geprefereerd bij het routeren van bestellingen.',
        ],
        'credentials' => [
            'label' => 'Referenties',
            'helper_text' => 'API-referenties voor deze leverancier. Deze worden versleuteld opgeslagen.',
            'key_label' => 'Sleutel',
            'value_label' => 'Waarde',
            'add_label' => 'Referentie toevoegen',
        ],
        'capabilities' => [
            'label' => 'Mogelijkheden',
            'helper_text' => 'Selecteer welke mogelijkheden deze leverancier ondersteunt.',
            'options' => [
                'catalog_sync' => 'Catalogus synchronisatie',
                'pricing' => 'Dynamische prijzen',
                'ordering' => 'Bestelling plaatsen',
                'file_upload' => 'Bestand uploaden',
                'tracking' => 'Bestelling volgen',
            ],
        ],
    ],
    'table' => [
        'name' => [
            'label' => 'Naam',
        ],
        'handle' => [
            'label' => 'Handle',
        ],
        'driver' => [
            'label' => 'Driver',
        ],
        'priority' => [
            'label' => 'Prioriteit',
        ],
        'enabled' => [
            'label' => 'Ingeschakeld',
            'badge' => 'Ingeschakeld',
        ],
        'disabled' => [
            'badge' => 'Uitgeschakeld',
        ],
        'products_count' => [
            'label' => 'Producten',
        ],
        'actions' => [
            'sync' => [
                'label' => 'Catalogus synchroniseren',
            ],
        ],
    ],
    'configurator' => [
        'helloprint' => [
            'title' => 'HelloPrint Configurator',
            'description' => 'Configureer je HelloPrint productopties en specificaties.',
            'coming_soon' => 'HelloPrint configurator komt binnenkort. Deze functie is momenteel in ontwikkeling.',
        ],
    ],
];
