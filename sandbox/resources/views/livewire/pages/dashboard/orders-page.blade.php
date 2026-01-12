<div>
    <x-dashboard.layout>
        {{-- Page Header --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('dashboard.orders.title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('dashboard.orders.subtitle') }}</p>
        </div>

        {{-- Filters --}}
        <div class="flex gap-2 mb-6">
            <button wire:click="setFilter('all')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $filter === 'all' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                {{ __('dashboard.orders.filter_all') }}
            </button>
            <button wire:click="setFilter('pending')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $filter === 'pending' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                {{ __('dashboard.orders.filter_pending') }}
            </button>
            <button wire:click="setFilter('completed')"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $filter === 'completed' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700' }}">
                {{ __('dashboard.orders.filter_completed') }}
            </button>
        </div>

        {{-- Orders List --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            @if($this->orders->isNotEmpty())
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->orders as $order)
                        <a href="{{ route('dashboard.orders.show', ['locale' => $locale, 'dashboardSegment' => localeSegment('dashboard', $locale), 'ordersSegment' => localeSegment('orders', $locale), 'order' => $order->id]) }}"
                           class="block p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3">
                                        <p class="font-semibold text-gray-900 dark:text-white">#{{ $order->reference }}</p>
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
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ __('dashboard.orders.placed_on') }} {{ $order->placed_at->format('d M Y, H:i') }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ trans_choice('dashboard.orders.items_count', $order->lines->count(), ['count' => $order->lines->count()]) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $order->total?->formatted() }}</p>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($this->orders->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $this->orders->links() }}
                    </div>
                @endif
            @else
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <p>{{ __('dashboard.orders.no_orders') }}</p>
                    <a href="{{ route('products.index', ['locale' => $locale, 'productsSegment' => localeSegment('products', $locale)]) }}"
                       class="inline-block mt-4 text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                        {{ __('dashboard.overview.start_shopping') }}
                    </a>
                </div>
            @endif
        </div>
    </x-dashboard.layout>
</div>
