<div>
    <!-- Header -->
    <section class="bg-gray-50 py-8 antialiased md:py-12">
        <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0">
            <h1 class="text-3xl font-bold text-gray-900 md:text-4xl">Hulp & informatie</h1>
            <p class="mt-2 text-gray-500">Veelgestelde vragen en handige artikelen</p>

            <!-- Category Filter -->
            <div class="mt-6 flex flex-wrap gap-2">
                <button
                    wire:click="$set('category', '')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $category === '' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
                >
                    Alle
                </button>
                <button
                    wire:click="$set('category', 'klantenservice')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $category === 'klantenservice' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
                >
                    Klantenservice
                </button>
                <button
                    wire:click="$set('category', 'blog')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $category === 'blog' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
                >
                    Blog
                </button>
            </div>
        </div>
    </section>

    <!-- Articles Grid -->
    <section class="bg-white py-8 antialiased md:py-12">
        <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0">
            @if($articles->count() > 0)
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($articles as $article)
                        <a href="{{ localizedUrl('article.view', ['slug' => $article->getSlug()]) }}" class="group">
                            <article class="h-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md">
                                @if($article->featured_image)
                                    <div class="aspect-video w-full overflow-hidden bg-gray-100">
                                        <img
                                            src="{{ $article->featured_image }}"
                                            alt="{{ $article->getTitle() }}"
                                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        >
                                    </div>
                                @else
                                    <div class="aspect-video w-full overflow-hidden bg-gray-100 flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="p-4">
                                    <div class="mb-2">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $article->category === 'klantenservice' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $article->category === 'klantenservice' ? 'Klantenservice' : 'Blog' }}
                                        </span>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 group-hover:text-primary-700">{{ $article->getTitle() }}</h3>
                                    @if($article->getExcerpt())
                                        <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $article->getExcerpt() }}</p>
                                    @endif
                                    @if($article->published_at)
                                        <p class="mt-3 text-xs text-gray-400">{{ $article->published_at->format('d M Y') }}</p>
                                    @endif
                                </div>
                            </article>
                        </a>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $articles->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Geen artikelen gevonden</h3>
                    <p class="mt-1 text-sm text-gray-500">Er zijn nog geen artikelen in deze categorie.</p>
                </div>
            @endif
        </div>
    </section>
</div>
