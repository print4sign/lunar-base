<div>
    <div class="max-w-screen-xl px-4 py-8 mx-auto lg:py-16">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <li class="inline-flex items-center">
                    <a href="{{ url('/') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-600 dark:text-gray-400 dark:hover:text-white">
                        <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                        Home
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <a href="{{ route('products.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-primary-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">Products</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">{{ $product->translateAttribute('name') }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="lg:grid lg:grid-cols-2 lg:gap-12">
            <!-- Product Images -->
            <div class="mb-8 lg:mb-0">
                <div class="aspect-square bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden mb-4">
                    @if($product->thumbnail)
                        <img
                            src="{{ $product->thumbnail->getUrl('large') }}"
                            alt="{{ $product->translateAttribute('name') }}"
                            class="h-full w-full object-cover"
                        >
                    @else
                        <div class="h-full w-full flex items-center justify-center">
                            <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Thumbnail Gallery -->
                @if($product->media->count() > 1)
                    <div class="grid grid-cols-4 gap-2">
                        @foreach($product->media->take(4) as $media)
                            <button class="aspect-square bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden border-2 border-transparent hover:border-primary-500 focus:border-primary-500 transition-colors">
                                <img src="{{ $media->getUrl('small') }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Info -->
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ $product->translateAttribute('name') }}
                </h1>

                <!-- Price -->
                @if($price)
                    <div class="mb-6">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $price->price->formatted() }}
                        </span>
                        @if($price->compare_price && $price->compare_price->value > $price->price->value)
                            <span class="text-xl text-gray-500 line-through ml-3">
                                {{ $price->compare_price->formatted() }}
                            </span>
                            @php
                                $discount = round((1 - $price->price->value / $price->compare_price->value) * 100);
                            @endphp
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                -{{ $discount }}%
                            </span>
                        @endif
                    </div>
                @endif

                <!-- SKU -->
                @if($selectedVariant?->sku)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        SKU: {{ $selectedVariant->sku }}
                    </p>
                @endif

                <!-- Description -->
                @if($description = $product->translateAttribute('description'))
                    <div class="prose dark:prose-invert max-w-none mb-6">
                        {!! $description !!}
                    </div>
                @endif

                <!-- Variant Options -->
                @if($product->productOptions->count() > 0)
                    <div class="space-y-4 mb-6">
                        @foreach($product->productOptions as $option)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $option->translateAttribute('name') }}
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($option->values as $value)
                                        <button
                                            type="button"
                                            wire:click="$set('selectedOptions.{{ $option->id }}', {{ $value->id }})"
                                            @class([
                                                'px-4 py-2 text-sm font-medium rounded-lg border transition-colors',
                                                'border-primary-600 bg-primary-50 text-primary-700 dark:border-primary-500 dark:bg-primary-900/50 dark:text-primary-400' => ($selectedOptions[$option->id] ?? null) == $value->id,
                                                'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => ($selectedOptions[$option->id] ?? null) != $value->id,
                                            ])
                                        >
                                            {{ $value->translateAttribute('name') }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Quantity & Add to Cart -->
                <div class="flex items-center gap-4 mb-6">
                    <!-- Quantity Selector -->
                    <div class="flex items-center">
                        <button
                            wire:click="decrementQuantity"
                            class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-l-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </button>
                        <input
                            type="number"
                            wire:model="quantity"
                            min="1"
                            class="w-16 text-center border-t border-b border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 focus:ring-0 focus:border-gray-300 dark:focus:border-gray-600"
                        >
                        <button
                            wire:click="incrementQuantity"
                            class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-r-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Add to Cart Button -->
                    <button
                        wire:click="addToCart"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-wait"
                        @disabled(!$selectedVariant)
                        @class([
                            'flex-1 flex items-center justify-center px-6 py-3 text-base font-medium rounded-lg transition-colors',
                            'bg-primary-600 text-white hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800' => !$added && $selectedVariant,
                            'bg-green-600 text-white' => $added,
                            'bg-gray-300 text-gray-500 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500' => !$selectedVariant,
                        ])
                    >
                        <span wire:loading.remove wire:target="addToCart">
                            @if($added)
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Added to Cart!
                            @else
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Add to Cart
                            @endif
                        </span>
                        <span wire:loading wire:target="addToCart" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Adding...
                        </span>
                    </button>
                </div>

                <!-- Product Features -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Free shipping on orders over $50
                    </div>
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        30-day easy returns
                    </div>
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Secure checkout
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
