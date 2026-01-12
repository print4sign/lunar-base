<?php

return [
    'label' => 'Inspiratie',
    'plural_label' => 'Inspiraties',

    'form' => [
        'content' => ['heading' => 'Inhoud'],
        'details' => ['heading' => 'Details'],
        'type' => [
            'label' => 'Type',
            'options' => [
                'review' => 'Review',
                'case_study' => 'Case Study',
            ],
        ],
        'rating' => ['label' => 'Beoordeling'],
        'text' => ['label' => 'Review Tekst'],
        'title' => ['label' => 'Titel'],
        'company_name' => ['label' => 'Bedrijfsnaam'],
        'project_type' => ['label' => 'Project Type'],
        'photos' => ['label' => 'Foto\'s'],
        'order' => ['label' => 'Bestelling'],
        'product' => ['label' => 'Product'],
        'customer' => ['label' => 'Klant'],
        'status' => [
            'label' => 'Status',
            'options' => [
                'pending' => 'In afwachting',
                'approved' => 'Goedgekeurd',
                'rejected' => 'Afgewezen',
            ],
        ],
        'rejection_reason' => ['label' => 'Reden afwijzing'],
        'featured' => [
            'label' => 'Uitgelicht',
            'helper' => 'Uitgelichte inspiraties worden prominent getoond op de inspiratiepagina',
        ],
        'published_at' => ['label' => 'Gepubliceerd op'],
        'permission_granted' => [
            'label' => 'Toestemming gegeven',
            'helper' => 'De klant heeft toestemming gegeven om de inhoud te gebruiken',
        ],
        'position' => ['label' => 'Positie'],
    ],

    'table' => [
        'photos' => ['label' => 'Foto\'s'],
        'order' => ['label' => 'Bestelling'],
        'product' => ['label' => 'Product'],
        'rating' => ['label' => 'Beoordeling'],
        'type' => ['label' => 'Type'],
        'status' => ['label' => 'Status'],
        'featured' => ['label' => 'Uitgelicht'],
        'created_at' => ['label' => 'Aangemaakt op'],
    ],

    'actions' => [
        'approve' => 'Goedkeuren',
        'reject' => 'Afwijzen',
        'approve_selected' => 'Geselecteerde goedkeuren',
    ],
];
