<?php

return [
    'label' => 'Article',
    'plural_label' => 'Articles',

    'form' => [
        'title' => ['label' => 'Title'],
        'slug' => ['label' => 'Slug'],
        'excerpt' => ['label' => 'Excerpt'],
        'body' => ['label' => 'Body'],
        'meta_description' => ['label' => 'Meta Description'],
        'status' => [
            'label' => 'Status',
            'options' => [
                'draft' => 'Draft',
                'rewritten' => 'Rewritten',
                'published' => 'Published',
            ],
        ],
        'category' => [
            'label' => 'Category',
            'options' => [
                'klantenservice' => 'Customer Service',
                'blog' => 'Blog',
            ],
        ],
        'subcategory' => ['label' => 'Subcategory'],
        'tags' => ['label' => 'Tags'],
        'published_at' => ['label' => 'Published At'],
        'source_url' => ['label' => 'Source URL'],
    ],

    'table' => [
        'title' => ['label' => 'Title'],
        'category' => ['label' => 'Category'],
        'status' => ['label' => 'Status'],
        'published_at' => ['label' => 'Published At'],
        'updated_at' => ['label' => 'Updated At'],
    ],

    'actions' => [
        'rewrite' => [
            'label' => 'Rewrite with AI',
            'modal_heading' => 'Rewrite Article with AI',
            'modal_description' => 'This will use Claude AI to rewrite the article content to make it unique and copyright-proof. The original content will be preserved.',
            'success' => 'Article has been rewritten successfully.',
            'error' => 'Failed to rewrite article.',
        ],
    ],
];
