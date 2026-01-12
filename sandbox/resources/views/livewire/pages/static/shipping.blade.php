@php
    $locale = app()->getLocale();

    $content = [
        'nl' => [
            'title' => 'Verzending & levertijden',
            'subtitle' => 'Snelle en betrouwbare levering van je drukwerk',
            'intro' => 'Bij Drukhoek zorgen we voor een vlotte afhandeling van je bestelling. De meeste producten worden binnen enkele werkdagen geleverd, zodat jij snel aan de slag kunt.',
            'delivery_title' => 'Levertijden',
            'delivery_intro' => 'Onze levertijden zijn afhankelijk van het type product:',
            'delivery_items' => [
                ['name' => 'Stickers & kleine prints', 'time' => '2-3 werkdagen'],
                ['name' => 'Spandoeken & banners', 'time' => '3-4 werkdagen'],
                ['name' => 'Roll-up banners', 'time' => '3-5 werkdagen'],
                ['name' => 'Reclameborden & dibond', 'time' => '4-5 werkdagen'],
                ['name' => 'Vlaggen & beachflags', 'time' => '5-7 werkdagen'],
                ['name' => 'Textielframes & displays', 'time' => '5-7 werkdagen'],
            ],
            'delivery_note' => 'De exacte levertijd zie je bij elk product tijdens het bestellen. Levertijden zijn in werkdagen en gelden na goedkeuring van je bestand.',
            'express_title' => 'Expreslevering',
            'express_text' => 'Haast? Voor veel producten bieden we expreslevering aan. Selecteer deze optie bij het afrekenen en ontvang je bestelling sneller. Expreslevering is beschikbaar tegen een meerprijs die je ziet in de checkout.',
            'shipping_title' => 'Verzendkosten',
            'shipping_items' => [
                ['destination' => 'Nederland', 'cost' => 'Vanaf € 6,95', 'free' => 'Gratis vanaf € 75,-'],
                ['destination' => 'België', 'cost' => 'Vanaf € 9,95', 'free' => 'Gratis vanaf € 100,-'],
                ['destination' => 'Duitsland', 'cost' => 'Vanaf € 12,95', 'free' => 'Gratis vanaf € 150,-'],
                ['destination' => 'Overig EU', 'cost' => 'Op aanvraag', 'free' => '-'],
            ],
            'shipping_note' => 'De exacte verzendkosten zijn afhankelijk van formaat en gewicht en worden berekend in je winkelwagen.',
            'carriers_title' => 'Bezorgdiensten',
            'carriers_text' => 'We werken samen met betrouwbare bezorgdiensten om je bestelling veilig te leveren:',
            'carriers' => ['PostNL', 'DHL', 'DPD'],
            'carriers_note' => 'Je ontvangt een track & trace code zodra je bestelling is verzonden, zodat je deze kunt volgen.',
            'tracking_title' => 'Bestelling volgen',
            'tracking_text' => 'Na verzending ontvang je een e-mail met de track & trace code. Hiermee kun je je pakket real-time volgen. Heb je een account? Dan vind je de status ook terug in je accountoverzicht.',
            'large_title' => 'Grote bestellingen',
            'large_text' => 'Voor grote of zware bestellingen kan levering via een transportbedrijf nodig zijn. We nemen dan contact met je op om een geschikt levermoment af te spreken. Zorg dat er iemand aanwezig is om de levering in ontvangst te nemen.',
            'questions_title' => 'Vragen over verzending?',
            'questions_text' => 'Heb je vragen over de levering van je bestelling? Neem gerust contact met ons op.',
            'contact' => 'Neem contact op',
        ],
        'en' => [
            'title' => 'Shipping & delivery',
            'subtitle' => 'Fast and reliable delivery of your print products',
            'intro' => 'At Drukhoek, we ensure smooth handling of your order. Most products are delivered within a few business days, so you can get started quickly.',
            'delivery_title' => 'Delivery times',
            'delivery_intro' => 'Our delivery times depend on the type of product:',
            'delivery_items' => [
                ['name' => 'Stickers & small prints', 'time' => '2-3 business days'],
                ['name' => 'Banners', 'time' => '3-4 business days'],
                ['name' => 'Roll-up banners', 'time' => '3-5 business days'],
                ['name' => 'Signs & dibond', 'time' => '4-5 business days'],
                ['name' => 'Flags & beachflags', 'time' => '5-7 business days'],
                ['name' => 'Textile frames & displays', 'time' => '5-7 business days'],
            ],
            'delivery_note' => 'You can see the exact delivery time for each product when ordering. Delivery times are in business days and apply after file approval.',
            'express_title' => 'Express delivery',
            'express_text' => 'In a hurry? We offer express delivery for many products. Select this option at checkout and receive your order faster. Express delivery is available at an additional cost shown at checkout.',
            'shipping_title' => 'Shipping costs',
            'shipping_items' => [
                ['destination' => 'Netherlands', 'cost' => 'From € 6.95', 'free' => 'Free from € 75'],
                ['destination' => 'Belgium', 'cost' => 'From € 9.95', 'free' => 'Free from € 100'],
                ['destination' => 'Germany', 'cost' => 'From € 12.95', 'free' => 'Free from € 150'],
                ['destination' => 'Other EU', 'cost' => 'On request', 'free' => '-'],
            ],
            'shipping_note' => 'Exact shipping costs depend on size and weight and are calculated in your cart.',
            'carriers_title' => 'Carriers',
            'carriers_text' => 'We work with reliable carriers to deliver your order safely:',
            'carriers' => ['PostNL', 'DHL', 'DPD'],
            'carriers_note' => "You'll receive a track & trace code once your order has been shipped.",
            'tracking_title' => 'Track your order',
            'tracking_text' => "After shipping, you'll receive an email with the track & trace code. This allows you to track your package in real-time. Have an account? You can also find the status in your account overview.",
            'large_title' => 'Large orders',
            'large_text' => "For large or heavy orders, delivery via a transport company may be necessary. We'll contact you to arrange a suitable delivery time. Please ensure someone is available to receive the delivery.",
            'questions_title' => 'Questions about shipping?',
            'questions_text' => "Have questions about your order's delivery? Feel free to contact us.",
            'contact' => 'Contact us',
        ],
        'de' => [
            'title' => 'Versand & Lieferung',
            'subtitle' => 'Schnelle und zuverlässige Lieferung Ihrer Druckprodukte',
            'intro' => 'Bei Drukhoek sorgen wir für eine reibungslose Abwicklung Ihrer Bestellung. Die meisten Produkte werden innerhalb weniger Werktage geliefert.',
            'delivery_title' => 'Lieferzeiten',
            'delivery_intro' => 'Unsere Lieferzeiten hängen vom Produkttyp ab:',
            'delivery_items' => [
                ['name' => 'Aufkleber & kleine Drucke', 'time' => '2-3 Werktage'],
                ['name' => 'Banner', 'time' => '3-4 Werktage'],
                ['name' => 'Roll-up Banner', 'time' => '3-5 Werktage'],
                ['name' => 'Schilder & Dibond', 'time' => '4-5 Werktage'],
                ['name' => 'Flaggen & Beachflags', 'time' => '5-7 Werktage'],
            ],
            'delivery_note' => 'Die genaue Lieferzeit sehen Sie bei jedem Produkt während der Bestellung.',
            'express_title' => 'Expresslieferung',
            'express_text' => 'Eilig? Für viele Produkte bieten wir Expresslieferung an. Wählen Sie diese Option an der Kasse.',
            'shipping_title' => 'Versandkosten',
            'shipping_items' => [
                ['destination' => 'Deutschland', 'cost' => 'Ab € 9,95', 'free' => 'Kostenlos ab € 100'],
                ['destination' => 'Niederlande', 'cost' => 'Ab € 9,95', 'free' => 'Kostenlos ab € 100'],
                ['destination' => 'Belgien', 'cost' => 'Ab € 9,95', 'free' => 'Kostenlos ab € 100'],
            ],
            'shipping_note' => 'Die genauen Versandkosten hängen von Größe und Gewicht ab.',
            'carriers_title' => 'Versanddienstleister',
            'carriers_text' => 'Wir arbeiten mit zuverlässigen Versanddienstleistern:',
            'carriers' => ['PostNL', 'DHL', 'DPD'],
            'carriers_note' => 'Sie erhalten eine Sendungsverfolgungsnummer, sobald Ihre Bestellung versandt wurde.',
            'tracking_title' => 'Bestellung verfolgen',
            'tracking_text' => 'Nach dem Versand erhalten Sie eine E-Mail mit der Sendungsverfolgungsnummer.',
            'large_title' => 'Große Bestellungen',
            'large_text' => 'Für große oder schwere Bestellungen kann eine Lieferung per Spedition erforderlich sein.',
            'questions_title' => 'Fragen zum Versand?',
            'questions_text' => 'Haben Sie Fragen zur Lieferung Ihrer Bestellung? Kontaktieren Sie uns.',
            'contact' => 'Kontaktieren Sie uns',
        ],
        'es' => [
            'title' => 'Envío y entrega',
            'subtitle' => 'Entrega rápida y fiable de sus productos impresos',
            'intro' => 'En Drukhoek, nos aseguramos de que su pedido se procese sin problemas. La mayoría de los productos se entregan en pocos días hábiles.',
            'delivery_title' => 'Tiempos de entrega',
            'delivery_intro' => 'Nuestros tiempos de entrega dependen del tipo de producto:',
            'delivery_items' => [
                ['name' => 'Pegatinas y pequeñas impresiones', 'time' => '2-3 días hábiles'],
                ['name' => 'Banners', 'time' => '3-4 días hábiles'],
                ['name' => 'Roll-up banners', 'time' => '3-5 días hábiles'],
            ],
            'delivery_note' => 'Puede ver el tiempo de entrega exacto para cada producto al realizar el pedido.',
            'express_title' => 'Entrega exprés',
            'express_text' => '¿Tiene prisa? Ofrecemos entrega exprés para muchos productos.',
            'shipping_title' => 'Costos de envío',
            'shipping_items' => [
                ['destination' => 'España', 'cost' => 'Desde € 14,95', 'free' => '-'],
            ],
            'shipping_note' => 'Los costos exactos de envío dependen del tamaño y peso.',
            'carriers_title' => 'Transportistas',
            'carriers_text' => 'Trabajamos con transportistas fiables:',
            'carriers' => ['DHL', 'UPS'],
            'carriers_note' => 'Recibirá un código de seguimiento cuando su pedido sea enviado.',
            'tracking_title' => 'Seguimiento de pedido',
            'tracking_text' => 'Después del envío, recibirá un correo electrónico con el código de seguimiento.',
            'large_title' => 'Pedidos grandes',
            'large_text' => 'Para pedidos grandes o pesados, puede ser necesaria la entrega por empresa de transporte.',
            'questions_title' => '¿Preguntas sobre el envío?',
            'questions_text' => '¿Tiene preguntas sobre la entrega de su pedido? Contáctenos.',
            'contact' => 'Contáctenos',
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
            <div class="mb-12 rounded-xl bg-primary-50 p-6">
                <p class="text-gray-700">{{ $c['intro'] }}</p>
            </div>

            <div class="grid gap-12 lg:grid-cols-2">
                {{-- Delivery Times --}}
                <div>
                    <h2 class="mb-4 flex items-center text-xl font-bold text-gray-900">
                        <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $c['delivery_title'] }}
                    </h2>
                    <p class="mb-4 text-gray-600">{{ $c['delivery_intro'] }}</p>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="w-full text-left text-sm">
                            <tbody class="divide-y divide-gray-200">
                                @foreach($c['delivery_items'] as $item)
                                    <tr class="bg-white">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item['name'] }}</td>
                                        <td class="px-4 py-3 text-right text-primary-600 font-semibold">{{ $item['time'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-sm text-gray-500">{{ $c['delivery_note'] }}</p>
                </div>

                {{-- Shipping Costs --}}
                <div>
                    <h2 class="mb-4 flex items-center text-xl font-bold text-gray-900">
                        <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ $c['shipping_title'] }}
                    </h2>
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 font-semibold text-gray-900">{{ $locale === 'nl' ? 'Bestemming' : ($locale === 'en' ? 'Destination' : ($locale === 'de' ? 'Ziel' : 'Destino')) }}</th>
                                    <th class="px-4 py-3 font-semibold text-gray-900">{{ $locale === 'nl' ? 'Kosten' : ($locale === 'en' ? 'Cost' : ($locale === 'de' ? 'Kosten' : 'Costo')) }}</th>
                                    <th class="px-4 py-3 font-semibold text-gray-900">{{ $locale === 'nl' ? 'Gratis verzending' : ($locale === 'en' ? 'Free shipping' : ($locale === 'de' ? 'Kostenloser Versand' : 'Envío gratis')) }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($c['shipping_items'] as $item)
                                    <tr class="bg-white">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item['destination'] }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $item['cost'] }}</td>
                                        <td class="px-4 py-3 text-green-600 font-medium">{{ $item['free'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-sm text-gray-500">{{ $c['shipping_note'] }}</p>
                </div>
            </div>

            {{-- Express Delivery --}}
            <div class="mt-12 rounded-xl border border-amber-200 bg-amber-50 p-6">
                <h2 class="mb-3 flex items-center text-xl font-bold text-gray-900">
                    <svg class="mr-3 h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    {{ $c['express_title'] }}
                </h2>
                <p class="text-gray-700">{{ $c['express_text'] }}</p>
            </div>

            {{-- Carriers & Tracking --}}
            <div class="mt-12 grid gap-8 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <h2 class="mb-4 flex items-center text-xl font-bold text-gray-900">
                        <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        {{ $c['carriers_title'] }}
                    </h2>
                    <p class="mb-4 text-gray-600">{{ $c['carriers_text'] }}</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($c['carriers'] as $carrier)
                            <span class="rounded-full bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700">{{ $carrier }}</span>
                        @endforeach
                    </div>
                    <p class="mt-4 text-sm text-gray-500">{{ $c['carriers_note'] }}</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6">
                    <h2 class="mb-4 flex items-center text-xl font-bold text-gray-900">
                        <svg class="mr-3 h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        {{ $c['tracking_title'] }}
                    </h2>
                    <p class="text-gray-600">{{ $c['tracking_text'] }}</p>
                </div>
            </div>

            {{-- Large Orders --}}
            <div class="mt-12 rounded-xl border border-gray-200 bg-gray-50 p-6">
                <h2 class="mb-3 flex items-center text-xl font-bold text-gray-900">
                    <svg class="mr-3 h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    {{ $c['large_title'] }}
                </h2>
                <p class="text-gray-700">{{ $c['large_text'] }}</p>
            </div>

            {{-- Contact CTA --}}
            <div class="mt-12 rounded-xl bg-primary-600 p-8 text-center text-white">
                <h2 class="mb-2 text-2xl font-bold">{{ $c['questions_title'] }}</h2>
                <p class="mb-6 text-primary-100">{{ $c['questions_text'] }}</p>
                <a
                    href="/{{ $locale }}/{{ $contactSegment }}"
                    class="inline-flex items-center rounded-lg bg-white px-6 py-3 text-center font-medium text-primary-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-primary-300"
                >
                    {{ $c['contact'] }}
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
</div>
