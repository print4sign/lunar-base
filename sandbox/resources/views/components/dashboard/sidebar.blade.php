@php
    $currentRoute = request()->route()?->getName() ?? '';
    $locale = currentLocale();

    $links = [
        [
            'route' => 'dashboard.index',
            'icon' => 'home',
            'label' => __('dashboard.nav.overview'),
            'params' => ['dashboardSegment' => localeSegment('dashboard', $locale)],
        ],
        [
            'route' => 'dashboard.orders',
            'icon' => 'shopping-bag',
            'label' => __('dashboard.nav.orders'),
            'params' => [
                'dashboardSegment' => localeSegment('dashboard', $locale),
                'ordersSegment' => localeSegment('orders', $locale),
            ],
        ],
        [
            'route' => 'dashboard.addresses',
            'icon' => 'map-pin',
            'label' => __('dashboard.nav.addresses'),
            'params' => [
                'dashboardSegment' => localeSegment('dashboard', $locale),
                'addressesSegment' => localeSegment('addresses', $locale),
            ],
        ],
        [
            'route' => 'dashboard.saved-carts',
            'icon' => 'shopping-cart',
            'label' => __('dashboard.nav.saved_carts'),
            'params' => [
                'dashboardSegment' => localeSegment('dashboard', $locale),
                'savedCartsSegment' => localeSegment('saved-carts', $locale),
            ],
        ],
        [
            'route' => 'dashboard.invoices',
            'icon' => 'document-text',
            'label' => __('dashboard.nav.invoices'),
            'params' => [
                'dashboardSegment' => localeSegment('dashboard', $locale),
                'invoicesSegment' => localeSegment('invoices', $locale),
            ],
        ],
    ];
@endphp

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
    {{-- User Info --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                <span class="text-primary-700 dark:text-primary-300 font-semibold text-lg">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>

    {{-- Navigation Links --}}
    <nav class="p-4">
        <ul class="space-y-1">
            @foreach($links as $link)
                @php
                    $isActive = str_starts_with($currentRoute, $link['route']);
                @endphp
                <li>
                    <a href="{{ route($link['route'], array_merge(['locale' => $locale], $link['params'])) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        @switch($link['icon'])
                            @case('home')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                @break
                            @case('shopping-bag')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                @break
                            @case('map-pin')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                @break
                            @case('shopping-cart')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                @break
                            @case('document-text')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                @break
                        @endswitch
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Logout --}}
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <form method="POST" action="{{ route('logout', ['locale' => $locale]) }}">
            @csrf
            <button type="submit" class="flex items-center gap-3 px-3 py-2.5 w-full rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                {{ __('dashboard.nav.logout') }}
            </button>
        </form>
    </div>
</div>
