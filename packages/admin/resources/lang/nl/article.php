<?php

return [
    'label' => 'Artikel',
    'plural_label' => 'Artikelen',

    'form' => [
        'title' => ['label' => 'Titel'],
        'slug' => ['label' => 'Slug'],
        'excerpt' => ['label' => 'Samenvatting'],
        'body' => ['label' => 'Inhoud'],
        'meta_description' => ['label' => 'Meta Omschrijving'],
        'status' => [
            'label' => 'Status',
            'options' => [
                'draft' => 'Concept',
                'rewritten' => 'Herschreven',
                'published' => 'Gepubliceerd',
            ],
        ],
        'category' => [
            'label' => 'Categorie',
            'options' => [
                'klantenservice' => 'Klantenservice',
                'blog' => 'Blog',
            ],
        ],
        'subcategory' => ['label' => 'Subcategorie'],
        'tags' => ['label' => 'Tags'],
        'published_at' => ['label' => 'Gepubliceerd Op'],
        'source_url' => ['label' => 'Bron URL'],
    ],

    'table' => [
        'title' => ['label' => 'Titel'],
        'category' => ['label' => 'Categorie'],
        'status' => ['label' => 'Status'],
        'published_at' => ['label' => 'Gepubliceerd Op'],
        'updated_at' => ['label' => 'Bijgewerkt Op'],
    ],

    'actions' => [
        'rewrite' => [
            'label' => 'Herschrijf met AI',
            'modal_heading' => 'Artikel Herschrijven met AI',
            'modal_description' => 'Dit gebruikt Claude AI om de artikelinhoud te herschrijven zodat deze uniek en copyright-vrij is. De originele inhoud wordt bewaard.',
            'success' => 'Artikel is succesvol herschreven.',
            'error' => 'Fout bij herschrijven van artikel.',
        ],
    ],
];
