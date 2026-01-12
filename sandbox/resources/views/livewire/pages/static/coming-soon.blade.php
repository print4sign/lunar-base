@php
    $locale = app()->getLocale();

    $content = [
        'nl' => [
            'title' => 'Binnenkort beschikbaar',
            'text' => 'We werken aan deze pagina. Kom snel terug voor meer informatie.',
            'back' => 'Terug naar home',
        ],
        'en' => [
            'title' => 'Coming soon',
            'text' => 'We are working on this page. Check back soon for more information.',
            'back' => 'Back to home',
        ],
        'de' => [
            'title' => 'Demnächst verfügbar',
            'text' => 'Wir arbeiten an dieser Seite. Schauen Sie bald wieder vorbei.',
            'back' => 'Zurück zur Startseite',
        ],
        'es' => [
            'title' => 'Próximamente',
            'text' => 'Estamos trabajando en esta página. Vuelva pronto para más información.',
            'back' => 'Volver al inicio',
        ],
    ];

    $c = $content[$locale] ?? $content['nl'];
@endphp

<div>
    <section class="bg-white py-24">
        <div class="mx-auto max-w-screen-xl px-4 text-center">
            <div class="mx-auto max-w-lg">
                <svg class="mx-auto mb-8 h-24 w-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h1 class="mb-4 text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl">
                    {{ $c['title'] }}
                </h1>
                <p class="mb-8 text-lg text-gray-600">
                    {{ $c['text'] }}
                </p>
                <a
                    href="/{{ $locale }}"
                    class="inline-flex items-center rounded-lg bg-primary-600 px-6 py-3 text-center font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300"
                >
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ $c['back'] }}
                </a>
            </div>
        </div>
    </section>
</div>
