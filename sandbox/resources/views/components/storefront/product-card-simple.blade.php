@props(['product'])

@php
    $url = localizedRoute()->product($product);
@endphp

<a href="{{ $url }}" class="group block h-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
    {{-- Product Image --}}
    <div class="aspect-square w-full overflow-hidden bg-gray-50">
        @if($product->thumbnail)
            <img
                src="{{ $product->thumbnail->getUrl('medium') }}"
                alt="{{ $product->translateAttribute('name') }}"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            >
        @else
            <div class="flex h-full w-full items-center justify-center bg-gray-100">
                <svg class="h-12 w-12 text-gray-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 18">
                    <path d="M18 0H2a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Zm-5.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm4.376 10.481A1 1 0 0 1 16 15H4a1 1 0 0 1-.895-1.447l3.5-7A1 1 0 0 1 7.468 6a.965.965 0 0 1 .9.5l2.775 4.757 1.546-1.887a1 1 0 0 1 1.618.1l2.541 4a1 1 0 0 1 .028 1.011Z"/>
                </svg>
            </div>
        @endif
    </div>

    {{-- Product Title & Description --}}
    <div class="p-4">
        <h3 class="text-base font-semibold leading-tight text-gray-900 group-hover:text-primary-700 line-clamp-2">
            {{ $product->translateAttribute('name') }}
        </h3>
        @if($product->translateAttribute('description'))
            <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                {{ Str::limit(strip_tags($product->translateAttribute('description')), 80) }}
            </p>
        @endif
    </div>
</a>
