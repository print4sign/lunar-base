<div>
    <div class="max-w-screen-xl px-4 py-8 mx-auto lg:py-16">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Search</h1>

            <!-- Search Input -->
            <div class="relative max-w-xl">
                <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="query"
                    class="block w-full p-4 ps-12 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                    placeholder="Search products..."
                    autofocus
                >
                @if($query)
                    <button
                        wire:click="$set('query', '')"
                        class="absolute inset-y-0 end-0 flex items-center pe-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- Results -->
        @if(strlen($query) >= 2)
            @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator && $products->count() > 0)
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    {{ $products->total() }} {{ Str::plural('result', $products->total()) }} for "{{ $query }}"
                </p>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($products as $product)
                        <x-storefront.product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No results found</h3>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">
                        No products match "{{ $query }}". Try a different search term.
                    </p>
                </div>
            @endif
        @elseif(strlen($query) > 0)
            <p class="text-gray-500 dark:text-gray-400">Enter at least 2 characters to search.</p>
        @else
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Search our store</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                    Start typing to find products.
                </p>
            </div>
        @endif
    </div>
</div>
