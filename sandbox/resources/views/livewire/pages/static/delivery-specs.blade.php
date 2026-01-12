@php
    $locale = app()->getLocale();

    $content = [
        'nl' => [
            'title' => 'Aanleverspecificaties',
            'subtitle' => 'Lever je bestanden correct aan voor het beste drukresultaat',
            'intro' => 'Voor een optimaal drukresultaat is het belangrijk dat je bestanden aan bepaalde technische eisen voldoen. Op deze pagina vind je alle informatie over bestandsformaten, kleuren, resolutie en afwerking.',

            'formats_title' => 'Bestandsformaten',
            'formats_intro' => 'Wij accepteren verschillende bestandsformaten. PDF is het meest geschikt omdat alle opmaakelementen behouden blijven.',
            'formats' => [
                ['name' => 'PDF', 'status' => 'recommended', 'desc' => 'Behoud van vectoren, transparanties en opmaak. Compacte bestandsgrootte.'],
                ['name' => 'TIFF / TIF', 'status' => 'ok', 'desc' => 'Geschikt voor foto\'s met weinig detailverlies. Grote bestandsgrootte.'],
                ['name' => 'JPG / JPEG', 'status' => 'ok', 'desc' => 'Compact formaat voor foto\'s. Let op: altijd enig kwaliteitsverlies.'],
                ['name' => 'PNG', 'status' => 'ok', 'desc' => 'Geschikt voor afbeeldingen met transparantie.'],
            ],
            'formats_note' => 'Office-bestanden (Word, Excel, PowerPoint) zijn niet geschikt voor drukwerk. Exporteer deze eerst naar PDF.',

            'colors_title' => 'Kleursystemen',
            'colors_intro' => 'Kleuren op een beeldscherm worden anders weergegeven dan op drukwerk. Gebruik het juiste kleursysteem.',
            'colors' => [
                ['name' => 'CMYK', 'full' => 'Cyaan, Magenta, Yellow, Key', 'suitable' => true, 'desc' => 'Standaard kleursysteem voor professioneel drukwerk.'],
                ['name' => 'PMS', 'full' => 'Pantone Matching System', 'suitable' => true, 'desc' => 'Voor exacte huisstijlkleuren die altijd hetzelfde moeten zijn.'],
                ['name' => 'RGB', 'full' => 'Rood, Groen, Blauw', 'suitable' => false, 'desc' => 'Bedoeld voor beeldschermen. Wordt automatisch omgezet naar CMYK.'],
            ],

            'resolution_title' => 'Resolutie',
            'resolution_intro' => 'De resolutie bepaalt de scherpte van je drukwerk. Gebruik de juiste DPI voor het beste resultaat.',
            'resolutions' => [
                ['name' => 'Vector', 'for' => 'Logo\'s & illustraties', 'suitable' => true, 'note' => 'Onbeperkt schaalbaar'],
                ['name' => '300 DPI', 'for' => 'A3 en kleiner', 'suitable' => true, 'note' => 'Optimale kwaliteit'],
                ['name' => '150 DPI', 'for' => 'A2 en groter', 'suitable' => true, 'note' => 'Voldoende voor groot formaat'],
                ['name' => '72 DPI', 'for' => 'Alleen web', 'suitable' => false, 'note' => 'Te laag voor drukwerk'],
            ],
            'resolution_note' => 'De minimale lijndikte is 0,5 pt. Dunnere lijnen kunnen onzichtbaar worden.',

            'bleed_title' => 'Afloop & veiligheidsmarge',
            'bleed_intro' => 'Door de juiste marges aan te houden voorkom je witranden en zorg je dat belangrijke elementen niet worden afgesneden.',
            'bleed_items' => [
                ['name' => 'Afloop (bleed)', 'value' => '3 mm', 'desc' => 'Laat achtergronden 3 mm doorlopen buiten het eindformaat.'],
                ['name' => 'Veiligheidsmarge', 'value' => '3-5 mm', 'desc' => 'Plaats teksten en logo\'s minimaal 3 mm vanaf de snijrand.'],
            ],

            'fonts_title' => 'Tekst & lettertypen',
            'fonts_text' => 'Om te garanderen dat je lettertypen correct worden weergegeven, adviseren we om tekst om te zetten naar lettercontouren (outlines). Zo voorkom je dat lettertypes automatisch worden vervangen.',

            'ink_title' => 'Inktdekking',
            'ink_intro' => 'De inktdekking is het totaal van alle CMYK-percentages. Een te hoge dekking kan vlekken veroorzaken.',
            'ink_items' => [
                ['type' => 'Digitaal drukwerk', 'max' => '300%'],
                ['type' => 'Offset drukwerk', 'max' => '280%'],
                ['type' => 'Kranten', 'max' => '240%'],
                ['type' => 'Textiel & vlaggen', 'max' => '200%'],
            ],
            'ink_note' => 'De minimale inktdekking is 10%. Bij lagere waarden kan het element onzichtbaar zijn.',

            'pages_title' => 'Meerdere pagina\'s',
            'pages_text' => 'Lever documenten met meerdere pagina\'s aan als losse pagina\'s in één PDF-bestand (geen spreads). Pagina\'s moeten in de juiste volgorde staan. Voor boeken en magazines met een rug lever je twee bestanden aan: één voor het binnenwerk en één voor de omslag.',

            'finishing_title' => 'Afwerkingen',
            'finishing_intro' => 'Voor speciale afwerkingen zijn aparte aanleverinstructies van toepassing.',
            'finishing_items' => [
                ['name' => 'Spot UV lak', 'desc' => 'Glanzende laklaag op specifieke delen. Geef het lakgebied aan in een apart PDF-bestand.'],
                ['name' => 'Foliedruk', 'desc' => 'Goud- of zilverfolie voor een luxe uitstraling. Lever een apart bestand aan voor de folie-elementen.'],
                ['name' => 'Stansen', 'desc' => 'Voor eigen vormen lever je een snijlijn aan als vector in een aparte laag.'],
            ],

            'checklist_title' => 'Snelle checklist',
            'checklist_items' => [
                'Bestandsformaat: PDF (bij voorkeur)',
                'Kleurmodus: CMYK of PMS',
                'Resolutie: minimaal 150-300 DPI',
                'Afloop: 3 mm rondom',
                'Veiligheidsmarge: 3 mm vanaf snijrand',
                'Teksten: omgezet naar lettercontouren',
                'Inktdekking: maximaal 280-300%',
            ],

            'help_title' => 'Hulp nodig bij het aanleveren?',
            'help_text' => 'Heb je vragen over het aanleveren van je bestanden? Ons team helpt je graag verder.',
            'help_cta' => 'Neem contact op',
        ],

        'en' => [
            'title' => 'File Specifications',
            'subtitle' => 'Deliver your files correctly for the best print results',
            'intro' => 'For optimal print results, your files must meet certain technical requirements. On this page you will find all information about file formats, colors, resolution and finishing.',

            'formats_title' => 'File formats',
            'formats_intro' => 'We accept various file formats. PDF is most suitable because all layout elements are preserved.',
            'formats' => [
                ['name' => 'PDF', 'status' => 'recommended', 'desc' => 'Preserves vectors, transparencies and layout. Compact file size.'],
                ['name' => 'TIFF / TIF', 'status' => 'ok', 'desc' => 'Suitable for photos with minimal detail loss. Large file size.'],
                ['name' => 'JPG / JPEG', 'status' => 'ok', 'desc' => 'Compact format for photos. Note: always some quality loss.'],
                ['name' => 'PNG', 'status' => 'ok', 'desc' => 'Suitable for images with transparency.'],
            ],
            'formats_note' => 'Office files (Word, Excel, PowerPoint) are not suitable for print. Export these to PDF first.',

            'colors_title' => 'Color systems',
            'colors_intro' => 'Colors on a screen are displayed differently than in print. Use the correct color system.',
            'colors' => [
                ['name' => 'CMYK', 'full' => 'Cyan, Magenta, Yellow, Key', 'suitable' => true, 'desc' => 'Standard color system for professional printing.'],
                ['name' => 'PMS', 'full' => 'Pantone Matching System', 'suitable' => true, 'desc' => 'For exact brand colors that must always be the same.'],
                ['name' => 'RGB', 'full' => 'Red, Green, Blue', 'suitable' => false, 'desc' => 'Intended for screens. Automatically converted to CMYK.'],
            ],

            'resolution_title' => 'Resolution',
            'resolution_intro' => 'Resolution determines the sharpness of your print. Use the correct DPI for the best result.',
            'resolutions' => [
                ['name' => 'Vector', 'for' => 'Logos & illustrations', 'suitable' => true, 'note' => 'Infinitely scalable'],
                ['name' => '300 DPI', 'for' => 'A3 and smaller', 'suitable' => true, 'note' => 'Optimal quality'],
                ['name' => '150 DPI', 'for' => 'A2 and larger', 'suitable' => true, 'note' => 'Sufficient for large format'],
                ['name' => '72 DPI', 'for' => 'Web only', 'suitable' => false, 'note' => 'Too low for print'],
            ],
            'resolution_note' => 'The minimum line weight is 0.5 pt. Thinner lines may become invisible.',

            'bleed_title' => 'Bleed & safety margin',
            'bleed_intro' => 'By maintaining the correct margins, you prevent white edges and ensure important elements are not cut off.',
            'bleed_items' => [
                ['name' => 'Bleed', 'value' => '3 mm', 'desc' => 'Extend backgrounds 3 mm beyond the final format.'],
                ['name' => 'Safety margin', 'value' => '3-5 mm', 'desc' => 'Place text and logos at least 3 mm from the trim edge.'],
            ],

            'fonts_title' => 'Text & fonts',
            'fonts_text' => 'To ensure your fonts are displayed correctly, we recommend converting text to outlines. This prevents fonts from being automatically replaced.',

            'ink_title' => 'Ink coverage',
            'ink_intro' => 'Ink coverage is the total of all CMYK percentages. Too high coverage can cause smudges.',
            'ink_items' => [
                ['type' => 'Digital printing', 'max' => '300%'],
                ['type' => 'Offset printing', 'max' => '280%'],
                ['type' => 'Newspapers', 'max' => '240%'],
                ['type' => 'Textile & flags', 'max' => '200%'],
            ],
            'ink_note' => 'The minimum ink coverage is 10%. At lower values the element may be invisible.',

            'pages_title' => 'Multiple pages',
            'pages_text' => 'Deliver multi-page documents as individual pages in one PDF file (no spreads). Pages must be in the correct order. For books and magazines with a spine, deliver two files: one for the content and one for the cover.',

            'finishing_title' => 'Finishing',
            'finishing_intro' => 'Special finishing options require separate delivery instructions.',
            'finishing_items' => [
                ['name' => 'Spot UV varnish', 'desc' => 'Glossy varnish layer on specific parts. Indicate the varnish area in a separate PDF file.'],
                ['name' => 'Foil printing', 'desc' => 'Gold or silver foil for a luxurious look. Deliver a separate file for the foil elements.'],
                ['name' => 'Die cutting', 'desc' => 'For custom shapes, deliver a cut line as a vector in a separate layer.'],
            ],

            'checklist_title' => 'Quick checklist',
            'checklist_items' => [
                'File format: PDF (preferred)',
                'Color mode: CMYK or PMS',
                'Resolution: minimum 150-300 DPI',
                'Bleed: 3 mm all around',
                'Safety margin: 3 mm from trim edge',
                'Text: converted to outlines',
                'Ink coverage: maximum 280-300%',
            ],

            'help_title' => 'Need help with your files?',
            'help_text' => 'Do you have questions about delivering your files? Our team is happy to help.',
            'help_cta' => 'Contact us',
        ],

        'de' => [
            'title' => 'Dateispezifikationen',
            'subtitle' => 'Liefern Sie Ihre Dateien korrekt für beste Druckergebnisse',
            'intro' => 'Für optimale Druckergebnisse müssen Ihre Dateien bestimmte technische Anforderungen erfüllen.',

            'formats_title' => 'Dateiformate',
            'formats_intro' => 'Wir akzeptieren verschiedene Dateiformate. PDF ist am besten geeignet.',
            'formats' => [
                ['name' => 'PDF', 'status' => 'recommended', 'desc' => 'Erhält Vektoren, Transparenzen und Layout.'],
                ['name' => 'TIFF / TIF', 'status' => 'ok', 'desc' => 'Geeignet für Fotos mit minimalem Detailverlust.'],
                ['name' => 'JPG / JPEG', 'status' => 'ok', 'desc' => 'Kompaktes Format für Fotos.'],
                ['name' => 'PNG', 'status' => 'ok', 'desc' => 'Geeignet für Bilder mit Transparenz.'],
            ],
            'formats_note' => 'Office-Dateien sind nicht für den Druck geeignet. Exportieren Sie diese zuerst als PDF.',

            'colors_title' => 'Farbsysteme',
            'colors_intro' => 'Farben auf einem Bildschirm werden anders dargestellt als im Druck.',
            'colors' => [
                ['name' => 'CMYK', 'full' => 'Cyan, Magenta, Yellow, Key', 'suitable' => true, 'desc' => 'Standardfarbsystem für professionellen Druck.'],
                ['name' => 'PMS', 'full' => 'Pantone Matching System', 'suitable' => true, 'desc' => 'Für exakte Markenfarben.'],
                ['name' => 'RGB', 'full' => 'Rot, Grün, Blau', 'suitable' => false, 'desc' => 'Für Bildschirme. Wird automatisch in CMYK konvertiert.'],
            ],

            'resolution_title' => 'Auflösung',
            'resolution_intro' => 'Die Auflösung bestimmt die Schärfe Ihres Drucks.',
            'resolutions' => [
                ['name' => 'Vektor', 'for' => 'Logos & Illustrationen', 'suitable' => true, 'note' => 'Unbegrenzt skalierbar'],
                ['name' => '300 DPI', 'for' => 'A3 und kleiner', 'suitable' => true, 'note' => 'Optimale Qualität'],
                ['name' => '150 DPI', 'for' => 'A2 und größer', 'suitable' => true, 'note' => 'Ausreichend für Großformat'],
                ['name' => '72 DPI', 'for' => 'Nur Web', 'suitable' => false, 'note' => 'Zu niedrig für Druck'],
            ],
            'resolution_note' => 'Die minimale Linienstärke beträgt 0,5 pt.',

            'bleed_title' => 'Beschnitt & Sicherheitsabstand',
            'bleed_intro' => 'Mit den richtigen Rändern vermeiden Sie weiße Kanten.',
            'bleed_items' => [
                ['name' => 'Beschnitt', 'value' => '3 mm', 'desc' => 'Lassen Sie Hintergründe 3 mm über das Endformat hinausgehen.'],
                ['name' => 'Sicherheitsabstand', 'value' => '3-5 mm', 'desc' => 'Platzieren Sie Text mindestens 3 mm vom Schnittrand.'],
            ],

            'fonts_title' => 'Text & Schriften',
            'fonts_text' => 'Um sicherzustellen, dass Ihre Schriften korrekt angezeigt werden, empfehlen wir, Text in Pfade umzuwandeln.',

            'ink_title' => 'Farbauftrag',
            'ink_intro' => 'Der Farbauftrag ist die Summe aller CMYK-Prozentsätze.',
            'ink_items' => [
                ['type' => 'Digitaldruck', 'max' => '300%'],
                ['type' => 'Offsetdruck', 'max' => '280%'],
                ['type' => 'Zeitungen', 'max' => '240%'],
                ['type' => 'Textil & Fahnen', 'max' => '200%'],
            ],
            'ink_note' => 'Der minimale Farbauftrag beträgt 10%.',

            'pages_title' => 'Mehrere Seiten',
            'pages_text' => 'Liefern Sie mehrseitige Dokumente als einzelne Seiten in einer PDF-Datei.',

            'finishing_title' => 'Veredelung',
            'finishing_intro' => 'Für spezielle Veredelungen gelten separate Anleitungen.',
            'finishing_items' => [
                ['name' => 'Spot-UV-Lack', 'desc' => 'Glänzende Lackschicht auf bestimmten Teilen.'],
                ['name' => 'Foliendruck', 'desc' => 'Gold- oder Silberfolie für einen luxuriösen Look.'],
                ['name' => 'Stanzen', 'desc' => 'Für individuelle Formen liefern Sie eine Schnittlinie als Vektor.'],
            ],

            'checklist_title' => 'Schnelle Checkliste',
            'checklist_items' => [
                'Dateiformat: PDF (bevorzugt)',
                'Farbmodus: CMYK oder PMS',
                'Auflösung: mindestens 150-300 DPI',
                'Beschnitt: 3 mm rundum',
                'Sicherheitsabstand: 3 mm vom Schnittrand',
                'Text: in Pfade umgewandelt',
                'Farbauftrag: maximal 280-300%',
            ],

            'help_title' => 'Hilfe bei Ihren Dateien?',
            'help_text' => 'Haben Sie Fragen zur Dateilieferung? Unser Team hilft Ihnen gerne.',
            'help_cta' => 'Kontaktieren Sie uns',
        ],

        'es' => [
            'title' => 'Especificaciones de archivos',
            'subtitle' => 'Entregue sus archivos correctamente para los mejores resultados',
            'intro' => 'Para obtener resultados óptimos, sus archivos deben cumplir ciertos requisitos técnicos.',

            'formats_title' => 'Formatos de archivo',
            'formats_intro' => 'Aceptamos varios formatos. PDF es el más adecuado.',
            'formats' => [
                ['name' => 'PDF', 'status' => 'recommended', 'desc' => 'Preserva vectores, transparencias y diseño.'],
                ['name' => 'TIFF / TIF', 'status' => 'ok', 'desc' => 'Adecuado para fotos.'],
                ['name' => 'JPG / JPEG', 'status' => 'ok', 'desc' => 'Formato compacto para fotos.'],
                ['name' => 'PNG', 'status' => 'ok', 'desc' => 'Adecuado para imágenes con transparencia.'],
            ],
            'formats_note' => 'Los archivos de Office no son adecuados para impresión.',

            'colors_title' => 'Sistemas de color',
            'colors_intro' => 'Los colores en pantalla se muestran de manera diferente que en impresión.',
            'colors' => [
                ['name' => 'CMYK', 'full' => 'Cian, Magenta, Amarillo, Negro', 'suitable' => true, 'desc' => 'Sistema estándar para impresión profesional.'],
                ['name' => 'PMS', 'full' => 'Pantone Matching System', 'suitable' => true, 'desc' => 'Para colores de marca exactos.'],
                ['name' => 'RGB', 'full' => 'Rojo, Verde, Azul', 'suitable' => false, 'desc' => 'Para pantallas. Se convierte automáticamente a CMYK.'],
            ],

            'resolution_title' => 'Resolución',
            'resolution_intro' => 'La resolución determina la nitidez de su impresión.',
            'resolutions' => [
                ['name' => 'Vector', 'for' => 'Logos e ilustraciones', 'suitable' => true, 'note' => 'Escalable infinitamente'],
                ['name' => '300 DPI', 'for' => 'A3 y menor', 'suitable' => true, 'note' => 'Calidad óptima'],
                ['name' => '150 DPI', 'for' => 'A2 y mayor', 'suitable' => true, 'note' => 'Suficiente para gran formato'],
                ['name' => '72 DPI', 'for' => 'Solo web', 'suitable' => false, 'note' => 'Muy bajo para impresión'],
            ],
            'resolution_note' => 'El grosor mínimo de línea es 0,5 pt.',

            'bleed_title' => 'Sangrado y margen de seguridad',
            'bleed_intro' => 'Con los márgenes correctos evita bordes blancos.',
            'bleed_items' => [
                ['name' => 'Sangrado', 'value' => '3 mm', 'desc' => 'Extienda los fondos 3 mm más allá del formato final.'],
                ['name' => 'Margen de seguridad', 'value' => '3-5 mm', 'desc' => 'Coloque texto al menos 3 mm del borde de corte.'],
            ],

            'fonts_title' => 'Texto y fuentes',
            'fonts_text' => 'Recomendamos convertir el texto a contornos para garantizar que las fuentes se muestren correctamente.',

            'ink_title' => 'Cobertura de tinta',
            'ink_intro' => 'La cobertura de tinta es el total de todos los porcentajes CMYK.',
            'ink_items' => [
                ['type' => 'Impresión digital', 'max' => '300%'],
                ['type' => 'Impresión offset', 'max' => '280%'],
                ['type' => 'Periódicos', 'max' => '240%'],
                ['type' => 'Textil y banderas', 'max' => '200%'],
            ],
            'ink_note' => 'La cobertura mínima es 10%.',

            'pages_title' => 'Múltiples páginas',
            'pages_text' => 'Entregue documentos de varias páginas como páginas individuales en un archivo PDF.',

            'finishing_title' => 'Acabados',
            'finishing_intro' => 'Los acabados especiales requieren instrucciones separadas.',
            'finishing_items' => [
                ['name' => 'Barniz UV selectivo', 'desc' => 'Capa de barniz brillante en partes específicas.'],
                ['name' => 'Impresión con foil', 'desc' => 'Foil dorado o plateado para un aspecto lujoso.'],
                ['name' => 'Troquelado', 'desc' => 'Para formas personalizadas, entregue una línea de corte como vector.'],
            ],

            'checklist_title' => 'Lista de verificación rápida',
            'checklist_items' => [
                'Formato: PDF (preferido)',
                'Modo de color: CMYK o PMS',
                'Resolución: mínimo 150-300 DPI',
                'Sangrado: 3 mm alrededor',
                'Margen de seguridad: 3 mm del borde',
                'Texto: convertido a contornos',
                'Cobertura de tinta: máximo 280-300%',
            ],

            'help_title' => '¿Necesita ayuda con sus archivos?',
            'help_text' => '¿Tiene preguntas sobre la entrega de archivos? Nuestro equipo está encantado de ayudar.',
            'help_cta' => 'Contáctenos',
        ],
    ];

    $c = $content[$locale] ?? $content['nl'];
    $contactSegment = match($locale) {
        'en' => 'contact',
        'de' => 'kontakt',
        'es' => 'contacto',
        default => 'contact',
    };
