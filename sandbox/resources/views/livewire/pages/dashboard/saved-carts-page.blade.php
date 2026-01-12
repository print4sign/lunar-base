<div>
    {{-- Filament Actions Modals Container --}}
    <x-filament-actions::modals />

    <x-dashboard.layout>
        {{-- Page Header --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('dashboard.saved_carts.title') }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('dashboard.saved_carts.subtitle') }}</p>
                </div>
                {{ $this->saveCartAction }}
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        {{-- Saved Carts List --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            @if($this->savedCarts->isNotEmpty())
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->savedCarts as $savedCart)
                        @if($savedCart->cart)
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $savedCart->name }}</h3>
                                    @if($savedCart->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $savedCart->description }}</p>
                                    @endif
                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        <span>
                                            {{ trans_choice('dashboard.saved_carts.items_count', $savedCart->cart->lines->count(), ['count' => $savedCart->cart->lines->count()]) }}
                                        </span>
                                        <span>
                                            {{ __('dashboard.saved_carts.saved_on', ['date' => $savedCart->created_at->format('d M Y')]) }}
                                        </span>
                                    </div>

                                    {{-- Preview of items --}}
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        @foreach($savedCart->cart->lines->take(3) as $line)
                                            <div class="flex items-center gap-2 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-700 dark:text-gray-300">
                                                @if($line->purchasable?->product?->thumbnail)
                                                    <img src="{{ $line->purchasable->product->thumbnail->getUrl('small') }}"
                                                         alt=""
                                                         class="w-6 h-6 object-cover rounded">
                                                @endif
                                                <span class="truncate max-w-[150px]">{{ $line->purchasable?->product?->translateAttribute('name') ?? 'Product' }}</span>
                                                <span class="text-gray-500">x{{ $line->quantity }}</span>
                                            </div>
                                        @endforeach
                                        @if($savedCart->cart->lines->count() > 3)
                                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-500 dark:text-gray-400">
                                                +{{ $savedCart->cart->lines->count() - 3 }} {{ __('dashboard.saved_carts.more') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="restoreCart({{ $savedCart->id }})"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        {{ __('dashboard.saved_carts.restore') }}
                                    </button>
                                    <button wire:click="deleteSavedCart({{ $savedCart->id }})"
                                            wire:confirm="{{ __('dashboard.saved_carts.confirm_delete') }}"
                                            class="px-3 py-2 text-red-600 bg-red-100 rounded-lg hover:bg-red-200 transition-colors dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p>{{ __('dashboard.saved_carts.no_saved_carts') }}</p>
                    <p class="text-sm mt-2">{{ __('dashboard.saved_carts.no_saved_carts_hint') }}</p>
                </div>
            @endif
        </div>
    </x-dashboard.layout>
</div>
