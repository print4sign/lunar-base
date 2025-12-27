@props(['product'])

@php
    $variant = $product->variants->first();
    $price = $variant?->prices->first();
    $url = route('product.view', $product->defaultUrl?->slug ?? $product->id);
@endphp

<div class="group bg-white dark:bg-gray-900 rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden">
    <a href="{{ $url }}" class="block">
        <div class="aspect-square bg-gray-100 dark:bg-gray-800 overflow-hidden">
            @if($product->thumbnail)
                <img
                    src="{{ $product->thumbnail->getUrl('medium') }}"
                    alt="{{ $product->translateAttribute('name') }}"
                    class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
                >
            @else
                <div class="h-full w-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif
        </div>
    </a>

    <div class="p-4">
        <a href="{{ $url }}">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-500 line-clamp-2 mb-2">
                {{ $product->translateAttribute('name') }}
            </h3>
        </a>

        @if($price)
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ $price->price->formatted() }}
                    </span>
                    @if($price->compare_price && $price->compare_price->value > $price->price->value)
                        <span class="text-sm text-gray-500 line-through ml-2">
                            {{ $price->compare_price->formatted() }}
                        </span>
                    @endif
                </div>
            </div>
        @endif

        <livewire:components.add-to-cart :variant="$variant" :wire:key="'add-to-cart-'.$variant->id" />
    </div>
</div>
