<div
    class="relative w-full"
    x-data="{
        open: @entangle('showResults'),
    }"
    @click.away="open = false"
    @keydown.escape="open = false"
>
    <div class="flex">
        {{-- Search input --}}
        <div class="relative w-full">
            <input
                type="search"
                wire:model.live.debounce.300ms="query"
                wire:keydown.enter="goToSearch"
                @focus="if($wire.query.length >= 2) open = true"
                class="z-20 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                placeholder="{{ __('search.placeholder') }}"
                autocomplete="off"
            >

            {{-- Search button --}}
            <button
                type="button"
                wire:click="goToSearch"
                class="absolute end-0 top-0 h-full rounded-e-lg border border-primary-700 bg-primary-700 p-2.5 text-sm font-medium text-white hover:bg-primary-800"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>
                <span class="sr-only">{{ __('search.button') }}</span>
            </button>

            {{-- Loading indicator --}}
            <div wire:loading wire:target="query" class="absolute end-12 top-1/2 -translate-y-1/2">
                <svg class="h-5 w-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    </div>

    {{-- Results dropdown --}}
    <div
        x-show="open && $wire.query.length >= 2"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="absolute left-0 right-0 top-full z-50 mt-1 max-h-96 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
        x-cloak
    >
        @if($this->results->count() > 0)
            <ul class="divide-y divide-gray-100">
                @foreach($this->results as $product)
                    <li>
                        <a
                            href="{{ localizedRoute()->product($product) }}"
                            class="flex items-center gap-4 p-3 hover:bg-gray-50 transition-colors"
                        >
                            {{-- Thumbnail --}}
                            <div class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-md bg-gray-100">
                                @if($product->thumbnail)
                                    <img
                                        src="{{ $product->thumbnail->getUrl('small') }}"
                                        alt="{{ $product->translateAttribute('name') }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center">
                                        <svg class="h-6 w-6 text-gray-300" fill="currentColor" viewBox="0 0 20 18">
                                            <path d="M18 0H2a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2Zm-5.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm4.376 10.481A1 1 0 0 1 16 15H4a1 1 0 0 1-.895-1.447l3.5-7A1 1 0 0 1 7.468 6a.965.965 0 0 1 .9.5l2.775 4.757 1.546-1.887a1 1 0 0 1 1.618.1l2.541 4a1 1 0 0 1 .028 1.011Z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Product info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ $product->translateAttribute('name') }}
                                </p>
                                @php $price = $product->variants->first()?->prices->first(); @endphp
                                @if($price)
                                    <p class="text-sm font-semibold text-primary-700">
                                        {{ $price->price->formatted() }}
                                    </p>
                                @endif
                            </div>

                            {{-- Arrow icon --}}
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- View all results link --}}
            <div class="border-t border-gray-100 bg-gray-50 p-3">
                <a
                    href="{{ localizedUrl('search.view') }}?q={{ urlencode($query) }}"
                    class="flex items-center justify-center gap-2 text-sm font-medium text-primary-700 hover:text-primary-800"
                >
                    {{ __('search.view_all_results') }}
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        @else
            {{-- No results --}}
            <div class="p-6 text-center">
                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                </svg>
                <p class="mt-2 text-sm text-gray-500">{{ __('search.no_results_for', ['query' => $query]) }}</p>
            </div>
        @endif
    </div>
</div>
