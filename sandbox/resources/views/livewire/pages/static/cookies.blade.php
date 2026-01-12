@php
    $locale = app()->getLocale();

    $content = [
        'nl' => [
            'title' => 'Cookiebeleid',
            'last_updated' => 'Laatst bijgewerkt',
            'date' => '1 januari 2025',
            'intro' => 'Drukhoek maakt gebruik van cookies om de website goed te laten functioneren en om het gebruik van de website te analyseren. In dit cookiebeleid leggen we uit welke cookies we gebruiken en waarvoor.',
            'what_title' => 'Wat zijn cookies?',
            'what_text' => 'Cookies zijn kleine tekstbestanden die door een website op je computer, tablet of telefoon worden geplaatst. Ze helpen de website om je voorkeuren te onthouden en om informatie te verzamelen over hoe je de website gebruikt.',
            'types_title' => 'Welke cookies gebruiken wij?',
            'types' => [
                [
                    'name' => 'Noodzakelijke cookies',
                    'description' => 'Deze cookies zijn essentieel voor het functioneren van de website. Zonder deze cookies werkt de website niet goed. Voorbeelden zijn cookies voor het winkelwagentje en inloggen.',
                    'required' => true,
                ],
                [
                    'name' => 'Functionele cookies',
                    'description' => 'Deze cookies onthouden je voorkeuren, zoals je taalinstelling en regio. Ze zorgen voor een betere gebruikerservaring.',
                    'required' => false,
                ],
                [
                    'name' => 'Analytische cookies',
                    'description' => 'Met deze cookies verzamelen we informatie over hoe bezoekers de website gebruiken. We gebruiken deze informatie om de website te verbeteren. We gebruiken hiervoor Google Analytics met geanonimiseerde IP-adressen.',
                    'required' => false,
                ],
                [
                    'name' => 'Marketing cookies',
                    'description' => 'Deze cookies worden gebruikt om advertenties relevanter voor je te maken. Ze kunnen ook worden gebruikt om het aantal keren dat je een advertentie ziet te beperken.',
                    'required' => false,
                ],
            ],
            'cookies_list_title' => 'Overzicht van cookies',
            'cookies_list' => [
                ['name' => 'session', 'purpose' => 'Sessie beheer', 'duration' => 'Sessie', 'type' => 'Noodzakelijk'],
                ['name' => 'XSRF-TOKEN', 'purpose' => 'Beveiliging', 'duration' => 'Sessie', 'type' => 'Noodzakelijk'],
                ['name' => 'cart_id', 'purpose' => 'Winkelwagen', 'duration' => '30 dagen', 'type' => 'Noodzakelijk'],
                ['name' => 'locale', 'purpose' => 'Taalvoorkeur', 'duration' => '1 jaar', 'type' => 'Functioneel'],
                ['name' => '_ga', 'purpose' => 'Google Analytics', 'duration' => '2 jaar', 'type' => 'Analytisch'],
                ['name' => '_gid', 'purpose' => 'Google Analytics', 'duration' => '24 uur', 'type' => 'Analytisch'],
            ],
            'manage_title' => 'Cookies beheren',
            'manage_text' => 'Je kunt je cookievoorkeuren op elk moment aanpassen. Bij je eerste bezoek aan onze website kun je aangeven welke cookies je accepteert. Je kunt ook cookies verwijderen of blokkeren via je browserinstellingen.',
            'browser_title' => 'Cookies uitschakelen in je browser',
            'browser_text' => 'Je kunt cookies ook uitschakelen of verwijderen via je browserinstellingen:',
            'browsers' => [
                ['name' => 'Google Chrome', 'url' => 'https://support.google.com/chrome/answer/95647'],
                ['name' => 'Mozilla Firefox', 'url' => 'https://support.mozilla.org/nl/kb/cookies-verwijderen-gegevens-wissen-websites-opgeslagen'],
                ['name' => 'Safari', 'url' => 'https://support.apple.com/nl-nl/guide/safari/sfri11471/mac'],
                ['name' => 'Microsoft Edge', 'url' => 'https://support.microsoft.com/nl-nl/microsoft-edge/cookies-verwijderen-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09'],
            ],
            'changes_title' => 'Wijzigingen',
            'changes_text' => 'Dit cookiebeleid kan worden aangepast. Controleer daarom regelmatig deze pagina voor de meest actuele versie.',
            'contact_title' => 'Vragen?',
            'contact_text' => 'Heb je vragen over ons cookiebeleid? Neem dan contact met ons op.',
            'contact_button' => 'Neem contact op',
        ],
        'en' => [
            'title' => 'Cookie Policy',
            'last_updated' => 'Last updated',
            'date' => 'January 1, 2025',
            'intro' => 'Drukhoek uses cookies to make the website function properly and to analyze the use of the website. In this cookie policy, we explain which cookies we use and why.',
            'what_title' => 'What are cookies?',
            'what_text' => 'Cookies are small text files that are placed on your computer, tablet or phone by a website. They help the website remember your preferences and collect information about how you use the website.',
            'types_title' => 'Which cookies do we use?',
            'types' => [
                [
                    'name' => 'Necessary cookies',
                    'description' => 'These cookies are essential for the functioning of the website. Without these cookies, the website does not work properly. Examples include cookies for the shopping cart and logging in.',
                    'required' => true,
                ],
                [
                    'name' => 'Functional cookies',
                    'description' => 'These cookies remember your preferences, such as your language setting and region. They provide a better user experience.',
                    'required' => false,
                ],
                [
                    'name' => 'Analytical cookies',
                    'description' => 'With these cookies, we collect information about how visitors use the website. We use this information to improve the website. We use Google Analytics with anonymized IP addresses.',
                    'required' => false,
                ],
                [
                    'name' => 'Marketing cookies',
                    'description' => 'These cookies are used to make advertisements more relevant to you. They can also be used to limit the number of times you see an advertisement.',
                    'required' => false,
                ],
            ],
            'cookies_list_title' => 'Cookie overview',
            'cookies_list' => [
                ['name' => 'session', 'purpose' => 'Session management', 'duration' => 'Session', 'type' => 'Necessary'],
                ['name' => 'XSRF-TOKEN', 'purpose' => 'Security', 'duration' => 'Session', 'type' => 'Necessary'],
                ['name' => 'cart_id', 'purpose' => 'Shopping cart', 'duration' => '30 days', 'type' => 'Necessary'],
                ['name' => 'locale', 'purpose' => 'Language preference', 'duration' => '1 year', 'type' => 'Functional'],
                ['name' => '_ga', 'purpose' => 'Google Analytics', 'duration' => '2 years', 'type' => 'Analytical'],
                ['name' => '_gid', 'purpose' => 'Google Analytics', 'duration' => '24 hours', 'type' => 'Analytical'],
            ],
            'manage_title' => 'Managing cookies',
            'manage_text' => 'You can adjust your cookie preferences at any time. When you first visit our website, you can indicate which cookies you accept. You can also delete or block cookies via your browser settings.',
            'browser_title' => 'Disabling cookies in your browser',
            'browser_text' => 'You can also disable or delete cookies via your browser settings:',
            'browsers' => [
                ['name' => 'Google Chrome', 'url' => 'https://support.google.com/chrome/answer/95647'],
                ['name' => 'Mozilla Firefox', 'url' => 'https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox'],
                ['name' => 'Safari', 'url' => 'https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac'],
                ['name' => 'Microsoft Edge', 'url' => 'https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09'],
            ],
            'changes_title' => 'Changes',
            'changes_text' => 'This cookie policy may be modified. Therefore, check this page regularly for the most current version.',
            'contact_title' => 'Questions?',
            'contact_text' => 'Do you have questions about our cookie policy? Please contact us.',
            'contact_button' => 'Contact us',
        ],
        'de' => [
            'title' => 'Cookie-Richtlinie',
            'last_updated' => 'Zuletzt aktualisiert',
            'date' => '1. Januar 2025',
            'intro' => 'Drukhoek verwendet Cookies, damit die Website ordnungsgemäß funktioniert und um die Nutzung der Website zu analysieren.',
            'what_title' => 'Was sind Cookies?',
            'what_text' => 'Cookies sind kleine Textdateien, die von einer Website auf Ihrem Computer, Tablet oder Telefon platziert werden.',
            'types_title' => 'Welche Cookies verwenden wir?',
            'types' => [
                ['name' => 'Notwendige Cookies', 'description' => 'Diese Cookies sind für das Funktionieren der Website unerlässlich.', 'required' => true],
                ['name' => 'Funktionale Cookies', 'description' => 'Diese Cookies merken sich Ihre Einstellungen wie Sprache und Region.', 'required' => false],
                ['name' => 'Analytische Cookies', 'description' => 'Mit diesen Cookies sammeln wir Informationen darüber, wie Besucher die Website nutzen.', 'required' => false],
                ['name' => 'Marketing-Cookies', 'description' => 'Diese Cookies werden verwendet, um Werbung für Sie relevanter zu machen.', 'required' => false],
            ],
            'cookies_list_title' => 'Cookie-Übersicht',
            'cookies_list' => [
                ['name' => 'session', 'purpose' => 'Sitzungsverwaltung', 'duration' => 'Sitzung', 'type' => 'Notwendig'],
                ['name' => 'cart_id', 'purpose' => 'Warenkorb', 'duration' => '30 Tage', 'type' => 'Notwendig'],
                ['name' => 'locale', 'purpose' => 'Spracheinstellung', 'duration' => '1 Jahr', 'type' => 'Funktional'],
            ],
            'manage_title' => 'Cookies verwalten',
            'manage_text' => 'Sie können Ihre Cookie-Einstellungen jederzeit anpassen.',
            'browser_title' => 'Cookies in Ihrem Browser deaktivieren',
            'browser_text' => 'Sie können Cookies auch über Ihre Browsereinstellungen deaktivieren oder löschen.',
            'browsers' => [
                ['name' => 'Google Chrome', 'url' => 'https://support.google.com/chrome/answer/95647'],
                ['name' => 'Mozilla Firefox', 'url' => 'https://support.mozilla.org/de/kb/cookies-loeschen-daten-von-websites-entfernen'],
            ],
            'changes_title' => 'Änderungen',
            'changes_text' => 'Diese Cookie-Richtlinie kann geändert werden.',
            'contact_title' => 'Fragen?',
            'contact_text' => 'Haben Sie Fragen zu unserer Cookie-Richtlinie? Kontaktieren Sie uns.',
            'contact_button' => 'Kontaktieren Sie uns',
        ],
        'es' => [
            'title' => 'Política de cookies',
            'last_updated' => 'Última actualización',
            'date' => '1 de enero de 2025',
            'intro' => 'Drukhoek utiliza cookies para que el sitio web funcione correctamente y para analizar el uso del sitio web.',
            'what_title' => '¿Qué son las cookies?',
            'what_text' => 'Las cookies son pequeños archivos de texto que un sitio web coloca en su ordenador, tableta o teléfono.',
            'types_title' => '¿Qué cookies utilizamos?',
            'types' => [
                ['name' => 'Cookies necesarias', 'description' => 'Estas cookies son esenciales para el funcionamiento del sitio web.', 'required' => true],
                ['name' => 'Cookies funcionales', 'description' => 'Estas cookies recuerdan sus preferencias como el idioma y la región.', 'required' => false],
                ['name' => 'Cookies analíticas', 'description' => 'Con estas cookies recopilamos información sobre cómo los visitantes utilizan el sitio web.', 'required' => false],
                ['name' => 'Cookies de marketing', 'description' => 'Estas cookies se utilizan para hacer que los anuncios sean más relevantes para usted.', 'required' => false],
            ],
            'cookies_list_title' => 'Resumen de cookies',
            'cookies_list' => [
                ['name' => 'session', 'purpose' => 'Gestión de sesión', 'duration' => 'Sesión', 'type' => 'Necesaria'],
                ['name' => 'cart_id', 'purpose' => 'Carrito de compras', 'duration' => '30 días', 'type' => 'Necesaria'],
                ['name' => 'locale', 'purpose' => 'Preferencia de idioma', 'duration' => '1 año', 'type' => 'Funcional'],
            ],
            'manage_title' => 'Gestionar cookies',
            'manage_text' => 'Puede ajustar sus preferencias de cookies en cualquier momento.',
            'browser_title' => 'Desactivar cookies en su navegador',
            'browser_text' => 'También puede desactivar o eliminar cookies a través de la configuración de su navegador.',
            'browsers' => [
                ['name' => 'Google Chrome', 'url' => 'https://support.google.com/chrome/answer/95647'],
                ['name' => 'Mozilla Firefox', 'url' => 'https://support.mozilla.org/es/kb/Borrar%20cookies'],
            ],
            'changes_title' => 'Cambios',
            'changes_text' => 'Esta política de cookies puede modificarse.',
            'contact_title' => '¿Preguntas?',
            'contact_text' => '¿Tiene preguntas sobre nuestra política de cookies? Contáctenos.',
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

            {{-- What are cookies --}}
            <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-xl font-bold text-gray-900">{{ $c['what_title'] }}</h2>
                <p class="text-gray-600">{{ $c['what_text'] }}</p>
            </div>

            {{-- Cookie types --}}
            <div class="mb-8">
                <h2 class="mb-6 text-xl font-bold text-gray-900">{{ $c['types_title'] }}</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($c['types'] as $type)
                        <div class="rounded-lg border {{ $type['required'] ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-white' }} p-6">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">{{ $type['name'] }}</h3>
                                @if($type['required'])
                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                        {{ $locale === 'nl' ? 'Vereist' : ($locale === 'en' ? 'Required' : ($locale === 'de' ? 'Erforderlich' : 'Requerido')) }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600">{{ $type['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Cookie list --}}
            <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-xl font-bold text-gray-900">{{ $c['cookies_list_title'] }}</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="px-4 py-3">Cookie</th>
                                <th class="px-4 py-3">{{ $locale === 'nl' ? 'Doel' : ($locale === 'en' ? 'Purpose' : ($locale === 'de' ? 'Zweck' : 'Propósito')) }}</th>
                                <th class="px-4 py-3">{{ $locale === 'nl' ? 'Bewaartermijn' : ($locale === 'en' ? 'Duration' : ($locale === 'de' ? 'Dauer' : 'Duración')) }}</th>
                                <th class="px-4 py-3">Type</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($c['cookies_list'] as $cookie)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-sm">{{ $cookie['name'] }}</td>
                                    <td class="px-4 py-3">{{ $cookie['purpose'] }}</td>
                                    <td class="px-4 py-3">{{ $cookie['duration'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs">{{ $cookie['type'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Manage cookies --}}
            <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-xl font-bold text-gray-900">{{ $c['manage_title'] }}</h2>
                <p class="text-gray-600">{{ $c['manage_text'] }}</p>
            </div>

            {{-- Browser settings --}}
            <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-xl font-bold text-gray-900">{{ $c['browser_title'] }}</h2>
                <p class="mb-4 text-gray-600">{{ $c['browser_text'] }}</p>
                <ul class="space-y-2">
                    @foreach($c['browsers'] as $browser)
                        <li>
                            <a href="{{ $browser['url'] }}" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline">
                                {{ $browser['name'] }}
                                <svg class="inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Changes --}}
            <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6">
                <h2 class="mb-4 text-xl font-bold text-gray-900">{{ $c['changes_title'] }}</h2>
                <p class="text-gray-600">{{ $c['changes_text'] }}</p>
            </div>

            {{-- Contact --}}
            <div class="mt-12 rounded-xl bg-gray-50 p-8 text-center">
                <h2 class="mb-2 text-xl font-bold text-gray-900">{{ $c['contact_title'] }}</h2>
                <p class="mb-6 text-gray-600">{{ $c['contact_text'] }}</p>
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