@endphp

<div>
    <section class="bg-white py-12 lg:py-16">
        <div class="mx-auto max-w-screen-xl px-4">
            {{-- Header --}}
            <div class="mb-12">
                <h1 class="mb-4 text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl">
                    {{ $c['title'] }}
                </h1>
                <p class="text-lg text-gray-600">
                    {{ $c['subtitle'] }}
                </p>
            </div>

            {{-- Intro --}}
            <div class="mb-12 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 p-8 text-white">
                <div class="flex items-start">
                    <div class="mr-4 flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="text-lg text-primary-100">{{ $c['intro'] }}</p>
                </div>
            </div>

            {{-- File Formats & Colors --}}
            <div class="mb-12 grid gap-8 lg:grid-cols-2">
                {{-- File Formats --}}
                <div>
                    <h2 class="mb-4 flex items-center text-xl font-bold text-gray-900">
                        <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        {{ $c['formats_title'] }}
                    </h2>
                    <p class="mb-4 text-gray-600">{{ $c['formats_intro'] }}</p>
                    <div class="space-y-3">
                        @foreach($c['formats'] as $format)
                            <div class="flex items-center rounded-lg border {{ $format['status'] === 'recommended' ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-white' }} p-4">
                                <div class="mr-4 flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $format['status'] === 'recommended' ? 'bg-green-100' : 'bg-gray-100' }}">
                                    @if($format['status'] === 'recommended')
                                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        {{ $format['name'] }}
                                        @if($format['status'] === 'recommended')
                                            <span class="ml-2 rounded bg-green-200 px-2 py-0.5 text-xs font-medium text-green-800">{{ $locale === 'nl' ? 'Aanbevolen' : ($locale === 'en' ? 'Recommended' : ($locale === 'de' ? 'Empfohlen' : 'Recomendado')) }}</span>
                                        @endif
                                    </h3>
                                    <p class="text-sm text-gray-600">{{ $format['desc'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                        <svg class="mr-1 inline h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ $c['formats_note'] }}
                    </p>
                </div>

                {{-- Colors --}}
                <div>
                    <h2 class="mb-4 flex items-center text-xl font-bold text-gray-900">
                        <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                        {{ $c['colors_title'] }}
                    </h2>
                    <p class="mb-4 text-gray-600">{{ $c['colors_intro'] }}</p>
                    <div class="space-y-3">
                        @foreach($c['colors'] as $color)
                            <div class="rounded-lg border {{ $color['suitable'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold {{ $color['suitable'] ? 'text-green-800' : 'text-red-800' }}">{{ $color['name'] }}</h3>
                                        <p class="text-xs text-gray-500">{{ $color['full'] }}</p>
                                    </div>
                                    @if($color['suitable'])
                                        <span class="flex items-center rounded-full bg-green-200 px-3 py-1 text-xs font-medium text-green-800">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            {{ $locale === 'nl' ? 'Geschikt' : ($locale === 'en' ? 'Suitable' : ($locale === 'de' ? 'Geeignet' : 'Adecuado')) }}
                                        </span>
                                    @else
                                        <span class="flex items-center rounded-full bg-red-200 px-3 py-1 text-xs font-medium text-red-800">
                                            <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                            {{ $locale === 'nl' ? 'Niet geschikt' : ($locale === 'en' ? 'Not suitable' : ($locale === 'de' ? 'Nicht geeignet' : 'No adecuado')) }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm text-gray-600">{{ $color['desc'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Resolution --}}
            <div class="mb-12">
                <h2 class="mb-4 flex items-center text-xl font-bold text-gray-900">
                    <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                    </svg>
                    {{ $c['resolution_title'] }}
                </h2>
                <p class="mb-4 text-gray-600">{{ $c['resolution_intro'] }}</p>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($c['resolutions'] as $res)
                        <div class="rounded-xl border {{ $res['suitable'] ? 'border-gray-200 bg-white' : 'border-red-200 bg-red-50' }} p-5 text-center shadow-sm">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full {{ $res['suitable'] ? 'bg-primary-100' : 'bg-red-100' }}">
                                @if($res['suitable'])
                                    <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                @else
                                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @endif
                            </div>
                            <h3 class="text-lg font-bold {{ $res['suitable'] ? 'text-gray-900' : 'text-red-700' }}">{{ $res['name'] }}</h3>
                            <p class="text-sm font-medium text-gray-500">{{ $res['for'] }}</p>
                            <p class="mt-2 text-xs text-gray-400">{{ $res['note'] }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-sm text-gray-500">{{ $c['resolution_note'] }}</p>
            </div>

            {{-- Bleed & Margins --}}
            <div class="mb-12 grid gap-8 md:grid-cols-2">
                @foreach($c['bleed_items'] as $item)
                    <div class="rounded-xl border border-primary-200 bg-primary-50 p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900">{{ $item['name'] }}</h3>
                            <span class="rounded-full bg-primary-600 px-4 py-2 text-lg font-bold text-white">{{ $item['value'] }}</span>
                        </div>
                        <p class="text-gray-600">{{ $item['desc'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Fonts --}}
            <div class="mb-12 rounded-xl border border-gray-200 bg-gray-50 p-6">
                <h2 class="mb-3 flex items-center text-xl font-bold text-gray-900">
                    <svg class="mr-3 h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                    {{ $c['fonts_title'] }}
                </h2>
                <p class="text-gray-700">{{ $c['fonts_text'] }}</p>
            </div>

            {{-- Ink Coverage --}}
            <div class="mb-12">
                <h2 class="mb-4 flex items-center text-xl font-bold text-gray-900">
                    <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    {{ $c['ink_title'] }}
                </h2>
                <p class="mb-4 text-gray-600">{{ $c['ink_intro'] }}</p>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 font-semibold text-gray-900">{{ $locale === 'nl' ? 'Type drukwerk' : ($locale === 'en' ? 'Print type' : ($locale === 'de' ? 'Druckart' : 'Tipo')) }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-900">{{ $locale === 'nl' ? 'Max. inktdekking' : ($locale === 'en' ? 'Max. ink coverage' : ($locale === 'de' ? 'Max. Farbauftrag' : 'Máx. cobertura')) }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($c['ink_items'] as $item)
                                <tr class="bg-white">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $item['type'] }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-primary-600">{{ $item['max'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-4 text-sm text-gray-500">{{ $c['ink_note'] }}</p>
            </div>

            {{-- Multiple Pages --}}
            <div class="mb-12 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-3 flex items-center text-xl font-bold text-gray-900">
                    <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    {{ $c['pages_title'] }}
                </h2>
                <p class="text-gray-700">{{ $c['pages_text'] }}</p>
            </div>

            {{-- Finishing --}}
            <div class="mb-12">
                <h2 class="mb-4 flex items-center text-xl font-bold text-gray-900">
                    <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    {{ $c['finishing_title'] }}
                </h2>
                <p class="mb-4 text-gray-600">{{ $c['finishing_intro'] }}</p>
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach($c['finishing_items'] as $item)
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <h3 class="mb-2 font-semibold text-gray-900">{{ $item['name'] }}</h3>
                            <p class="text-sm text-gray-600">{{ $item['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Checklist --}}
            <div class="mb-12 rounded-xl bg-gray-50 p-8">
                <h2 class="mb-6 text-center text-2xl font-bold text-gray-900">{{ $c['checklist_title'] }}</h2>
                <div class="mx-auto max-w-2xl">
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach($c['checklist_items'] as $item)
                            <div class="flex items-center rounded-lg bg-white px-4 py-3 shadow-sm">
                                <div class="mr-3 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-100">
                                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="text-sm text-gray-700">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Help CTA --}}
            <div class="rounded-xl bg-primary-600 p-8 text-center text-white">
                <h2 class="mb-2 text-2xl font-bold">{{ $c['help_title'] }}</h2>
                <p class="mb-6 text-primary-100">{{ $c['help_text'] }}</p>
                <a
                    href="/{{ $locale }}/{{ $contactSegment }}"
                    class="inline-flex items-center rounded-lg bg-white px-6 py-3 text-center font-medium text-primary-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-primary-300"
                >
                    {{ $c['help_cta'] }}
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
</div>
