<div>
    <!-- Breadcrumb -->
    <section class="bg-gray-50 py-4">
        <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ localizedUrl('home') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-600">
                            <svg class="mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                            Home
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <a href="{{ localizedUrl('articles.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-primary-600 md:ml-2">Artikelen</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">{{ Str::limit($article->getTitle(), 40) }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Article Content -->
    <article class="bg-white py-8 antialiased md:py-12">
        <div class="mx-auto max-w-screen-xl px-4 2xl:px-0">
            <div class="mx-auto max-w-3xl">
                <!-- Category & Date -->
                <div class="mb-4 flex items-center gap-4">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $article->category === 'klantenservice' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                        {{ $article->category === 'klantenservice' ? 'Klantenservice' : 'Blog' }}
                    </span>
                    @if($article->published_at)
                        <span class="text-sm text-gray-500">{{ $article->published_at->format('d M Y') }}</span>
                    @endif
                </div>

                <!-- Title -->
                <h1 class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl">{{ $article->getTitle() }}</h1>

                <!-- Excerpt -->
                @if($article->getExcerpt())
                    <p class="mb-8 text-xl text-gray-600 leading-relaxed">{{ $article->getExcerpt() }}</p>
                @endif

                <!-- Featured Image -->
                @if($article->featured_image)
                    <div class="mb-8 overflow-hidden rounded-lg">
                        <img
                            src="{{ $article->featured_image }}"
                            alt="{{ $article->getTitle() }}"
                            class="w-full object-cover"
                        >
                    </div>
                @endif

                <!-- Body Content -->
                <div class="prose prose-lg max-w-none prose-headings:text-gray-900 prose-p:text-gray-600 prose-a:text-primary-600 prose-a:no-underline hover:prose-a:underline prose-img:rounded-lg">
                    {!! $article->getBody() !!}
                </div>

                <!-- Tags -->
                @if($article->tags && count($article->tags) > 0)
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <div class="flex flex-wrap gap-2">
                            @foreach($article->tags as $tag)
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-800">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Back Link -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <a href="{{ localizedUrl('articles.index') }}" class="inline-flex items-center text-primary-600 hover:text-primary-700 font-medium">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Terug naar alle artikelen
                    </a>
                </div>
            </div>
        </div>
    </article>
</div>
