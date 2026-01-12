@php
    $locale = app()->getLocale();

    $content = [
        'nl' => [
            'title' => 'Werken bij Drukhoek',
            'subtitle' => 'Word onderdeel van ons team',
            'intro' => 'Bij Drukhoek werken we met een enthousiast team aan hoogwaardige drukwerkoplossingen. We zijn altijd op zoek naar gemotiveerde mensen die willen bijdragen aan onze groei.',
            'why_title' => 'Waarom werken bij Drukhoek?',
            'why_items' => [
                ['title' => 'Dynamische omgeving', 'text' => 'Werk in een snelgroeiend bedrijf waar elke dag anders is.'],
                ['title' => 'Doorgroeimogelijkheden', 'text' => 'We investeren in de ontwikkeling van onze medewerkers.'],
                ['title' => 'Fijne sfeer', 'text' => 'Een informele werksfeer met leuke collega\'s.'],
                ['title' => 'Moderne technologie', 'text' => 'Werk met de nieuwste productie- en printtechnieken.'],
            ],
            'vacancies_title' => 'Openstaande vacatures',
            'no_vacancies' => 'Op dit moment hebben we geen openstaande vacatures. Houd deze pagina in de gaten of stuur een open sollicitatie.',
            'open_application_title' => 'Open sollicitatie',
            'open_application_text' => 'Zie je geen passende vacature maar wil je toch graag bij ons werken? Stuur dan een open sollicitatie met je CV en motivatie.',
            'contact_button' => 'Stuur je sollicitatie',
        ],
        'en' => [
            'title' => 'Careers at Drukhoek',
            'subtitle' => 'Join our team',
            'intro' => 'At Drukhoek, we work with an enthusiastic team on high-quality print solutions. We are always looking for motivated people who want to contribute to our growth.',
            'why_title' => 'Why work at Drukhoek?',
            'why_items' => [
                ['title' => 'Dynamic environment', 'text' => 'Work in a fast-growing company where every day is different.'],
                ['title' => 'Growth opportunities', 'text' => 'We invest in the development of our employees.'],
                ['title' => 'Great atmosphere', 'text' => 'An informal work atmosphere with great colleagues.'],
                ['title' => 'Modern technology', 'text' => 'Work with the latest production and printing techniques.'],
            ],
            'vacancies_title' => 'Current vacancies',
            'no_vacancies' => 'We currently have no open positions. Keep an eye on this page or send an open application.',
            'open_application_title' => 'Open application',
            'open_application_text' => "Don't see a suitable vacancy but would you like to work with us? Send an open application with your CV and motivation.",
            'contact_button' => 'Send your application',
        ],
        'de' => [
            'title' => 'Karriere bei Drukhoek',
            'subtitle' => 'Werden Sie Teil unseres Teams',
            'intro' => 'Bei Drukhoek arbeiten wir mit einem engagierten Team an hochwertigen Drucklösungen.',
            'why_title' => 'Warum bei Drukhoek arbeiten?',
            'why_items' => [
                ['title' => 'Dynamisches Umfeld', 'text' => 'Arbeiten Sie in einem schnell wachsenden Unternehmen.'],
                ['title' => 'Wachstumsmöglichkeiten', 'text' => 'Wir investieren in die Entwicklung unserer Mitarbeiter.'],
                ['title' => 'Tolle Atmosphäre', 'text' => 'Eine informelle Arbeitsatmosphäre mit tollen Kollegen.'],
                ['title' => 'Moderne Technologie', 'text' => 'Arbeiten Sie mit den neuesten Drucktechniken.'],
            ],
            'vacancies_title' => 'Aktuelle Stellenangebote',
            'no_vacancies' => 'Derzeit haben wir keine offenen Stellen. Behalten Sie diese Seite im Auge oder senden Sie eine Initiativbewerbung.',
            'open_application_title' => 'Initiativbewerbung',
            'open_application_text' => 'Keine passende Stelle gefunden? Senden Sie uns eine Initiativbewerbung.',
            'contact_button' => 'Bewerbung senden',
        ],
        'es' => [
            'title' => 'Trabaja con nosotros',
            'subtitle' => 'Únete a nuestro equipo',
            'intro' => 'En Drukhoek trabajamos con un equipo entusiasta en soluciones de impresión de alta calidad.',
            'why_title' => '¿Por qué trabajar en Drukhoek?',
            'why_items' => [
                ['title' => 'Entorno dinámico', 'text' => 'Trabaje en una empresa de rápido crecimiento.'],
                ['title' => 'Oportunidades de crecimiento', 'text' => 'Invertimos en el desarrollo de nuestros empleados.'],
                ['title' => 'Gran ambiente', 'text' => 'Un ambiente de trabajo informal con grandes colegas.'],
                ['title' => 'Tecnología moderna', 'text' => 'Trabaje con las últimas técnicas de impresión.'],
            ],
            'vacancies_title' => 'Vacantes actuales',
            'no_vacancies' => 'Actualmente no tenemos vacantes. Esté atento a esta página o envíe una solicitud abierta.',
            'open_application_title' => 'Solicitud abierta',
            'open_application_text' => '¿No encuentra una vacante adecuada? Envíenos una solicitud abierta.',
            'contact_button' => 'Enviar solicitud',
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
                <h1 class="mb-4 text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl lg:text-5xl">
                    {{ $c['title'] }}
                </h1>
                <p class="text-xl text-gray-600">
                    {{ $c['subtitle'] }}
                </p>
            </div>

            {{-- Intro --}}
            <div class="mb-12 rounded-xl bg-gradient-to-r from-primary-500 to-primary-700 p-8 text-center text-white">
                <p class="text-lg">{{ $c['intro'] }}</p>
            </div>

            {{-- Why work here --}}
            <div class="mb-16">
                <h2 class="mb-8 text-center text-2xl font-bold text-gray-900">{{ $c['why_title'] }}</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($c['why_items'] as $item)
                        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center">
                            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-100">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="mb-2 font-semibold text-gray-900">{{ $item['title'] }}</h3>
                            <p class="text-sm text-gray-600">{{ $item['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Vacancies --}}
            <div class="mb-12">
                <h2 class="mb-6 text-2xl font-bold text-gray-900">{{ $c['vacancies_title'] }}</h2>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center">
                    <svg class="mx-auto mb-4 h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <p class="text-gray-600">{{ $c['no_vacancies'] }}</p>
                </div>
            </div>

            {{-- Open application --}}
            <div class="rounded-xl bg-primary-50 p-8 text-center">
                <h2 class="mb-4 text-2xl font-bold text-gray-900">{{ $c['open_application_title'] }}</h2>
                <p class="mb-6 text-gray-600">{{ $c['open_application_text'] }}</p>
                <a
                    href="/{{ $locale }}/{{ $contactSegment }}"
                    class="inline-flex items-center rounded-lg bg-primary-600 px-6 py-3 text-center font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300"
                >
                    {{ $c['contact_button'] }}
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
</div>
