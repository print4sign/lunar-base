<?php

return [
    'label' => 'Productvariant',
    'plural_label' => 'Productvarianten',
    'pages' => [
        'edit' => [
            'title' => 'Basisinformatie',
        ],
        'media' => [
            'title' => 'Media',
            'form' => [
                'no_selection' => [
                    'label' => 'U heeft momenteel geen afbeelding geselecteerd voor deze variant.',
                ],
                'no_media_available' => [
                    'label' => 'Er is momenteel geen media beschikbaar voor dit product.',
                ],
                'images' => [
                    'label' => 'Primaire Afbeelding',
                    'helper_text' => 'Selecteer de productafbeelding die deze variant vertegenwoordigt.',
                ],
            ],
        ],
        'identifiers' => [
            'title' => 'Identificatoren',
        ],
        'inventory' => [
            'title' => 'Voorraad',
        ],
        'shipping' => [
            'title' => 'Verzending',
        ],
        'upload' => [
            'label' => 'Upload Vereisten',
        ],
    ],
    'form' => [
        'sku' => [
            'label' => 'Artikelnummer (SKU)',
        ],
        'gtin' => [
            'label' => 'Globaal Handelsartikelnummer (GTIN)',
        ],
        'mpn' => [
            'label' => 'Fabrikant Onderdeelnummer (MPN)',
        ],
        'ean' => [
            'label' => 'UPC/EAN',
        ],
        'stock' => [
            'label' => 'Op Voorraad',
        ],
        'backorder' => [
            'label' => 'In Nabestelling',
        ],
        'purchasable' => [
            'label' => 'Koopbaarheid',
            'options' => [
                'always' => 'Altijd',
                'in_stock' => 'Op Voorraad',
                'in_stock_or_on_backorder' => 'Op Voorraad of In Nabestelling',
            ],
        ],
        'unit_quantity' => [
            'label' => 'Eenheidsaantal',
            'helper_text' => 'Hoeveel individuele items vormen 1 eenheid.',
        ],
        'min_quantity' => [
            'label' => 'Minimale Hoeveelheid',
            'helper_text' => 'De minimale hoeveelheid van een productvariant die in één aankoop kan worden gekocht.',
        ],
        'quantity_increment' => [
            'label' => 'Hoeveelheidsverhoging',
            'helper_text' => 'De productvariant moet in veelvouden van deze hoeveelheid worden gekocht.',
        ],
        'tax_class_id' => [
            'label' => 'Belastingklasse',
        ],
        'shippable' => [
            'label' => 'Verzendbaar',
        ],
        'length_value' => [
            'label' => 'Lengte',
        ],
        'length_unit' => [
            'label' => 'Lengte-eenheid',
        ],
        'width_value' => [
            'label' => 'Breedte',
        ],
        'width_unit' => [
            'label' => 'Breedte-eenheid',
        ],
        'height_value' => [
            'label' => 'Hoogte',
        ],
        'height_unit' => [
            'label' => 'Hoogte-eenheid',
        ],
        'weight_value' => [
            'label' => 'Gewicht',
        ],
        'weight_unit' => [
            'label' => 'Gewichtseenheid',
        ],
    ],
    'fulfillment' => [
        'actions' => [
            'ai_match' => 'Match met AI',
            'link_supplier' => 'Koppel Leverancier',
            'set_margin' => 'Stel Marge In',
            'enable_dynamic' => 'Dynamische Prijzen Inschakelen',
            'disable_dynamic' => 'Dynamische Prijzen Uitschakelen',
            'configure' => 'Configureren',
            'refresh_price' => 'Prijs Verversen',
            'unlink' => 'Ontkoppel Leverancier',
        ],
        'ai_match' => [
            'title' => 'AI Product Matching',
            'description' => 'Onze AI analyseert deze productvariant en vindt de best passende leverancier producten op basis van specificaties, afmetingen en attributen.',
            'filter_suppliers' => 'Filter op Leveranciers',
            'filter_help' => 'Houd Ctrl/Cmd ingedrukt om meerdere leveranciers te selecteren, of laat leeg om alle te doorzoeken.',
            'analyzing' => 'Product analyseren en catalogus doorzoeken...',
            'error_title' => 'Matching Mislukt',
            'error' => 'Kon geen matches vinden: :message',
            'no_matches' => 'Geen matchende leverancier producten gevonden. Pas je leverancier filter aan of controleer of er leverancier producten beschikbaar zijn in de catalogus.',
            'recommended' => 'Aanbevolen',
            'concerns' => 'Aandachtspunten',
            'select' => 'Selecteer',
            'refresh' => 'Ververs Matches',
            'linked_success' => 'Leverancier product succesvol gekoppeld!',
            'link_error' => 'Kon leverancier product niet koppelen',
        ],
        'dynamic' => [
            'title' => 'Dynamische Prijzen',
            'info' => 'Prijzen worden automatisch opgehaald van de leverancier op basis van configuratie en aantal.',
        ],
        'margin' => 'Marge',
        'no_margin_set' => 'Geen marge geconfigureerd',
        'no_supplier' => 'Geen leverancier gekoppeld',
        'supplier' => 'Leverancier',
        'supplier_info' => 'De leverancier die dit product zal verzenden',
        'supplier_product' => 'Leverancier Product',
        'supplier_product_info' => 'Het specifieke product uit de leverancier catalogus',
        'supplier_product_id' => 'Product ID',
        'supplier_product_name' => 'Productnaam',
    ],
];
