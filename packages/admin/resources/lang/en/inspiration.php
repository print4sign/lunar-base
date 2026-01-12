<?php

return [
    'label' => 'Inspiration',
    'plural_label' => 'Inspirations',

    'form' => [
        'content' => ['heading' => 'Content'],
        'details' => ['heading' => 'Details'],
        'type' => [
            'label' => 'Type',
            'options' => [
                'review' => 'Review',
                'case_study' => 'Case Study',
            ],
        ],
        'rating' => ['label' => 'Rating'],
        'text' => ['label' => 'Review Text'],
        'title' => ['label' => 'Title'],
        'company_name' => ['label' => 'Company Name'],
        'project_type' => ['label' => 'Project Type'],
        'photos' => ['label' => 'Photos'],
        'order' => ['label' => 'Order'],
        'product' => ['label' => 'Product'],
        'customer' => ['label' => 'Customer'],
        'status' => [
            'label' => 'Status',
            'options' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ],
        ],
        'rejection_reason' => ['label' => 'Rejection Reason'],
        'featured' => [
            'label' => 'Featured',
            'helper' => 'Featured inspirations are shown prominently on the inspiration page',
        ],
        'published_at' => ['label' => 'Published At'],
        'permission_granted' => [
            'label' => 'Permission Granted',
            'helper' => 'The customer has granted permission to use their content',
        ],
        'position' => ['label' => 'Position'],
    ],

    'table' => [
        'photos' => ['label' => 'Photos'],
        'order' => ['label' => 'Order'],
        'product' => ['label' => 'Product'],
        'rating' => ['label' => 'Rating'],
        'type' => ['label' => 'Type'],
        'status' => ['label' => 'Status'],
        'featured' => ['label' => 'Featured'],
        'created_at' => ['label' => 'Created At'],
    ],

    'actions' => [
        'approve' => 'Approve',
        'reject' => 'Reject',
        'approve_selected' => 'Approve Selected',
    ],
];
