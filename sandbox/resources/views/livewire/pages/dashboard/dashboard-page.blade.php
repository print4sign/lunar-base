<div>
    <x-dashboard.layout>
        {{-- Welcome Card --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ __('dashboard.overview.welcome', ['name' => auth()->user()->name]) }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('dashboard.overview.subtitle') }}</p>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            {{-- Total Orders --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-3 bg-primary-100 dark:bg-primary-900/50 rounded-lg">
                        <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.overview.total_orders') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->orderCount }}</p>
                    </div>
                </div>
            </div>

            {{-- Saved Addresses --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.overview.saved_addresses') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->addressCount }}</p>
                    </div>
                </div>
            </div>

            {{-- Saved Carts --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.overview.saved_carts') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->savedCartCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Orders --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('dashboard.overview.recent_orders') }}</h2>
                <a href="{{ route('dashboard.orders', ['locale' => $locale, 'dashboardSegment' => localeSegment('dashboard', $locale), 'ordersSegment' => localeSegment('orders', $locale)]) }}"
                   class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                    {{ __('dashboard.overview.view_all') }}
                </a>
            </div>

            @if($this->recentOrders->isNotEmpty())
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->recentOrders as $order)
                        <a href="{{ route('dashboard.orders.show', ['locale' => $locale, 'dashboardSegment' => localeSegment('dashboard', $locale), 'ordersSegment' => localeSegment('orders', $locale), 'order' => $order->id]) }}"
                           class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">#{{ $order->reference }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->placed_at->format('d M Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $order->total?->formatted() }}</p>
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                        @if($order->status === 'dispatched' || $order->status === 'completed')
                                            bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400
                                        @elseif($order->status === 'payment-received')
                                            bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400
                                        @else
                                            bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-400
                                        @endif
                                    ">
                                        {{ __('dashboard.orders.status.' . $order->status, [], $locale) }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <p>{{ __('dashboard.overview.no_orders') }}</p>
                    <a href="{{ route('products.index', ['locale' => $locale, 'productsSegment' => localeSegment('products', $locale)]) }}"
                       class="inline-block mt-4 text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                        {{ __('dashboard.overview.start_shopping') }}
                    </a>
                </div>
            @endif
        </div>
    </x-dashboard.layout>
</div>
