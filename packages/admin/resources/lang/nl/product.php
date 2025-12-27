<?php

return [

    'label' => 'Product',

    'plural_label' => 'Producten',

    'tabs' => [
        'all' => 'Allemaal',
        'published' => 'Gepubliceerd',
        'draft' => 'Concept',
    ],

    'status' => [
        'unpublished' => [
            'content' => 'Momenteel in conceptstatus, dit product is verborgen op alle kanalen en klantgroepen.',
        ],
        'availability' => [
            'customer_groups' => 'Dit product is momenteel niet beschikbaar voor alle klantgroepen.',
            'channels' => 'Dit product is momenteel niet beschikbaar voor alle kanalen.',
        ],
    ],

    'table' => [
        'status' => [
            'label' => 'Status',
            'states' => [
                'deleted' => 'Verwijderd',
                'draft' => 'Concept',
                'published' => 'Gepubliceerd',
            ],
        ],
        'name' => [
            'label' => 'Naam',
        ],
        'brand' => [
            'label' => 'Merk',
        ],
        'sku' => [
            'label' => 'SKU',
        ],
        'stock' => [
            'label' => 'Voorraad',
        ],
        'producttype' => [
            'label' => 'Producttype',
        ],
    ],

    'actions' => [
        'create' => [
            'label' => 'Product aanmaken',
        ],
        'create_manual' => [
            'label' => 'Handmatig product aanmaken',
        ],
        'create_from_supplier' => [
            'label' => 'Aanmaken van leveranciersproduct',
        ],
        'add_supplier_variant' => [
            'label' => 'Leveranciersvariant toevoegen',
            'sku_helper' => 'Laat leeg om het leveranciersproduct ID als SKU te gebruiken.',
            'sku_placeholder' => 'Automatisch gegenereerd van leverancier',
            'success' => 'Leveranciersvariant succesvol toegevoegd.',
        ],
        'edit_status' => [
            'label' => 'Status bijwerken',
            'heading' => 'Status bijwerken',
        ],
    ],

    'form' => [
        'name' => [
            'label' => 'Naam',
        ],
        'brand' => [
            'label' => 'Merk',
        ],
        'sku' => [
            'label' => 'SKU',
        ],
        'producttype' => [
            'label' => 'Producttype',
        ],
        'supplier_id' => [
            'label' => 'Leverancier',
        ],
        'supplier_product_id' => [
            'label' => 'Leveranciersproduct',
        ],
        'status' => [
            'label' => 'Status',
            'options' => [
                'published' => [
                    'label' => 'Gepubliceerd',
                    'description' => 'Dit product zal beschikbaar zijn voor alle ingeschakelde klantgroepen en kanalen',
                ],
                'draft' => [
                    'label' => 'Concept',
                    'description' => 'Dit product zal verborgen zijn op alle kanalen en klantgroepen',
                ],
            ],
        ],
        'tags' => [
            'label' => 'Tags',
            'helper_text' => 'Scheid tags door op Enter, Tab of komma (,) te drukken',
        ],
        'collections' => [
            'label' => 'Collecties',
            'select_collection' => 'Selecteer een collectie',
        ],
    ],

    'pages' => [
        'availability' => [
            'label' => 'Beschikbaarheid',
        ],
        'edit' => [
            'title' => 'Basisinformatie',
        ],
        'identifiers' => [
            'label' => 'Product Identificatoren',
        ],
        'inventory' => [
            'label' => 'Voorraad',
        ],
        'pricing' => [
            'form' => [
                'tax_class_id' => [
                    'label' => 'Belastingklasse',
                ],
                'tax_ref' => [
                    'label' => 'Belastingreferentie',
                    'helper_text' => 'Optioneel, voor integratie met systemen van derden.',
                ],
            ],
        ],
        'shipping' => [
            'label' => 'Verzending',
        ],
        'variants' => [
            'label' => 'Varianten',
        ],
        'collections' => [
            'label' => 'Collecties',
            'select_collection' => 'Selecteer een collectie',
        ],
        'associations' => [
            'label' => 'Productassociaties',
        ],
    ],

    'configurator' => [
        'loading' => 'Configurator laden...',
        'select_option' => 'Selecteer een optie',
        'quantity' => 'Aantal',
        'dimensions' => 'Afmetingen',
        'size_presets' => 'Snelle maten',
        'custom_size' => 'Aangepaste maat',
        'next_step' => 'Volgende stap',
        'pricing' => 'Prijzen',
        'cost_price' => 'Inkoopprijs',
        'sell_price' => 'Verkoopprijs',
        'breakdown' => 'Prijsopbouw',
        'shipping_options' => 'Verzendopties',
        'complete_config_for_price' => 'Voltooi de configuratie om prijzen te zien.',
        'current_selections' => 'Huidige selecties',
        'selections_made' => 'opties geselecteerd',
        'actions' => [
            'reset' => 'Opnieuw',
            'create_variant' => 'Variant aanmaken',
            'link_variant' => 'Configuratie opslaan',
            'unlink' => 'Ontkoppelen',
            'configure_probo' => 'Configureren',
            'configure' => 'Configureren',
            'edit_configuration' => 'Configuratie bewerken',
        ],
        'notifications' => [
            'incomplete' => 'Voltooi alle vereiste opties.',
            'select_variant' => 'Selecteer een variant om te koppelen.',
            'variant_exists' => 'Een variant met deze configuratie bestaat al.',
            'variant_created' => 'Variant succesvol aangemaakt.',
            'variant_linked' => 'Configuratie succesvol opgeslagen.',
            'variant_not_found' => 'Geselecteerde variant niet gevonden.',
            'no_configuration' => 'Deze variant heeft geen configuratie om te ontkoppelen.',
            'unlinked' => 'Configuratie succesvol ontkoppeld.',
            'error' => 'Er is een fout opgetreden.',
        ],
        'modal' => [
            'title' => 'Product Configurator',
            'title_with_product' => 'Product Configurator: :product',
            'select_supplier' => 'Selecteer leverancier',
            'select_product' => 'Selecteer leveranciersproduct',
            'width' => 'Breedte',
            'height' => 'Hoogte',
            'quantity' => 'Aantal',
            'sku_helper' => 'Laat leeg om automatisch te genereren op basis van afmetingen',
            'unsupported_driver' => 'Geen configurator beschikbaar voor leverancier driver ":driver".',
        ],
        'cross_sell' => [
            'no_accessories' => 'Geen accessoires',
            'amount' => 'Aantal',
            'continue' => 'Doorgaan',
        ],
    ],

];
