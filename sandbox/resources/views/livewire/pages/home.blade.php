<div>
    <!-- Hero Section -->
    <section class="bg-gray-50 pb-8 antialiased md:pb-16">
        <div class="bg-[url('https://flowbite.s3.amazonaws.com/blocks/e-commerce/hero-ecommcerce-image-light.jpg')] bg-cover bg-center bg-no-repeat'https://flowbite.s3.amazonaws.com/blocks/e-commerce/hero-ecommcerce-image-dark.jpg')]">
            <div class="relative z-10 mx-auto max-w-2xl px-4 pb-32 pt-8 text-center text-white lg:pt-16 xl:px-0">
                <h1 class="mb-4 text-4xl font-extrabold leading-tight tracking-tight text-primary-900 lg:text-6xl">Premium Quality Products</h1>
                <p class="mb-6 font-light text-primary-800 md:text-lg lg:mb-8 lg:text-xl">Discover our curated selection of high-quality products for your business and home.</p>
                <a href="{{ localizedUrl('products.index') }}" class="inline-block rounded-lg bg-primary-700 px-6 py-3.5 text-center font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300">Shop now</a>
            </div>
        </div>
        @if($collections->count() > 0)
        <div class="-mt-20 px-4 2xl:px-0">
            <div class="mx-auto grid max-w-screen-2xl grid-cols-2 gap-x-4 gap-y-8 rounded-lg border border-gray-200 bg-white py-8 shadow-sm sm:grid-cols-3 md:grid-cols-4 md:p-8 lg:grid-cols-{{ min($collections->count(), 8) }}">
                @foreach($collections->take(8) as $collection)
                <div class="text-center">
                    <a href="{{ localizedUrl('collection.view', ['slug' => $collection->defaultUrl?->slug ?? $collection->id]) }}" class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-lg border border-gray-200 bg-white hover:bg-gray-50" aria-label="{{ $collection->translateAttribute('name') }}">
                        @if($collection->thumbnail)
                            <img src="{{ $collection->thumbnail->getUrl('small') }}" alt="{{ $collection->translateAttribute('name') }}" class="h-12 w-12 object-contain">
                        @else
                            <svg class="h-5 w-5 text-gray-900 lg:h-8 lg:w-8" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        @endif
                    </a>
                    <a href="{{ localizedUrl('collection.view', ['slug' => $collection->defaultUrl?->slug ?? $collection->id]) }}" class="mb-2 text-lg font-semibold text-gray-900 hover:underline">{{ $collection->translateAttribute('name') }}</a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </section>

    <!-- Featured Products Section -->
    @if($featuredProducts->count() > 0)
    <section class="bg-white">
        <div class="max-w-screen-2xl px-4 py-8 mx-auto lg:py-16 2xl:px-0">
            <div class="flex items-center justify-between mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Featured Products</h2>
                <a href="{{ localizedUrl('products.index') }}" class="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center">
                    View all
                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                </a>
            </div>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($featuredProducts as $product)
                    <x-storefront.product-card :product="$product" />
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Promo Cards Section -->
    <section class="bg-gray-50 py-8 antialiased md:py-16">
        <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0">
            <div class="grid gap-4 md:gap-6 lg:grid-cols-2">
                <!-- Spoedje Card - Photo Background -->
                <div class="relative overflow-hidden rounded-lg border border-gray-200 shadow-sm min-h-[280px] md:min-h-[320px]">
                    <!-- Background Image -->
                    <img
                        src="/images/drukhoek-spoedlevering-medewerker.jpg"
                        alt="Drukhoek medewerker met pakket"
                        class="absolute inset-0 h-full w-full object-cover object-center"
                    >
                    <!-- Gradient Overlay (left to right for text readability) -->
                    <div class="absolute inset-0 bg-gradient-to-r from-gray-900/80 via-gray-900/50 to-transparent"></div>
                    <!-- Content -->
                    <div class="relative z-10 flex h-full min-h-[280px] md:min-h-[320px] flex-col justify-center p-6 md:p-8 lg:p-10">
                        <div class="max-w-xs md:max-w-sm">
                            <h2 class="text-2xl font-extrabold uppercase leading-tight text-white md:text-3xl lg:text-4xl">
                                Haast?<br>Wij regelen het!
                            </h2>
                            <p class="mt-4 text-sm font-normal text-gray-200 md:text-base">
                                De meeste producten zijn binnen 1-2 werkdagen bij je. Nog sneller nodig? Met onze spoedlevering zorgen we dat jouw bestelling op tijd aankomt.
                            </p>
                            <a href="#" class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-300">
                                Levertijden bekijken
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Samples Card - Photo Background -->
                <div class="relative overflow-hidden rounded-lg border border-gray-200 shadow-sm min-h-[280px] md:min-h-[320px]">
                    <!-- Background Image -->
                    <img
                        src="/images/drukhoek-samples-medewerker.jpg"
                        alt="Drukhoek medewerker met samplebox"
                        class="absolute inset-0 h-full w-full object-cover object-center"
                    >
                    <!-- Gradient Overlay (left to right for text readability) -->
                    <div class="absolute inset-0 bg-gradient-to-r from-gray-900/80 via-gray-900/50 to-transparent"></div>
                    <!-- Content -->
                    <div class="relative z-10 flex h-full min-h-[280px] md:min-h-[320px] flex-col justify-center p-6 md:p-8 lg:p-10">
                        <div class="max-w-xs md:max-w-sm">
                            <h2 class="text-2xl font-extrabold uppercase leading-tight text-white md:text-3xl lg:text-4xl">
                                Gratis<br>samples!
                            </h2>
                            <p class="mt-4 text-sm font-normal text-gray-200 md:text-base">
                                Benieuwd naar de kwaliteit? Bestel gratis materiaalvoorbeelden en overtuig jezelf van onze printkwaliteit voordat je bestelt.
                            </p>
                            <a href="#" class="mt-6 inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-300">
                                Samples aanvragen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Klantenservice Artikelen Section -->
    <section class="bg-white py-8 antialiased md:py-16">
        <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0">
            <!-- Section Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Hulp & informatie</h2>
                    <p class="mt-1 text-gray-500">Veelgestelde vragen en handige artikelen</p>
                </div>
                <a href="#" class="hidden text-primary-600 hover:text-primary-700 font-medium md:inline-flex items-center">
                    Alle artikelen
                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                </a>
            </div>

            <!-- Articles Grid -->
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($articles as $article)
                <a href="#" class="group">
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
                            <h3 class="font-semibold text-gray-900 group-hover:text-primary-700">{{ $article->getTitle() }}</h3>
                            @if($article->getExcerpt())
                            <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $article->getExcerpt() }}</p>
                            @endif
                        </div>
                    </article>
                </a>
                @empty
                <!-- Fallback content when no articles -->
                <div class="col-span-full text-center py-8 text-gray-500">
                    Nog geen artikelen beschikbaar.
                </div>
                @endforelse
            </div>

            <!-- Mobile: View all link -->
            <div class="mt-6 text-center md:hidden">
                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium inline-flex items-center">
                    Alle artikelen bekijken
                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Over Drukhoek Section -->
    <section class="bg-gray-50 py-8 antialiased md:py-16">
        <div class="mx-auto max-w-screen-2xl px-4 2xl:px-0">
            <div class="items-center gap-8 lg:flex lg:gap-16">
                <!-- Image -->
                <div class="mb-8 lg:mb-0 lg:w-1/2">
                    <div class="overflow-hidden rounded-lg shadow-lg">
                        <img
                            src="/images/drukhoek-bedrijfsband.jpg"
                            alt="Drukhoek productiehal en team"
                            class="h-full w-full object-cover"
                        >
                    </div>
                </div>
                <!-- Content -->
                <div class="lg:w-1/2">
                    <span class="mb-2 inline-block text-sm font-semibold uppercase tracking-wider text-primary-600">Over ons</span>
                    <h2 class="mb-4 text-2xl font-bold tracking-tight text-gray-900 md:text-3xl lg:text-4xl">
                        Drukhoek. Al sinds 2005 maken we reclame makkelijk én leuk!
                    </h2>
                    <p class="mb-6 text-gray-600 md:text-lg">
                        Wij helpen ondernemers opvallen: op straat, op beurzen, in winkels en op kantoor. In onze eigen productiehal van 25.000m² maken we alles zelf. Van stoepbord en flyers tot kleding, cadeaus en gevelreclame. Alles om je bedrijf in beeld te brengen.
                    </p>
                    <!-- Stats -->
                    <div class="mb-6 grid grid-cols-3 gap-4 border-y border-gray-200 py-6">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 md:text-3xl">2005</p>
                            <p class="text-sm text-gray-500">Opgericht</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 md:text-3xl">25.000m²</p>
                            <p class="text-sm text-gray-500">Productiehal</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 md:text-3xl">100%</p>
                            <p class="text-sm text-gray-500">Eigen productie</p>
                        </div>
                    </div>
                    <a href="#" class="inline-flex items-center gap-2 font-medium text-primary-600 hover:text-primary-700 hover:underline">
                        Meer over Drukhoek
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Exclusieve Bezorgdienst Section - Full Width Banner -->
    <section class="relative overflow-hidden bg-gray-900 py-12 md:py-20">
        <!-- Background Image with Overlay -->
        <div class="absolute inset-0">
            <img
                src="/images/drukhoek-bezorgdienst.jpg"
                alt="Drukhoek bezorgdienst"
                class="h-full w-full object-cover opacity-40"
            >
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900 via-gray-900/90 to-gray-900/70"></div>
        </div>

        <!-- Content -->
        <div class="relative mx-auto max-w-screen-2xl px-4 2xl:px-0">
            <div class="lg:flex lg:items-center lg:gap-16">
                <!-- Left: Text Content -->
                <div class="lg:w-1/2">
                    <span class="mb-4 inline-flex items-center gap-2 rounded-full bg-primary-600/20 px-4 py-1.5 text-sm font-medium text-primary-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        Exclusief voor professionals
                    </span>
                    <h2 class="mb-4 text-3xl font-bold tracking-tight text-white md:text-4xl lg:text-5xl">
                        Exclusieve bezorgdienst
                    </h2>
                    <p class="mb-8 text-lg text-gray-300">
                        Kies naast PostNL en DHL ook voor onze whitelabel bezorgdienst, exclusief voor printprofessionals.
                    </p>

                    <!-- Feature Cards Grid -->
                    <div class="mb-8 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="mb-1 font-semibold text-white">Betrouwbaar</h3>
                            <p class="text-sm text-gray-400">Zorgvuldige bezorging</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h3 class="mb-1 font-semibold text-white">Evenementen</h3>
                            <p class="text-sm text-gray-400">Bezorgt op beurzen</p>
                        </div>
                        <div class="rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-600">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="mb-1 font-semibold text-white">Voordelig</h3>
                            <p class="text-sm text-gray-400">Scherpe tarieven</p>
                        </div>
                    </div>

                    <a href="#" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-semibold text-gray-900 transition hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-white/30">
                        Meer over bezorging
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>

                <!-- Right: Decorative/Stats -->
                <div class="mt-10 lg:mt-0 lg:w-1/2">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-sm">
                            <p class="text-4xl font-bold text-white md:text-5xl">24u</p>
                            <p class="mt-2 text-sm text-gray-400">Levergarantie</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-sm">
                            <p class="text-4xl font-bold text-white md:text-5xl">NL</p>
                            <p class="mt-2 text-sm text-gray-400">Heel Nederland</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-sm">
                            <p class="text-4xl font-bold text-white md:text-5xl">0%</p>
                            <p class="mt-2 text-sm text-gray-400">Schade bij levering</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-6 text-center backdrop-blur-sm">
                            <p class="text-4xl font-bold text-white md:text-5xl">
                                <svg class="mx-auto h-10 w-10 md:h-12 md:w-12 text-success-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </p>
                            <p class="mt-2 text-sm text-gray-400">Whitelabel service</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

  <section class="bg-gray-50 py-8 antialiased md:py-16">
  <div class="mx-auto max-w-screen-2xl items-center gap-8 px-4 lg:grid lg:grid-cols-2 xl:gap-16 2xl:px-0">
    <div class="text-base text-gray-500">
      <h2 class="mb-4 text-2xl font-bold tracking-tight text-gray-900 md:text-4xl">Start your shopping journey</h2>
      <p class="mb-8 md:text-xl">Find what speaks to you, and embark on a shopping experience that's both convenient and enjoyable.</p>
      <div class="mb-6 border-b border-t border-gray-200 py-8">
        <div class="flex">
          <div class="bg-white-100 me-4 flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-gray-200">
            <svg class="h-6 w-6 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M18.796 4H5.204a1 1 0 0 0-.753 1.659l5.302 6.058a1 1 0 0 1 .247.659v4.874a.5.5 0 0 0 .2.4l3 2.25a.5.5 0 0 0 .8-.4v-7.124a1 1 0 0 1 .247-.659l5.302-6.059c.566-.646.106-1.658-.753-1.658Z" />
            </svg>
          </div>
          <div>
            <h3 class="mb-2 text-xl font-semibold text-gray-900">Advanced Filtering</h3>
            <p class="mb-2 text-gray-500">Easy-to-use advanced filtering options (by category, price, brand, etc.) to help customers find products quickly.</p>
            <a href="#" class="inline-flex items-center font-medium text-primary-700 hover:underline">
              Learn more
              <svg class="ms-1 h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
              </svg>
            </a>
          </div>
        </div>
        <div class="flex pt-8">
          <div class="bg-white-100 me-4 flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-gray-200">
            <svg class="h-6 w-6 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 12a28.076 28.076 0 0 1-1.091 9M7.231 4.37a8.994 8.994 0 0 1 12.88 3.73M2.958 15S3 14.577 3 12a8.949 8.949 0 0 1 1.735-5.307m12.84 3.088A5.98 5.98 0 0 1 18 12a30 30 0 0 1-.464 6.232M6 12a6 6 0 0 1 9.352-4.974M4 21a5.964 5.964 0 0 1 1.01-3.328 5.15 5.15 0 0 0 .786-1.926m8.66 2.486a13.96 13.96 0 0 1-.962 2.683M7.5 19.336C9 17.092 9 14.845 9 12a3 3 0 1 1 6 0c0 .749 0 1.521-.031 2.311M12 12c0 3 0 6-2 9"
              />
            </svg>
          </div>
          <div>
            <h3 class="mb-2 text-xl font-semibold text-gray-900">Secure Payment</h3>
            <p class="mb-2 text-gray-500">Integration with trusted payment gateways (such as PayPal, Stripe, etc.) to ensure safe and secure transactions for customers.</p>
            <a href="#" class="inline-flex items-center font-medium text-primary-700 hover:underline">
              Learn more
              <svg class="ms-1 h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
              </svg>
            </a>
          </div>
        </div>
        <div class="flex pt-8">
          <div class="bg-white-100 me-4 flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-gray-200">
            <svg class="h-6 w-6 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h6l2 4m-8-4v8m0-8V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v9h2m8 0H9m4 0h2m4 0h2v-4m0 0h-5m3.5 5.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Zm-10 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z" />
            </svg>
          </div>
          <div>
            <h3 class="mb-2 text-xl font-semibold text-gray-900">Shipping Options</h3>
            <p class="mb-2 text-gray-500">Multiple shipping methods with real-time shipping cost calculation and tracking information provided to customers.</p>
            <a href="#" class="inline-flex items-center font-medium text-primary-700 hover:underline">
              Learn more
              <svg class="ms-1 h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4" />
              </svg>
            </a>
          </div>
        </div>
      </div>
      <p>Deliver great service experiences - without the complexity of traditional shops.</p>
    </div>
    <div class="hidden lg:flex">
      <img class="mb-4 w-full rounded-lg lg:mb-0" src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/store.svg" alt="store image" />
      <img class="mb-4 hidden w-full rounded-lg lg:mb-0" src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/store-dark.svg" alt="store image" />
    </div>
  </div>
</section>
</div>
