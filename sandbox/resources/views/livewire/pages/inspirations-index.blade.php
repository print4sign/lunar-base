<div>
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-primary-600 to-primary-800 py-12 md:py-16">
        <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0 text-center">
            <h1 class="text-3xl font-bold text-white md:text-4xl lg:text-5xl">
                {{ __('inspiration.index.title') }}
            </h1>
            <p class="mt-4 text-lg text-primary-100 max-w-2xl mx-auto">
                {{ __('inspiration.index.subtitle') }}
            </p>

            <!-- Stats -->
            <div class="mt-8 flex justify-center gap-8">
                <div class="text-center">
                    <div class="text-3xl font-bold text-white">{{ $this->totalReviews }}</div>
                    <div class="text-sm text-primary-200">{{ __('inspiration.stats.reviews') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white flex items-center justify-center gap-1">
                        {{ number_format($this->averageRating ?? 0, 1) }}
                        <svg class="w-6 h-6 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <div class="text-sm text-primary-200">{{ __('inspiration.stats.average') }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filters -->
    <section class="bg-gray-50 border-b py-4">
        <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <!-- Type Filter -->
                <div class="flex flex-wrap gap-2">
                    <button
                        wire:click="$set('type', '')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $type === '' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
                    >
                        {{ __('inspiration.filter.all') }}
                    </button>
                    <button
                        wire:click="$set('type', 'review')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $type === 'review' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
                    >
                        {{ __('inspiration.filter.reviews') }}
                    </button>
                    <button
                        wire:click="$set('type', 'case_study')"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $type === 'case_study' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
                    >
                        {{ __('inspiration.filter.cases') }}
                    </button>
                </div>

                <!-- Rating Filter -->
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">{{ __('inspiration.filter.min_rating') }}:</span>
                    <div class="flex gap-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <button
                                wire:click="$set('rating', {{ $rating === $i ? 0 : $i }})"
                                class="p-1 rounded transition {{ $rating >= $i ? 'text-yellow-400' : 'text-gray-300 hover:text-yellow-300' }}"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </button>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Inspirations Grid -->
    <section class="bg-white py-8 md:py-12">
        <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0">
            @if($inspirations->count() > 0)
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($inspirations as $inspiration)
                        <article class="group overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-lg">
                            <!-- Photo Gallery -->
                            @php
                                $photos = $inspiration->getMedia('inspiration_photos');
                            @endphp
                            @if($photos->count() > 0)
                                <div class="relative aspect-square overflow-hidden bg-gray-100">
                                    <img
                                        src="{{ $photos->first()->getUrl('medium') }}"
                                        alt="{{ $inspiration->getProductName() }}"
                                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    >
                                    @if($photos->count() > 1)
                                        <div class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded-full">
                                            +{{ $photos->count() - 1 }}
                                        </div>
                                    @endif
                                    @if($inspiration->featured)
                                        <div class="absolute top-2 left-2">
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                {{ __('inspiration.badge.featured') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="aspect-square w-full bg-gray-100 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif

                            <div class="p-4">
                                <!-- Rating -->
                                <div class="flex items-center gap-1 mb-2">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $inspiration->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                    <span class="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ __('inspiration.badge.verified') }}
                                    </span>
                                </div>

                                <!-- Title or Product -->
                                @if($inspiration->isCaseStudy() && $inspiration->getTitle())
                                    <h3 class="font-semibold text-gray-900">{{ $inspiration->getTitle() }}</h3>
                                    @if($inspiration->company_name)
                                        <p class="text-sm text-gray-500">{{ $inspiration->company_name }}</p>
                                    @endif
                                @else
                                    <h3 class="font-medium text-gray-900 line-clamp-1">{{ $inspiration->getProductName() }}</h3>
                                @endif

                                <!-- Review Text -->
                                <p class="mt-2 text-sm text-gray-600 line-clamp-3">{{ $inspiration->getText() }}</p>

                                <!-- Type Badge -->
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $inspiration->type === 'case_study' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $inspiration->type === 'case_study' ? __('inspiration.type.case_study') : __('inspiration.type.review') }}
                                    </span>
                                    @if($inspiration->published_at)
                                        <span class="text-xs text-gray-400">{{ $inspiration->published_at->diffForHumans() }}</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $inspirations->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">{{ __('inspiration.empty.title') }}</h3>
                    <p class="mt-2 text-gray-500">{{ __('inspiration.empty.description') }}</p>
                </div>
            @endif
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-gray-50 py-12">
        <div class="mx-auto max-w-screen-xl px-4 text-center">
            <h2 class="text-2xl font-bold text-gray-900">{{ __('inspiration.cta.title') }}</h2>
            <p class="mt-2 text-gray-600">{{ __('inspiration.cta.description') }}</p>
            @auth
                <a href="{{ route('dashboard.orders') }}" class="mt-6 inline-flex items-center px-6 py-3 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition">
                    {{ __('inspiration.cta.button') }}
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            @else
                <a href="{{ localizedUrl('login') }}" class="mt-6 inline-flex items-center px-6 py-3 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition">
                    {{ __('inspiration.cta.login') }}
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            @endauth
        </div>
    </section>
</div>
