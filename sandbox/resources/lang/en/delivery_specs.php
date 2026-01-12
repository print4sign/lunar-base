<?php

return [
    'title' => 'Delivery specifications',
    'close' => 'Close',
    'explain' => 'Explain',
    'more_info' => 'View all about layout and files',
    'no_specs' => 'No specifications available',
    'no_specs_description' => 'This product does not have specific file delivery requirements.',

    // Template section
    'template' => [
        'title' => 'Template',
        'description' => 'Download the template to create your file. Also follow the requirements below to deliver your file correctly.',
        'download' => 'Download template',
        'not_available' => 'Template is not available for this product.',
    ],

    // Uploader types
    'types' => [
        'single' => 'Single file',
        'frontback' => 'Front & back',
        'multipage' => 'Multiple pages',
        'custom' => 'Custom',
    ],

    // Specifications
    'specs' => [
        'amount' => 'Number of designs',
        'amount_value' => 'Maximum :amount',

        'dimensions' => 'Dimensions',

        'resolution' => 'Optimal resolution',
        'resolution_info' => 'We recommend a minimum of 150 PPI for a sharp print. A higher resolution gives better results.',

        'colors' => 'Optimal colors',
        'colors_value' => 'Follow our guidelines',
        'colors_info' => 'Use CMYK color mode for best print results. RGB colors will be converted automatically.',

        'bleed' => 'Bleed',
        'bleed_value' => 'Top: :top mm, Right: :right mm, Bottom: :bottom mm, Left: :left mm',
        'bleed_info' => 'Bleed is the area that extends beyond the edge of your design. It ensures no white edges after cutting.',

        'line_thickness' => 'Minimum line thickness',
        'line_thickness_info' => 'Very thin lines may not print correctly. We recommend a minimum line thickness of 0.25 pt.',

        'font_size' => 'Minimum font size',
        'font_size_info' => 'Very small text may become illegible. We recommend a minimum font size of 6 pt.',

        'file_type' => 'File type',
        'file_type_value' => 'PDF or JPG',
        'file_type_info' => 'PDF files give the best results. JPG files should be at least 150 PPI.',

        'embed_fonts' => 'Embed fonts',
        'embed_fonts_value' => 'Yes, recommended',
        'embed_fonts_info' => 'Embed all fonts in your PDF to ensure they print correctly. Non-embedded fonts may be substituted.',

        'cut_marks' => 'Cut marks',
        'cut_marks_required' => 'Yes, required',
        'cut_marks_not_allowed' => 'No, not allowed',
        'cut_marks_info' => 'Cut marks show where the product will be cut. Only include if specifically required.',

        'max_file_size' => 'Max. file size',

        'white_spot' => 'White spot',
        'white_spot_required' => 'Required',
        'white_spot_info' => 'A white spot is required for this product. This is used for special print effects.',

        'tiling' => 'Tiling',
        'tiling_mandatory' => 'Mandatory',
        'tiling_optional' => 'Optional',
        'tiling_info' => 'Tiling allows large prints to be split into smaller sections for printing.',
    ],
];
