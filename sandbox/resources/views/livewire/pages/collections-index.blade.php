<div>
    <div class="max-w-screen-xl px-4 py-8 mx-auto lg:py-16">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Collections</h1>
            <p class="text-gray-500 dark:text-gray-400">Browse our curated product collections</p>
        </div>

        @if($collections->count() > 0)
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($collections as $collection)
                    <a href="{{ route('collection.view', $collection->defaultUrl?->slug ?? $collection->id) }}" class="group relative block overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-md hover:shadow-lg transition-shadow">
                        <div class="aspect-[16/9] bg-gray-100 dark:bg-gray-700">
                            @if($collection->thumbnail)
                                <img src="{{ $collection->thumbnail->getUrl('medium') }}" alt="{{ $collection->translateAttribute('name') }}" class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="h-full w-full flex items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-500">
                                {{ $collection->translateAttribute('name') }}
                            </h3>
                            @if($description = $collection->translateAttribute('description'))
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                    {{ strip_tags($description) }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No collections found</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">There are no collections available at the moment.</p>
            </div>
        @endif
    </div>
</div>
