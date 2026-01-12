@php
    $locale = app()->getLocale();

    $content = [
        'nl' => [
            'title' => 'Veelgestelde vragen',
            'subtitle' => 'Vind snel antwoord op je vraag',
            'categories' => [
                'ordering' => [
                    'title' => 'Bestellen',
                    'questions' => [
                        [
                            'q' => 'Hoe plaats ik een bestelling?',
                            'a' => 'Kies het gewenste product, configureer de opties (formaat, materiaal, aantal), upload je bestand en voeg het toe aan je winkelwagen. In de checkout vul je je gegevens in en rond je de betaling af.',
                        ],
                        [
                            'q' => 'Kan ik mijn bestelling nog wijzigen of annuleren?',
                            'a' => 'Omdat we direct met de productie starten na ontvangst van je bestelling, is annuleren of wijzigen helaas niet mogelijk. Controleer daarom je bestelling goed voordat je afrekent.',
                        ],
                        [
                            'q' => 'Krijg ik korting bij grotere aantallen?',
                            'a' => 'Ja! Onze prijzen zijn gestaffeld: hoe meer je bestelt, hoe lager de prijs per stuk. Je ziet de staffelkorting direct bij het configureren van je product.',
                        ],
                        [
                            'q' => 'Kan ik een proefdruk of sample aanvragen?',
                            'a' => 'Voor de meeste producten kun je een sample aanvragen. Neem contact met ons op via het contactformulier en we bespreken de mogelijkheden.',
                        ],
                    ],
                ],
                'files' => [
                    'title' => 'Bestanden & ontwerp',
                    'questions' => [
                        [
                            'q' => 'Welke bestandsformaten accepteren jullie?',
                            'a' => 'Wij accepteren PDF, JPG, PNG en TIFF bestanden. Voor het beste resultaat raden we PDF aan met minimaal 150 DPI voor grote prints en 300 DPI voor kleinere producten.',
                        ],
                        [
                            'q' => 'Ik heb geen ontwerp. Kunnen jullie dat voor me maken?',
                            'a' => 'Jazeker! Met onze online designer kun je eenvoudig zelf een ontwerp maken. Je kunt ook je eigen logo en afbeeldingen uploaden en combineren met onze templates.',
                        ],
                        [
                            'q' => 'Wat als mijn bestand niet goed is?',
                            'a' => 'Onze systemen controleren je bestand automatisch op resolutie en afloop. Als er iets niet klopt, krijg je direct een melding met uitleg hoe je dit kunt oplossen.',
                        ],
                    ],
                ],
                'shipping' => [
                    'title' => 'Verzending & levering',
                    'questions' => [
                        [
                            'q' => 'Wat zijn de levertijden?',
                            'a' => 'De meeste producten leveren we binnen 2-5 werkdagen. Bij elk product zie je de exacte levertijd. Heb je spoed? Kies dan voor expreslevering bij de checkout.',
                        ],
                        [
                            'q' => 'Wat zijn de verzendkosten?',
                            'a' => 'De verzendkosten zijn afhankelijk van het formaat en gewicht van je bestelling. Je ziet de exacte kosten in je winkelwagen voordat je afrekent. Vanaf een bepaald bedrag is verzending gratis.',
                        ],
                        [
                            'q' => 'Kan ik mijn bestelling volgen?',
                            'a' => 'Ja, zodra je bestelling is verzonden ontvang je een e-mail met een track & trace code. Hiermee kun je je pakket volgen.',
                        ],
                        [
                            'q' => 'Leveren jullie ook in België en Duitsland?',
                            'a' => 'Ja, we leveren in heel Nederland, België, Duitsland en andere EU-landen. De verzendkosten en levertijden kunnen per land verschillen.',
                        ],
                    ],
                ],
                'payment' => [
                    'title' => 'Betaling',
                    'questions' => [
                        [
                            'q' => 'Welke betaalmethodes accepteren jullie?',
                            'a' => 'Je kunt betalen met iDEAL, Bancontact, creditcard (Visa, Mastercard), PayPal en voor zakelijke klanten ook op factuur.',
                        ],
                        [
                            'q' => 'Is betalen op factuur mogelijk?',
                            'a' => 'Voor zakelijke klanten met een KvK-nummer is betalen op factuur mogelijk. Maak een zakelijk account aan en vraag deze optie aan.',
                        ],
                        [
                            'q' => 'Krijg ik een factuur?',
                            'a' => 'Ja, na elke bestelling ontvang je automatisch een factuur per e-mail. Je kunt al je facturen ook terugvinden in je account.',
                        ],
                    ],
                ],
                'returns' => [
                    'title' => 'Retourneren',
                    'questions' => [
                        [
                            'q' => 'Kan ik mijn bestelling retourneren?',
                            'a' => 'Omdat al onze producten op maat worden gemaakt met jouw ontwerp, kunnen we deze helaas niet terugnemen. Is er iets mis met je bestelling? Neem dan contact met ons op.',
                        ],
                        [
                            'q' => 'Wat als mijn bestelling beschadigd aankomt?',
                            'a' => 'Meld transportschade binnen 48 uur via ons contactformulier met foto\'s van de schade. We zorgen dan zo snel mogelijk voor een oplossing.',
                        ],
                        [
                            'q' => 'Er klopt iets niet aan mijn bestelling. Wat nu?',
                            'a' => 'Neem direct contact met ons op. Stuur foto\'s mee van het probleem. Als de fout bij ons ligt, zorgen we kosteloos voor een nieuwe levering.',
                        ],
                    ],
                ],
            ],
            'still_questions' => 'Staat je vraag er niet bij?',
            'contact_us' => 'Neem contact op',
            'contact_text' => 'Ons team helpt je graag verder met al je vragen.',
        ],
        'en' => [
            'title' => 'Frequently Asked Questions',
            'subtitle' => 'Find answers to common questions',
            'categories' => [
                'ordering' => [
                    'title' => 'Ordering',
                    'questions' => [
                        [
                            'q' => 'How do I place an order?',
                            'a' => 'Choose your desired product, configure the options (size, material, quantity), upload your file and add it to your cart. At checkout, fill in your details and complete the payment.',
                        ],
                        [
                            'q' => 'Can I modify or cancel my order?',
                            'a' => 'Because we start production immediately after receiving your order, cancellation or modification is unfortunately not possible. Please check your order carefully before checkout.',
                        ],
                        [
                            'q' => 'Do I get a discount for larger quantities?',
                            'a' => 'Yes! Our prices are tiered: the more you order, the lower the price per piece. You can see the volume discount directly when configuring your product.',
                        ],
                        [
                            'q' => 'Can I request a proof or sample?',
                            'a' => 'For most products, you can request a sample. Contact us via the contact form and we will discuss the possibilities.',
                        ],
                    ],
                ],
                'files' => [
                    'title' => 'Files & design',
                    'questions' => [
                        [
                            'q' => 'What file formats do you accept?',
                            'a' => 'We accept PDF, JPG, PNG and TIFF files. For best results, we recommend PDF with at least 150 DPI for large prints and 300 DPI for smaller products.',
                        ],
                        [
                            'q' => "I don't have a design. Can you create one for me?",
                            'a' => 'Absolutely! With our online designer, you can easily create your own design. You can also upload your own logo and images and combine them with our templates.',
                        ],
                        [
                            'q' => 'What if my file is not correct?',
                            'a' => "Our systems automatically check your file for resolution and bleed. If something isn't right, you'll immediately receive a notification explaining how to fix it.",
                        ],
                    ],
                ],
                'shipping' => [
                    'title' => 'Shipping & delivery',
                    'questions' => [
                        [
                            'q' => 'What are the delivery times?',
                            'a' => 'Most products are delivered within 2-5 business days. You can see the exact delivery time for each product. In a hurry? Choose express delivery at checkout.',
                        ],
                        [
                            'q' => 'What are the shipping costs?',
                            'a' => 'Shipping costs depend on the size and weight of your order. You can see the exact costs in your cart before checkout. Above a certain amount, shipping is free.',
                        ],
                        [
                            'q' => 'Can I track my order?',
                            'a' => "Yes, once your order has been shipped, you'll receive an email with a track & trace code to follow your package.",
                        ],
                        [
                            'q' => 'Do you deliver to Belgium and Germany?',
                            'a' => 'Yes, we deliver throughout the Netherlands, Belgium, Germany and other EU countries. Shipping costs and delivery times may vary by country.',
                        ],
                    ],
                ],
                'payment' => [
                    'title' => 'Payment',
                    'questions' => [
                        [
                            'q' => 'What payment methods do you accept?',
                            'a' => 'You can pay with iDEAL, Bancontact, credit card (Visa, Mastercard), PayPal and for business customers also by invoice.',
                        ],
                        [
                            'q' => 'Is payment by invoice possible?',
                            'a' => 'For business customers with a chamber of commerce number, payment by invoice is possible. Create a business account and request this option.',
                        ],
                        [
                            'q' => 'Will I receive an invoice?',
                            'a' => "Yes, after each order you'll automatically receive an invoice by email. You can also find all your invoices in your account.",
                        ],
                    ],
                ],
                'returns' => [
                    'title' => 'Returns',
                    'questions' => [
                        [
                            'q' => 'Can I return my order?',
                            'a' => "Because all our products are custom made with your design, we unfortunately cannot take them back. Is something wrong with your order? Please contact us.",
                        ],
                        [
                            'q' => 'What if my order arrives damaged?',
                            'a' => "Report shipping damage within 48 hours via our contact form with photos of the damage. We'll provide a solution as quickly as possible.",
                        ],
                        [
                            'q' => "Something's wrong with my order. What now?",
                            'a' => "Contact us immediately. Include photos of the problem. If the error is on our end, we'll provide a free replacement.",
                        ],
                    ],
                ],
            ],
            'still_questions' => "Can't find your answer?",
            'contact_us' => 'Contact us',
            'contact_text' => 'Our team is happy to help you with all your questions.',
        ],
        'de' => [
            'title' => 'Häufig gestellte Fragen',
            'subtitle' => 'Finden Sie schnell Antworten auf Ihre Fragen',
            'categories' => [
                'ordering' => [
                    'title' => 'Bestellen',
                    'questions' => [
                        [
                            'q' => 'Wie gebe ich eine Bestellung auf?',
                            'a' => 'Wählen Sie das gewünschte Produkt, konfigurieren Sie die Optionen (Größe, Material, Menge), laden Sie Ihre Datei hoch und legen Sie sie in den Warenkorb. An der Kasse geben Sie Ihre Daten ein und schließen die Zahlung ab.',
                        ],
                        [
                            'q' => 'Kann ich meine Bestellung ändern oder stornieren?',
                            'a' => 'Da wir sofort nach Eingang Ihrer Bestellung mit der Produktion beginnen, ist eine Stornierung oder Änderung leider nicht möglich. Bitte überprüfen Sie Ihre Bestellung sorgfältig vor dem Checkout.',
                        ],
                    ],
                ],
                'shipping' => [
                    'title' => 'Versand & Lieferung',
                    'questions' => [
                        [
                            'q' => 'Wie sind die Lieferzeiten?',
                            'a' => 'Die meisten Produkte werden innerhalb von 2-5 Werktagen geliefert. Bei jedem Produkt sehen Sie die genaue Lieferzeit.',
                        ],
                        [
                            'q' => 'Was kostet der Versand?',
                            'a' => 'Die Versandkosten hängen von Größe und Gewicht Ihrer Bestellung ab. Sie sehen die genauen Kosten in Ihrem Warenkorb.',
                        ],
                    ],
                ],
                'payment' => [
                    'title' => 'Zahlung',
                    'questions' => [
                        [
                            'q' => 'Welche Zahlungsmethoden akzeptieren Sie?',
                            'a' => 'Sie können mit iDEAL, Bancontact, Kreditkarte (Visa, Mastercard), PayPal und für Geschäftskunden auch auf Rechnung bezahlen.',
                        ],
                    ],
                ],
            ],
            'still_questions' => 'Ihre Frage nicht gefunden?',
            'contact_us' => 'Kontaktieren Sie uns',
            'contact_text' => 'Unser Team hilft Ihnen gerne bei allen Fragen weiter.',
        ],
        'es' => [
            'title' => 'Preguntas frecuentes',
            'subtitle' => 'Encuentre respuestas a preguntas comunes',
            'categories' => [
                'ordering' => [
                    'title' => 'Pedidos',
                    'questions' => [
                        [
                            'q' => '¿Cómo hago un pedido?',
                            'a' => 'Elija el producto deseado, configure las opciones (tamaño, material, cantidad), suba su archivo y añádalo al carrito. En el checkout, complete sus datos y finalice el pago.',
                        ],
                    ],
                ],
                'shipping' => [
                    'title' => 'Envío y entrega',
                    'questions' => [
                        [
                            'q' => '¿Cuáles son los tiempos de entrega?',
                            'a' => 'La mayoría de los productos se entregan en 2-5 días hábiles. Puede ver el tiempo de entrega exacto para cada producto.',
                        ],
                    ],
                ],
            ],
            'still_questions' => '¿No encuentra su respuesta?',
            'contact_us' => 'Contáctenos',
            'contact_text' => 'Nuestro equipo estará encantado de ayudarle con todas sus preguntas.',
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
            <div class="mb-12 text-center">
                <h1 class="mb-4 text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl">
                    {{ $c['title'] }}
                </h1>
                <p class="text-lg text-gray-600">
                    {{ $c['subtitle'] }}
                </p>
            </div>

            {{-- FAQ Categories --}}
            <div class="grid gap-8 lg:grid-cols-2">
                @foreach($c['categories'] as $categoryKey => $category)
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="mb-6 flex items-center text-xl font-bold text-gray-900">
                            @switch($categoryKey)
                                @case('ordering')
                                    <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    @break
                                @case('files')
                                    <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    @break
                                @case('shipping')
                                    <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                    @break
                                @case('payment')
                                    <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    @break
                                @case('returns')
                                    <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                                    </svg>
                                    @break
                            @endswitch
                            {{ $category['title'] }}
                        </h2>

                        <div class="space-y-4" x-data="{ open: null }">
                            @foreach($category['questions'] as $index => $qa)
                                <div class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                                    <button
                                        @click="open = open === {{ $index }} ? null : {{ $index }}"
                                        class="flex w-full items-center justify-between text-left"
                                    >
                                        <span class="font-medium text-gray-900">{{ $qa['q'] }}</span>
                                        <svg
                                            class="ml-2 h-5 w-5 shrink-0 text-gray-500 transition-transform"
                                            :class="{ 'rotate-180': open === {{ $index }} }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div
                                        x-show="open === {{ $index }}"
                                        x-collapse
                                        class="mt-3 text-gray-600"
                                    >
                                        {{ $qa['a'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Contact CTA --}}
            <div class="mt-12 rounded-xl bg-gray-50 p-8 text-center">
                <h2 class="mb-2 text-2xl font-bold text-gray-900">{{ $c['still_questions'] }}</h2>
                <p class="mb-6 text-gray-600">{{ $c['contact_text'] }}</p>
                <a
                    href="/{{ $locale }}/{{ $contactSegment }}"
                    class="inline-flex items-center rounded-lg bg-primary-600 px-6 py-3 text-center font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300"
                >
                    {{ $c['contact_us'] }}
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
</div>
