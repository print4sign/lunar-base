@php
    $locale = app()->getLocale();

    $content = [
        'nl' => [
            'title' => 'Privacybeleid',
            'last_updated' => 'Laatst bijgewerkt',
            'date' => '1 januari 2025',
            'intro' => 'Drukhoek B.V. respecteert de privacy van alle gebruikers van haar website en zorgt ervoor dat de persoonlijke informatie die je ons verschaft vertrouwelijk wordt behandeld. Dit privacybeleid is van toepassing op alle diensten van Drukhoek.',
            'sections' => [
                [
                    'title' => 'Welke gegevens verzamelen wij?',
                    'content' => 'Wij verzamelen de volgende persoonsgegevens:
                    <ul class="list-disc pl-6 mt-2 space-y-1">
                        <li>Naam en contactgegevens (e-mailadres, telefoonnummer, adres)</li>
                        <li>Bedrijfsgegevens (bedrijfsnaam, KvK-nummer, BTW-nummer)</li>
                        <li>Bestelgegevens en orderhistorie</li>
                        <li>Betaalgegevens</li>
                        <li>Communicatie met onze klantenservice</li>
                        <li>Websitegebruik via cookies (zie ons cookiebeleid)</li>
                    </ul>',
                ],
                [
                    'title' => 'Waarom verzamelen wij deze gegevens?',
                    'content' => 'Wij gebruiken je gegevens voor:
                    <ul class="list-disc pl-6 mt-2 space-y-1">
                        <li>Het uitvoeren van je bestelling</li>
                        <li>Het versturen van facturen en orderbevestigingen</li>
                        <li>Communicatie over je bestelling</li>
                        <li>Het verbeteren van onze dienstverlening</li>
                        <li>Het versturen van nieuwsbrieven (alleen met jouw toestemming)</li>
                        <li>Het voldoen aan wettelijke verplichtingen</li>
                    </ul>',
                ],
                [
                    'title' => 'Rechtsgrond voor verwerking',
                    'content' => 'Wij verwerken je gegevens op basis van:
                    <ul class="list-disc pl-6 mt-2 space-y-1">
                        <li><strong>Uitvoering van de overeenkomst:</strong> om je bestelling te kunnen verwerken en leveren.</li>
                        <li><strong>Wettelijke verplichting:</strong> zoals het bewaren van facturen voor de belastingdienst.</li>
                        <li><strong>Toestemming:</strong> voor het versturen van marketingcommunicatie.</li>
                        <li><strong>Gerechtvaardigd belang:</strong> voor het verbeteren van onze dienstverlening.</li>
                    </ul>',
                ],
                [
                    'title' => 'Hoe lang bewaren wij je gegevens?',
                    'content' => 'Wij bewaren je gegevens niet langer dan nodig voor de doeleinden waarvoor ze zijn verzameld:
                    <ul class="list-disc pl-6 mt-2 space-y-1">
                        <li>Accountgegevens: zolang je account actief is</li>
                        <li>Bestelgegevens: 7 jaar (wettelijke bewaarplicht)</li>
                        <li>Communicatie: 2 jaar na laatste contact</li>
                    </ul>',
                ],
                [
                    'title' => 'Delen met derden',
                    'content' => 'Wij delen je gegevens alleen met derden wanneer dit nodig is voor onze dienstverlening:
                    <ul class="list-disc pl-6 mt-2 space-y-1">
                        <li>Bezorgdiensten (voor levering van je bestelling)</li>
                        <li>Betalingsproviders (voor het verwerken van betalingen)</li>
                        <li>Hostingproviders (voor het hosten van onze website)</li>
                    </ul>
                    Wij verkopen je gegevens nooit aan derden.',
                ],
                [
                    'title' => 'Jouw rechten',
                    'content' => 'Je hebt de volgende rechten:
                    <ul class="list-disc pl-6 mt-2 space-y-1">
                        <li><strong>Inzage:</strong> je kunt opvragen welke gegevens wij van je hebben.</li>
                        <li><strong>Correctie:</strong> je kunt onjuiste gegevens laten aanpassen.</li>
                        <li><strong>Verwijdering:</strong> je kunt verzoeken om verwijdering van je gegevens.</li>
                        <li><strong>Bezwaar:</strong> je kunt bezwaar maken tegen bepaalde verwerkingen.</li>
                        <li><strong>Dataportabiliteit:</strong> je kunt je gegevens opvragen in een overdraagbaar formaat.</li>
                    </ul>
                    Om gebruik te maken van deze rechten kun je contact met ons opnemen.',
                ],
                [
                    'title' => 'Beveiliging',
                    'content' => 'Wij nemen de bescherming van je gegevens serieus. We nemen passende technische en organisatorische maatregelen om misbruik, verlies, onbevoegde toegang en andere ongewenste handelingen tegen te gaan. Onze website maakt gebruik van een SSL-certificaat voor een beveiligde verbinding.',
                ],
                [
                    'title' => 'Klachten',
                    'content' => 'Als je een klacht hebt over de verwerking van je persoonsgegevens, neem dan contact met ons op. Je hebt ook het recht om een klacht in te dienen bij de Autoriteit Persoonsgegevens.',
                ],
            ],
            'contact_title' => 'Contact',
            'contact_text' => 'Voor vragen over dit privacybeleid kun je contact met ons opnemen via:',
            'contact_email' => 'privacy@drukhoek.nl',
            'contact_button' => 'Neem contact op',
        ],
        'en' => [
            'title' => 'Privacy Policy',
            'last_updated' => 'Last updated',
            'date' => 'January 1, 2025',
            'intro' => 'Drukhoek B.V. respects the privacy of all users of its website and ensures that the personal information you provide is treated confidentially. This privacy policy applies to all services of Drukhoek.',
            'sections' => [
                [
                    'title' => 'What data do we collect?',
                    'content' => 'We collect the following personal data:
                    <ul class="list-disc pl-6 mt-2 space-y-1">
                        <li>Name and contact details (email address, phone number, address)</li>
                        <li>Company data (company name, registration number, VAT number)</li>
                        <li>Order data and order history</li>
                        <li>Payment data</li>
                        <li>Communication with our customer service</li>
                        <li>Website usage via cookies (see our cookie policy)</li>
                    </ul>',
                ],
                [
                    'title' => 'Why do we collect this data?',
                    'content' => 'We use your data for:
                    <ul class="list-disc pl-6 mt-2 space-y-1">
                        <li>Processing your order</li>
                        <li>Sending invoices and order confirmations</li>
                        <li>Communication about your order</li>
                        <li>Improving our services</li>
                        <li>Sending newsletters (only with your consent)</li>
                        <li>Complying with legal obligations</li>
                    </ul>',
                ],
                [
                    'title' => 'Legal basis for processing',
                    'content' => 'We process your data based on contract execution, legal obligation, consent, and legitimate interest.',
                ],
                [
                    'title' => 'How long do we keep your data?',
                    'content' => 'We do not keep your data longer than necessary. Account data is kept while your account is active. Order data is kept for 7 years (legal requirement).',
                ],
                [
                    'title' => 'Sharing with third parties',
                    'content' => 'We only share your data with third parties when necessary for our services: delivery services, payment providers, and hosting providers. We never sell your data.',
                ],
                [
                    'title' => 'Your rights',
                    'content' => 'You have the right to access, correction, deletion, objection, and data portability. Contact us to exercise these rights.',
                ],
                [
                    'title' => 'Security',
                    'content' => 'We take the protection of your data seriously with appropriate technical and organizational measures. Our website uses an SSL certificate for a secure connection.',
                ],
                [
                    'title' => 'Complaints',
                    'content' => 'If you have a complaint about the processing of your personal data, please contact us. You also have the right to file a complaint with the Data Protection Authority.',
                ],
            ],
            'contact_title' => 'Contact',
            'contact_text' => 'For questions about this privacy policy, you can contact us at:',
            'contact_email' => 'privacy@drukhoek.nl',
            'contact_button' => 'Contact us',
        ],
        'de' => [
            'title' => 'Datenschutzerklärung',
            'last_updated' => 'Zuletzt aktualisiert',
            'date' => '1. Januar 2025',
            'intro' => 'Drukhoek B.V. respektiert die Privatsphäre aller Nutzer seiner Website und stellt sicher, dass die persönlichen Informationen, die Sie uns mitteilen, vertraulich behandelt werden.',
            'sections' => [
                ['title' => 'Welche Daten erheben wir?', 'content' => 'Wir erheben Name, Kontaktdaten, Bestelldaten, Zahlungsdaten und Website-Nutzung.'],
                ['title' => 'Warum erheben wir diese Daten?', 'content' => 'Für die Bestellabwicklung, Rechnungen, Kommunikation und Serviceverbesserung.'],
                ['title' => 'Ihre Rechte', 'content' => 'Sie haben das Recht auf Auskunft, Berichtigung, Löschung, Widerspruch und Datenübertragbarkeit.'],
                ['title' => 'Sicherheit', 'content' => 'Wir nehmen den Schutz Ihrer Daten ernst mit geeigneten technischen und organisatorischen Maßnahmen.'],
            ],
            'contact_title' => 'Kontakt',
            'contact_text' => 'Für Fragen zu dieser Datenschutzerklärung kontaktieren Sie uns unter:',
            'contact_email' => 'privacy@drukhoek.nl',
            'contact_button' => 'Kontaktieren Sie uns',
        ],
        'es' => [
            'title' => 'Política de privacidad',
            'last_updated' => 'Última actualización',
            'date' => '1 de enero de 2025',
            'intro' => 'Drukhoek B.V. respeta la privacidad de todos los usuarios de su sitio web y garantiza que la información personal que nos proporciona sea tratada de forma confidencial.',
            'sections' => [
                ['title' => '¿Qué datos recopilamos?', 'content' => 'Recopilamos nombre, datos de contacto, datos de pedidos, datos de pago y uso del sitio web.'],
                ['title' => '¿Por qué recopilamos estos datos?', 'content' => 'Para procesar pedidos, enviar facturas, comunicación y mejorar nuestros servicios.'],
                ['title' => 'Sus derechos', 'content' => 'Tiene derecho a acceso, rectificación, supresión, oposición y portabilidad de datos.'],
                ['title' => 'Seguridad', 'content' => 'Tomamos en serio la protección de sus datos con medidas técnicas y organizativas apropiadas.'],
            ],
            'contact_title' => 'Contacto',
            'contact_text' => 'Para preguntas sobre esta política de privacidad, contáctenos en:',
            'contact_email' => 'privacy@drukhoek.nl',
            'contact_button' => 'Contáctenos',
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
            <div class="mb-8">
                <h1 class="mb-2 text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl">
                    {{ $c['title'] }}
                </h1>
                <p class="text-sm text-gray-500">
                    {{ $c['last_updated'] }}: {{ $c['date'] }}
                </p>
            </div>

            {{-- Intro --}}
            <div class="mb-8 rounded-lg bg-blue-50 border border-blue-200 p-6">
                <p class="text-gray-700">{{ $c['intro'] }}</p>
            </div>

            {{-- Content --}}
            <div class="space-y-6">
                @foreach($c['sections'] as $section)
                    <div class="rounded-lg border border-gray-200 bg-white p-6">
                        <h2 class="mb-4 text-xl font-bold text-gray-900">{{ $section['title'] }}</h2>
                        <div class="text-gray-600">{!! $section['content'] !!}</div>
                    </div>
                @endforeach
            </div>

            {{-- Contact --}}
            <div class="mt-12 rounded-xl bg-gray-50 p-8">
                <h2 class="mb-4 text-xl font-bold text-gray-900">{{ $c['contact_title'] }}</h2>
                <p class="mb-2 text-gray-600">{{ $c['contact_text'] }}</p>
                <p class="mb-6">
                    <a href="mailto:{{ $c['contact_email'] }}" class="text-primary-600 hover:underline font-medium">
                        {{ $c['contact_email'] }}
                    </a>
                </p>
                <a
                    href="/{{ $locale }}/{{ $contactSegment }}"
                    class="inline-flex items-center rounded-lg bg-primary-600 px-6 py-3 text-center font-medium text-white hover:bg-primary-700"
                >
                    {{ $c['contact_button'] }}
                </a>
            </div>
        </div>
    </section>
</div>
