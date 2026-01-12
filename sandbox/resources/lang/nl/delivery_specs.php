<?php

return [
    'title' => 'Aanleverspecificaties',
    'close' => 'Sluiten',
    'explain' => 'Uitleg',
    'more_info' => 'Bekijk alles over opmaak en bestanden',
    'no_specs' => 'Geen specificaties beschikbaar',
    'no_specs_description' => 'Dit product heeft geen specifieke aanleverspecificaties.',

    // Template section
    'template' => [
        'title' => 'Werktekening',
        'description' => 'Download de werktekening (template) om je bestand op te maken. Houd je daarnaast aan onderstaande eisen om je bestand correct aan te leveren.',
        'download' => 'Download werktekening',
        'not_available' => 'Werktekening is niet beschikbaar voor dit product.',
    ],

    // Uploader types
    'types' => [
        'single' => 'Enkel bestand',
        'frontback' => 'Voor- & achterkant',
        'multipage' => 'Meerdere pagina\'s',
        'custom' => 'Aangepast',
    ],

    // Specifications
    'specs' => [
        'amount' => 'Aantal ontwerpen',
        'amount_value' => 'Maximaal :amount',

        'dimensions' => 'Afmetingen',

        'resolution' => 'Optimale resolutie',
        'resolution_info' => 'Wij adviseren minimaal 150 PPI voor een scherpe print. Een hogere resolutie geeft betere resultaten.',

        'colors' => 'Optimale kleuren',
        'colors_value' => 'Volg onze richtlijnen',
        'colors_info' => 'Gebruik CMYK-kleurmodus voor het beste drukresultaat. RGB-kleuren worden automatisch geconverteerd.',

        'bleed' => 'Afloop',
        'bleed_value' => 'Boven: :top mm, Rechts: :right mm, Onder: :bottom mm, Links: :left mm',
        'bleed_info' => 'Afloop is het gebied dat doorloopt tot buiten de rand van je ontwerp. Dit zorgt ervoor dat er geen witte randen ontstaan na het snijden.',

        'line_thickness' => 'Minimale lijndikte',
        'line_thickness_info' => 'Zeer dunne lijnen worden mogelijk niet correct afgedrukt. Wij adviseren een minimale lijndikte van 0,25 pt.',

        'font_size' => 'Minimale lettergrootte',
        'font_size_info' => 'Zeer kleine tekst kan onleesbaar worden. Wij adviseren een minimale lettergrootte van 6 pt.',

        'file_type' => 'Bestandstype',
        'file_type_value' => 'PDF of JPG',
        'file_type_info' => 'PDF-bestanden geven het beste resultaat. JPG-bestanden moeten minimaal 150 PPI zijn.',

        'embed_fonts' => 'Lettertypes insluiten',
        'embed_fonts_value' => 'Ja, aanbevolen',
        'embed_fonts_info' => 'Sluit alle lettertypes in je PDF in om er zeker van te zijn dat ze correct worden afgedrukt. Niet-ingesloten lettertypes kunnen worden vervangen.',

        'cut_marks' => 'Snijtekens',
        'cut_marks_required' => 'Ja, verplicht',
        'cut_marks_not_allowed' => 'Nee, niet toegestaan',
        'cut_marks_info' => 'Snijtekens geven aan waar het product wordt gesneden. Voeg deze alleen toe als dit specifiek vereist is.',

        'max_file_size' => 'Max. bestandsgrootte',

        'white_spot' => 'Wit spot',
        'white_spot_required' => 'Vereist',
        'white_spot_info' => 'Een wit spot is vereist voor dit product. Dit wordt gebruikt voor speciale drukeffecten.',

        'tiling' => 'Tegelen',
        'tiling_mandatory' => 'Verplicht',
        'tiling_optional' => 'Optioneel',
        'tiling_info' => 'Tegelen maakt het mogelijk om grote prints op te splitsen in kleinere secties voor het afdrukken.',
    ],
];
