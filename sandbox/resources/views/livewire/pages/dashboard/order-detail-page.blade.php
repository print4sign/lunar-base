<div>
    <x-dashboard.layout>
        {{-- Back Link --}}
        <a href="{{ route('dashboard.orders', ['locale' => $locale, 'dashboardSegment' => localeSegment('dashboard', $locale), 'ordersSegment' => localeSegment('orders', $locale)]) }}"
           class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('dashboard.orders.back_to_orders') }}
        </a>

        {{-- Order Header --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ __('dashboard.orders.order_detail', ['reference' => $order->reference]) }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        {{ __('dashboard.orders.placed_on') }} {{ $order->placed_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="inline-flex px-3 py-1.5 text-sm font-medium rounded-full
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
                    <button wire:click="reorder"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ __('dashboard.orders.reorder') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Order Items --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('dashboard.orders.items') }}</h2>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($order->productLines as $line)
                            <div class="p-4 flex gap-4">
                                @if($line->purchasable?->product?->thumbnail)
                                    <img src="{{ $line->purchasable->product->thumbnail->getUrl('small') }}"
                                         alt="{{ $line->description }}"
                                         class="w-16 h-16 object-cover rounded-lg">
                                @else
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate">{{ $line->description }}</p>
                                    @if($line->option)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $line->option }}</p>
                                    @endif
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('dashboard.orders.qty') }}: {{ $line->quantity }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $line->total?->formatted() }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $line->unit_price?->formatted() }} {{ __('dashboard.orders.each') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Order Summary & Addresses --}}
            <div class="space-y-6">
                {{-- Order Summary --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('dashboard.orders.summary') }}</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('dashboard.orders.subtotal') }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $order->sub_total?->formatted() }}</span>
                        </div>
                        @if($order->discount_total?->value > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('dashboard.orders.discount') }}</span>
                                <span class="text-green-600 dark:text-green-400">-{{ $order->discount_total?->formatted() }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('dashboard.orders.shipping') }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $order->shipping_total?->formatted() }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('dashboard.orders.tax') }}</span>
                            <span class="text-gray-900 dark:text-white">{{ $order->tax_total?->formatted() }}</span>
                        </div>
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ __('dashboard.orders.total') }}</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $order->total?->formatted() }}</span>
                        </div>
                    </div>
                </div>

                {{-- Shipping Address --}}
                @if($order->shippingAddress)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('dashboard.orders.shipping_address') }}</h3>
                        <address class="text-sm text-gray-600 dark:text-gray-400 not-italic">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}</p>
                            @if($order->shippingAddress->company_name)
                                <p>{{ $order->shippingAddress->company_name }}</p>
                            @endif
                            <p>{{ $order->shippingAddress->line_one }}</p>
                            @if($order->shippingAddress->line_two)
                                <p>{{ $order->shippingAddress->line_two }}</p>
                            @endif
                            <p>{{ $order->shippingAddress->postcode }} {{ $order->shippingAddress->city }}</p>
                            <p>{{ $order->shippingAddress->country?->name }}</p>
                        </address>
                    </div>
                @endif

                {{-- Billing Address --}}
                @if($order->billingAddress)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 dark:bg-gray-800 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('dashboard.orders.billing_address') }}</h3>
                        <address class="text-sm text-gray-600 dark:text-gray-400 not-italic">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $order->billingAddress->first_name }} {{ $order->billingAddress->last_name }}</p>
                            @if($order->billingAddress->company_name)
                                <p>{{ $order->billingAddress->company_name }}</p>
                            @endif
                            <p>{{ $order->billingAddress->line_one }}</p>
                            @if($order->billingAddress->line_two)
                                <p>{{ $order->billingAddress->line_two }}</p>
                            @endif
                            <p>{{ $order->billingAddress->postcode }} {{ $order->billingAddress->city }}</p>
                            <p>{{ $order->billingAddress->country?->name }}</p>
                        </address>
                    </div>
                @endif
            </div>
        </div>
    </x-dashboard.layout>
</div>
