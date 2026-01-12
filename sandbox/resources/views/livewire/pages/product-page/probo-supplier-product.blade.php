<div class="w-full">
    @php
        $locale = app()->getLocale();

        // Get all translatable data
        $notificationsData = $product->translateAttribute('notifications');
        $notifications = collect($notificationsData[$locale] ?? $notificationsData['en'] ?? [])->values()->toArray();

        $benefitsData = $product->translateAttribute('product-benefits');
        $productBenefits = collect($benefitsData[$locale] ?? $benefitsData['en'] ?? [])->values()->toArray();

        $prosData = $product->translateAttribute('product-pros');
        $productPros = collect($prosData[$locale] ?? $prosData['en'] ?? [])->values()->toArray();

        $consData = $product->translateAttribute('product-cons');
        $productCons = collect($consData[$locale] ?? $consData['en'] ?? [])->values()->toArray();

        $downloadsData = $product->translateAttribute('downloads');
        $downloads = collect($downloadsData[$locale] ?? $downloadsData['en'] ?? [])->values()->toArray();

        $faqData = $product->translateAttribute('faq');
        $faqItems = collect($faqData[$locale] ?? $faqData['en'] ?? [])->values()->toArray();

        $optionAlertsData = $product->translateAttribute('option-alerts');
        $optionAlerts = collect($optionAlertsData[$locale] ?? $optionAlertsData['en'] ?? [])
            ->values()
            ->map(fn($alert) => ['message' => $alert['message'] ?? $alert, 'type' => 'warning'])
            ->toArray();

        $description = $product->translateAttribute('description');
        $specificatiesPath = $product->translateAttribute('specificaties');

        // Probo-specific attributes
        $deliveryInfoTitle = $product->translateAttribute('delivery-info-title');
        $deliveryInfoSubtitle = $product->translateAttribute('delivery-info-subtitle');
        $menuLabel = $product->translateAttribute('menu-label');
        $menuPassportLabel = $product->translateAttribute('menu-passport-label');
        $hidePricelistAttr = $product->attr('hide-pricelist');
        $hidePricelist = $hidePricelistAttr && $hidePricelistAttr !== false ? $hidePricelistAttr->getValue() : false;
        $hasSampleAttr = $product->attr('has-sample');
        $hasSample = $hasSampleAttr && $hasSampleAttr !== false ? $hasSampleAttr->getValue() : false;

        // Pinterest URL
        $pinterestUrl = $product->attr('pinterest-url')?->getValue();
    @endphp

    {{-- Product Notifications --}}
    <x-product-notification
        :notifications="$notifications"
        class="max-w-screen-xl mx-auto px-4 pt-4"
    />

    {{-- Main Product Section --}}
    <section class="py-8 bg-white md:py-16 antialiased">
        <div class="max-w-screen-xl mx-auto">
            <div class="lg:flex justify-between">
                {{-- Left Column: Image Gallery --}}
                <div class="px-4">
                    <div class="max-w-md lg:max-w-none mx-auto flex flex-col lg:flex-row justify-center mb-4">
                        {{-- Thumbnail Navigation --}}
                        <div class="order-2 lg:order-1 mt-8 lg:mt-0">
                            {{-- Mobile: horizontal scroll --}}
                            <div class="lg:hidden overflow-x-auto pb-2 -mx-4 px-4">
                                <ul class="flex gap-3 w-max">
                                    @foreach($this->productImages as $index => $image)
                                    <li class="shrink-0">
                                        <button
                                            wire:click="setActiveImage({{ $index }})"
                                            @class([
                                                'h-20 w-20 overflow-hidden border-2 rounded-lg p-2 cursor-pointer transition-colors',
                                                'border-primary-500' => $activeImageIndex === $index,
                                                'border-gray-200 hover:border-gray-300' => $activeImageIndex !== $index,
                                            ])
                                            type="button"
                                        >
                                            @if($image['thumb'])
                                                <img class="object-contain w-full h-full" src="{{ $image['thumb'] }}" alt="{{ $image['alt'] }}" />
                                            @else
                                                <div class="w-full h-full bg-gray-100 flex items-center justify-center rounded">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </button>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- Desktop: vertical scroll showing exactly 4 thumbnails --}}
                            @php $totalImages = count($this->productImages); @endphp
                            <div class="hidden lg:flex lg:flex-col lg:items-center relative"
                                x-data="{
                                    atBottom: false,
                                    atTop: true,
                                    checkScroll(el) {
                                        this.atTop = el.scrollTop < 10;
                                        this.atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 10;
                                    }
                                }"
                            >
                                {{-- Scroll container --}}
                                <div
                                    class="h-[432px] overflow-y-auto overflow-x-hidden"
                                    style="scrollbar-width: thin;"
                                    x-ref="scrollContainer"
                                    @scroll="checkScroll($el)"
                                    x-init="checkScroll($refs.scrollContainer)"
                                >
                                    <ul class="flex flex-col gap-4 pr-2">
                                        @foreach($this->productImages as $index => $image)
                                        <li class="shrink-0">
                                            <button
                                                wire:click="setActiveImage({{ $index }})"
                                                @class([
                                                    'h-24 w-24 overflow-hidden border-2 rounded-lg p-2 cursor-pointer transition-colors',
                                                    'border-primary-500' => $activeImageIndex === $index,
                                                    'border-gray-200 hover:border-gray-300' => $activeImageIndex !== $index,
                                                ])
                                                type="button"
                                            >
                                                @if($image['thumb'])
                                                    <img class="object-contain w-full h-full" src="{{ $image['thumb'] }}" alt="{{ $image['alt'] }}" />
                                                @else
                                                    <div class="w-full h-full bg-gray-100 flex items-center justify-center rounded">
                                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </button>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>

                                {{-- Bottom fade + scroll indicator (shows when not at bottom) --}}
                                @if($totalImages > 4)
                                <div
                                    x-show="!atBottom"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="absolute bottom-0 left-0 right-2 h-16 bg-gradient-to-t from-white to-transparent pointer-events-none flex items-end justify-center pb-1"
                                >
                                    <span class="text-xs text-gray-500 bg-white/80 px-2 py-1 rounded-full flex items-center gap-1 pointer-events-auto">
                                        <svg class="w-3 h-3 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                        </svg>
                                        +{{ $totalImages - 4 }} meer
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Main Image Display --}}
                        <div class="order-1 lg:order-2">
                            @foreach($this->productImages as $index => $image)
                            <div class="{{ $activeImageIndex === $index ? '' : 'hidden' }} px-4 rounded-lg bg-white">
                                @if($image['url'])
                                    <img class="w-full max-w-md mx-auto" src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" />
                                @else
                                    <div class="w-full max-w-md mx-auto aspect-square bg-gray-100 flex items-center justify-center rounded-lg">
                                        <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Right Column: Product Info Card --}}
                <div class="w-full mt-6 lg:max-w-lg lg:mt-0 shrink-0 px-4">
                    <div class="p-4 border border-gray-200 rounded-lg sm:p-6 lg:p-8 bg-gray-50">
                        {{-- Menu Label Badge (Nieuw, Populair, etc.) --}}
                        @if($menuLabel)
                        <span class="inline-flex items-center px-2.5 py-0.5 mb-3 text-xs font-medium rounded-full bg-red-100 text-red-800">
                            {{ $menuLabel }}
                        </span>
                        @endif

                        {{-- Product Title --}}
                        <h1 class="text-xl font-semibold text-gray-900">
                            {{ $product->translateAttribute('name') }}
                        </h1>

                        {{-- Menu Passport Label (product subtitle) --}}
                        @if($menuPassportLabel)
                        <p class="mt-1 text-sm text-gray-500">{{ $menuPassportLabel }}</p>
                        @endif

                        {{-- Short Description --}}
                        @if($shortDescription = $product->translateAttribute('short_description'))
                        <p class="mt-4 text-gray-500">
                            {{ strip_tags($shortDescription) }}
                        </p>
                        @endif

                        {{-- Delivery Info --}}
                        @if($deliveryInfoTitle || $deliveryInfoSubtitle)
                        <div class="flex items-center gap-3 mt-4 p-4 bg-gray-50 rounded-full border border-gray-200" x-data="{ showDeliveryTooltip: false }">
                            <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-lg shrink-0">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                @if($deliveryInfoTitle)
                                <p class="text-sm font-semibold text-gray-900">{{ $deliveryInfoTitle }}</p>
                                @endif
                                @if($deliveryInfoSubtitle)
                                <p class="text-sm text-gray-600 flex items-center flex-wrap">
                                    <span>{{ __('Bestel op werkdagen voor') }} <span class="text-red-600 font-medium">21.30 uur</span></span>
                                    <span class="relative ml-1">
                                        <button
                                            type="button"
                                            class="text-blue-600 hover:text-blue-700"
                                            @mouseenter="showDeliveryTooltip = true"
                                            @mouseleave="showDeliveryTooltip = false"
                                            @focus="showDeliveryTooltip = true"
                                            @blur="showDeliveryTooltip = false"
                                        >
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                        <div
                                            x-show="showDeliveryTooltip"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 translate-y-1"
                                            x-cloak
                                            class="absolute right-0 top-full mt-2 w-72 p-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
                                        >
                                            {{ __('Bestel je tussen 16.30 en 21.30 uur voor bezorging de volgende dag, dan kun je alleen kiezen voor Bezorgdienst ONB of afhalen.') }}
                                            <br><br>
                                            {{ __('Bezorgen en afhalen is mogelijk van maandag t/m zaterdag.') }}
                                        </div>
                                    </span>
                                </p>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- Tier Pricing Table --}}
                        @if(!$hidePricelist && $this->hasPriceBreaks)
                        @php
                            $unitSymbol = $this->unitCode?->symbol() ?? 'st';
                            $priceBreaks = $this->priceBreaks;
                        @endphp
                        <div class="mt-4 md:mt-6">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm text-gray-700">
                                    {{ __('Vanafprijs voor:') }} <span class="font-medium">{{ $product->translateAttribute('name') }}</span>
                                </p>
                                <div class="flex items-center gap-2">
                                    <div class="relative" x-data="{ showTooltip: false }">
                                        <button
                                            type="button"
                                            class="text-blue-600 hover:text-blue-700 p-1"
                                            @mouseenter="showTooltip = true"
                                            @mouseleave="showTooltip = false"
                                            @focus="showTooltip = true"
                                            @blur="showTooltip = false"
                                        >
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                        <div
                                            x-show="showTooltip"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 translate-y-1"
                                            x-transition:enter-end="opacity-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100 translate-y-0"
                                            x-transition:leave-end="opacity-0 translate-y-1"
                                            x-cloak
                                            class="absolute right-0 top-full mt-2 w-72 p-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
                                        >
                                            {{ __('Hier tonen we de prijs per m² van dit materiaal inclusief snijden of standaard afwerking. Aanvullende opties hebben mogelijk een meerprijs. Stel je product samen om jouw definitieve prijs te berekenen.') }}
                                        </div>
                                    </div>
                                    <a href="#" class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                                        <span>&gt;</span> {{ __('Bekijk prijslijst') }}
                                    </a>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-x-4 gap-y-2 border-t border-gray-200 pt-3">
                                @foreach($priceBreaks as $priceBreak)
                                @php
                                    $priceDecimal = $priceBreak->price->decimal;
                                    $euros = floor($priceDecimal);
                                    $cents = round(($priceDecimal - $euros) * 100);
                                @endphp
                                <div class="text-center min-w-15">
                                    <p class="text-sm text-gray-600">
                                        {{ number_format($priceBreak->min_quantity, $priceBreak->min_quantity < 1 ? 1 : 0, ',', '.') }} {{ $unitSymbol }}
                                    </p>
                                    <p class="text-red-600 leading-tight">
                                        <span class="text-sm">€</span>
                                        <span class="text-xl font-bold">{{ $euros }}</span><sup class="text-xs font-bold">,{{ str_pad($cents, 2, '0', STR_PAD_LEFT) }}</sup>
                                    </p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @elseif($price && !$hidePricelist)
                        {{-- Simple price display when no tier pricing --}}
                        <div class="gap-4 mt-4 md:mt-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">{{ __('Vanaf') }}</p>
                                <p class="text-2xl font-extrabold text-gray-900 sm:text-3xl">
                                    {{ $price->price->formatted() }}
                                </p>
                            </div>
                        </div>
                        @endif

                        {{-- CTA Buttons --}}
                        <div class="gap-4 mt-4 md:mt-6 sm:flex sm:items-center lg:flex-col">
                            <a
                                href="#configurator-section"
                                onclick="event.preventDefault(); document.getElementById('configurator-section').scrollIntoView({ behavior: 'smooth' })"
                                class="text-white w-full bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none flex items-center justify-center"
                            >
                                <svg class="w-5 h-5 -ms-2 me-2" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h1.5L8 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm.75-3H7.5M11 7H6.312M17 4v6m-3-3h6"/>
                                </svg>
                                Begin met bestellen
                            </a>

                            <a
                                href="#"
                                class="flex items-center w-full justify-center py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-100 mt-4 sm:mt-0 lg:mt-4"
                            >
                                <svg class="w-5 h-5 -ms-2 me-2" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.01 6.001C6.5 1 1 8 5.782 13.001L12.011 20l6.23-7C23 8 17.5 1 12.01 6.002Z"/>
                                </svg>
                                {{ __('Toevoegen aan favorieten') }}
                            </a>

                            @if($hasSample)
                            <a
                                href="#"
                                class="flex items-center w-full justify-center py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-100 mt-4 sm:mt-0 lg:mt-4"
                            >
                                <svg class="w-5 h-5 -ms-2 me-2" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                {{ __('Bestel een monster') }}
                            </a>
                            @endif
                        </div>

                        {{-- Features / Product Benefits (in card) --}}
                        @if(count($productBenefits) > 0)
                        <div class="pt-6 mt-6 space-y-4 border-t border-gray-200">
                            <ul class="space-y-3 text-sm">
                                @foreach($productBenefits as $benefit)
                                <li class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-gray-600">{!! $benefit['value'] ?? $benefit !!}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Sticky Navigation Bar --}}
    <nav id="nav-sections" aria-label="Pagina secties" class="sticky top-0 z-40 bg-white border-b border-gray-200 shadow-sm"
        x-data="{
            scrollTo(id) {
                const element = document.getElementById(id);
                if (element) {
                    const navHeight = this.$el.offsetHeight;
                    const elementPosition = element.getBoundingClientRect().top + window.scrollY;
                    window.scrollTo({
                        top: elementPosition - navHeight - 8,
                        behavior: 'smooth'
                    });
                }
            }
        }"
    >
        <div class="max-w-screen-xl mx-auto px-4">
            <ul class="flex gap-1 overflow-x-auto text-sm font-medium py-1 scrollbar-hide">
                <li>
                    <a id="nav-configurator" href="#configurator-section" @click.prevent="scrollTo('configurator-section')" class="inline-flex items-center px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                        </svg>
                        Configurator
                    </a>
                </li>
                @if($description)
                <li>
                    <a id="nav-description" href="#section-description" @click.prevent="scrollTo('section-description')" class="inline-flex items-center px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z"/>
                        </svg>
                        Omschrijving
                    </a>
                </li>
                @endif
                @if(count($productBenefits) > 0)
                <li>
                    <a id="nav-benefits" href="#section-benefits" @click.prevent="scrollTo('section-benefits')" class="inline-flex items-center px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Productvoordelen
                    </a>
                </li>
                @endif
                @if(count($productPros) > 0 || count($productCons) > 0)
                <li>
                    <a id="nav-pros-cons" href="#section-pros-cons" @click.prevent="scrollTo('section-pros-cons')" class="inline-flex items-center px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                        </svg>
                        Plus- en minpunten
                    </a>
                </li>
                @endif
                @if($specificatiesPath)
                <li>
                    <a id="nav-specifications" href="#section-specifications" @click.prevent="scrollTo('section-specifications')" class="inline-flex items-center px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                        Specificaties
                    </a>
                </li>
                @endif
                @if(count($downloads) > 0)
                <li>
                    <a id="nav-downloads" href="#section-downloads" @click.prevent="scrollTo('section-downloads')" class="inline-flex items-center px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Downloads
                    </a>
                </li>
                @endif
                @if(count($faqItems) > 0)
                <li>
                    <a id="nav-faq" href="#section-faq" @click.prevent="scrollTo('section-faq')" class="inline-flex items-center px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        Veelgestelde vragen
                    </a>
                </li>
                @endif
                @if($pinterestUrl)
                <li>
                    <a id="nav-pinterest" href="#section-pinterest" @click.prevent="scrollTo('section-pinterest')" class="inline-flex items-center px-4 py-2.5 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-primary-600 transition-colors whitespace-nowrap">
                        <svg class="w-4 h-4 me-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0C4.477 0 0 4.477 0 10c0 4.237 2.636 7.855 6.356 9.312-.088-.791-.167-2.005.035-2.868.181-.78 1.172-4.97 1.172-4.97s-.299-.598-.299-1.482c0-1.388.805-2.425 1.808-2.425.852 0 1.264.64 1.264 1.408 0 .858-.546 2.14-.828 3.33-.236.995.5 1.807 1.48 1.807 1.778 0 3.144-1.874 3.144-4.58 0-2.393-1.72-4.068-4.177-4.068-2.845 0-4.515 2.135-4.515 4.34 0 .859.331 1.781.745 2.281a.3.3 0 01.069.288l-.278 1.133c-.044.183-.145.223-.335.134-1.249-.581-2.03-2.407-2.03-3.874 0-3.154 2.292-6.052 6.608-6.052 3.469 0 6.165 2.473 6.165 5.776 0 3.447-2.173 6.22-5.19 6.22-1.013 0-1.965-.527-2.291-1.148l-.623 2.378c-.226.869-.835 1.958-1.244 2.621.937.29 1.931.446 2.962.446 5.523 0 10-4.477 10-10S15.523 0 10 0z"/>
                        </svg>
                        Inspiratie
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </nav>

    {{-- Configurator Section --}}
    <section id="configurator-section" class="scroll-mt-14 bg-gray-50 py-8 border-b border-gray-200">
        <div class="max-w-screen-xl mx-auto px-4">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <span class="flex items-center justify-center w-8 h-8 bg-primary-600 text-white rounded-full text-sm font-bold">1</span>
                    <h2 class="text-lg font-semibold text-gray-900">Product samenstellen</h2>
                </div>

                {{-- Option Alerts --}}
                <x-product-notification :notifications="$optionAlerts" class="mb-6" />

                {{-- Probo Configurator Component --}}
                @if($this->showConfigurator)
                    @php $configuratorClass = $this->getConfiguratorComponent(); @endphp
                    @if($configuratorClass)
                        @livewire($configuratorClass, [
                            'supplierProductId' => $this->supplierProductId,
                            'productVariantId' => $selectedVariant->id,
                        ], key('configurator-'.$selectedVariant->id))
                    @else
                        <p class="text-gray-500">Configurator niet beschikbaar.</p>
                    @endif
                @elseif($this->isPreConfigured && $selectedVariant?->configuration)
                    {{-- Pre-configured variant --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        @foreach($selectedVariant->configuration as $key => $value)
                            @if(!in_array($key, ['hash', 'quantity', '_skip']) && !str_ends_with($key, '_skip'))
                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ ucwords(str_replace(['_', '-'], ' ', $key)) }}
                                </span>
                                <div class="flex">
                                    <input type="text" value="{{ $value }}" readonly class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-l-lg block w-full p-2.5">
                                    <span class="inline-flex items-center px-3 text-sm text-gray-500 bg-gray-200 border border-l-0 border-gray-300 rounded-r-lg">cm</span>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>

                                    {{-- Quantity and Add to Cart --}}
                <div class="flex flex-wrap items-end gap-4 pt-4 border-t border-gray-200">
                    <div>
                        <span class="block text-sm font-medium text-gray-700 mb-1">Aantal</span>
                        <div class="flex">
                            <button wire:click="decrementQuantity" class="px-3 py-2.5 bg-gray-100 border border-gray-300 rounded-l-lg hover:bg-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                            </button>
                            <input type="number" wire:model="quantity" min="1" class="w-16 text-center border-t border-b border-gray-300 bg-white text-gray-900 py-2">
                            <button wire:click="incrementQuantity" class="px-3 py-2.5 bg-gray-100 border border-gray-300 rounded-r-lg hover:bg-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </button>
                        </div>
                    </div>
                    @if($price)
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Totaal</p>
                        <p class="text-lg font-bold text-gray-900">{{ $price->price->formatted() }}</p>
                    </div>
                    @endif
                    <button
                        wire:click="addToCart"
                        wire:loading.attr="disabled"
                        @disabled(!$selectedVariant)
                        @class([
                            'flex-1 font-medium rounded-lg text-sm px-5 py-2.5 flex items-center justify-center transition-colors',
                            'text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 focus:outline-none' => !$added && $selectedVariant,
                            'text-white bg-green-600' => $added,
                            'bg-gray-300 text-gray-500 cursor-not-allowed' => !$selectedVariant,
                        ])
                    >
                        <svg class="w-5 h-5 -ms-2 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        @if($added) Toegevoegd! @else Toevoegen aan winkelwagen @endif
                    </button>
                </div>
                @elseif($this->requiresDimensions && !$this->showConfigurator)
                    {{-- Dimension Inputs for dimensional products (m², m, cm², etc.) --}}
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 mb-4">
                            @if($this->dimensionType === 'area')
                                Voer afmetingen in
                            @else
                                Voer lengte in
                            @endif
                        </h3>

                        @if($this->dimensionType === 'area')
                            {{-- Size Presets --}}
                            <div class="mb-4">
                                <p class="text-xs text-gray-500 mb-2">Snel selecteren:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($sizePresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="selectSizePreset({{ $preset['width'] }}, {{ $preset['height'] }})"
                                        @class([
                                            'px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors',
                                            'border-primary-600 bg-primary-50 text-primary-700' => $dimensionWidth == $preset['width'] && $dimensionHeight == $preset['height'],
                                            'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' => !($dimensionWidth == $preset['width'] && $dimensionHeight == $preset['height']),
                                        ])
                                    >
                                        {{ $preset['label'] }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Width and Height Inputs --}}
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="dimension-width" class="block text-sm font-medium text-gray-700 mb-1">
                                        Breedte ({{ $this->inputUnit }})
                                    </label>
                                    <input
                                        type="number"
                                        id="dimension-width"
                                        wire:model.live="dimensionWidth"
                                        min="1"
                                        step="0.1"
                                        placeholder="100"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-primary-500 focus:border-primary-500"
                                    >
                                </div>
                                <div>
                                    <label for="dimension-height" class="block text-sm font-medium text-gray-700 mb-1">
                                        Hoogte ({{ $this->inputUnit }})
                                    </label>
                                    <input
                                        type="number"
                                        id="dimension-height"
                                        wire:model.live="dimensionHeight"
                                        min="1"
                                        step="0.1"
                                        placeholder="200"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-primary-500 focus:border-primary-500"
                                    >
                                </div>
                            </div>
                        @else
                            {{-- Length Input for linear units --}}
                            <div class="mb-4">
                                <label for="dimension-length" class="block text-sm font-medium text-gray-700 mb-1">
                                    Lengte ({{ $this->inputUnit }})
                                </label>
                                <input
                                    type="number"
                                    id="dimension-length"
                                    wire:model.live="dimensionLength"
                                    min="0.1"
                                    step="0.1"
                                    placeholder="5.5"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-primary-500 focus:border-primary-500"
                                >
                            </div>
                        @endif

                        {{-- Calculated Size & Price Display --}}
                        @if($this->calculatedSize && $this->calculatedPrice)
                        <div class="p-4 bg-primary-50 rounded-lg border border-primary-200 mb-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        {{ number_format($this->calculatedSize, 2) }} {{ $this->unitCode->symbol() }}
                                        @if($this->matchedPrice)
                                            <span class="text-gray-400">×</span>
                                            {{ $this->matchedPrice->price->unitFormattedWithCode() }}
                                        @endif
                                    </p>
                                    @if($quantity > 1)
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $quantity }} items × {{ number_format($this->calculatedSize, 2) }} {{ $this->unitCode->symbol() }} = {{ number_format($this->calculatedSize * $quantity, 2) }} {{ $this->unitCode->symbol() }} totaal
                                    </p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-bold text-primary-600">
                                        {{ $this->calculatedPrice->formatted() }}
                                    </p>
                                    @if($quantity > 1)
                                    <p class="text-sm text-gray-500">
                                        Totaal: {{ (new \Lunar\DataTypes\Price($this->calculatedPrice->value * $quantity, $this->calculatedPrice->currency, 1))->formatted() }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @elseif($this->requiresDimensions)
                        <div class="p-4 bg-gray-100 rounded-lg mb-4">
                            <p class="text-sm text-gray-500 text-center">
                                @if($this->dimensionType === 'area')
                                    Voer breedte en hoogte in om prijs te berekenen
                                @else
                                    Voer lengte in om prijs te berekenen
                                @endif
                            </p>
                        </div>
                        @endif

                        {{-- Quantity and Add to Cart --}}
                        <div class="flex flex-wrap items-end gap-4 pt-4 border-t border-gray-200">
                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-1">Aantal</span>
                                <div class="flex">
                                    <button wire:click="decrementQuantity" class="px-3 py-2.5 bg-gray-100 border border-gray-300 rounded-l-lg hover:bg-gray-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                    </button>
                                    <input type="number" wire:model="quantity" min="1" class="w-16 text-center border-t border-b border-gray-300 bg-white text-gray-900 py-2">
                                    <button wire:click="incrementQuantity" class="px-3 py-2.5 bg-gray-100 border border-gray-300 rounded-r-lg hover:bg-gray-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <button
                                wire:click="addToCart"
                                wire:loading.attr="disabled"
                                @disabled(!$this->canAddToCart)
                                @class([
                                    'flex-1 font-medium rounded-lg text-sm px-5 py-2.5 flex items-center justify-center transition-colors',
                                    'text-white bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 focus:outline-none' => !$added && $this->canAddToCart,
                                    'text-white bg-green-600' => $added,
                                    'bg-gray-300 text-gray-500 cursor-not-allowed' => !$this->canAddToCart,
                                ])
                            >
                                <svg class="w-5 h-5 -ms-2 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                @if($added) Toegevoegd! @else Toevoegen aan winkelwagen @endif
                            </button>
                        </div>
                    </div>
                @endif


            </div>
        </div>
    </section>

    {{-- All Content Sections Visible (SEO-friendly) --}}
    <div class="bg-white w-full">
        <div class="max-w-screen-xl w-full mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 w-full">
                {{-- Main Content Column (8 cols = 2/3 width) --}}
                <div class="lg:col-span-8 space-y-12 w-full">
                    {{-- Description Section --}}
                    @if($description)
                    <section id="section-description" class="scroll-mt-20">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ $product->translateAttribute('name') }}</h2>
                        <div class="prose prose-sm max-w-none">
                            {!! $description !!}
                        </div>
                    </section>
                    @endif

                    {{-- Product Benefits Section --}}
                    @if(count($productBenefits) > 0)
                    <section id="section-benefits" class="scroll-mt-20">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Productvoordelen</h2>
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach($productBenefits as $benefit)
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-full shrink-0 me-3">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <span class="text-gray-700">{!! $benefit['value'] ?? $benefit !!}</span>
                            </div>
                            @endforeach
                        </div>
                    </section>
                    @endif

                    {{-- Pros and Cons Section (Plus- en minpunten) --}}
                    @if(count($productPros) > 0 || count($productCons) > 0)
                    <section id="section-pros-cons" class="scroll-mt-20">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Plus- en minpunten</h2>
                        <div class="space-y-3">
                            {{-- Pros (Pluspunten) --}}
                            @foreach($productPros as $pro)
                            <div class="flex items-start gap-3">
                                <span class="flex items-center justify-center w-5 h-5 shrink-0 mt-0.5 text-green-500">
                                    <svg height="20" viewBox="0 0 20 20" width="20" fill="currentColor">
                                        <path d="m10 18c4.4183 0 8-3.5817 8-8 0-4.41828-3.5817-8-8-8-4.41828 0-8 3.58172-8 8 0 4.4183 3.58172 8 8 8zm1-11c0-.55228-.4477-1-1-1-.55228 0-1 .44772-1 1v2h-2c-.55228 0-1 .44771-1 1 0 .5523.44772 1 1 1h2v2c0 .5523.44772 1 1 1 .5523 0 1-.4477 1-1v-2h2c.5523 0 1-.4477 1-1 0-.55228-.4477-1-1-1h-2z" fill-rule="evenodd"/>
                                    </svg>
                                </span>
                                <span class="text-sm text-gray-700">{!! $pro['value'] ?? $pro !!}</span>
                            </div>
                            @endforeach

                            {{-- Cons (Minpunten) --}}
                            @foreach($productCons as $con)
                            <div class="flex items-start gap-3">
                                <span class="flex items-center justify-center w-5 h-5 shrink-0 mt-0.5 text-gray-400">
                                    <svg height="20" viewBox="0 0 20 20" width="20" fill="currentColor">
                                        <path d="m10 18c4.4183 0 8-3.5817 8-8 0-4.41828-3.5817-8-8-8-4.41828 0-8 3.58172-8 8 0 4.4183 3.58172 8 8 8zm-3-9c-.55228 0-1 .44772-1 1 0 .5523.44772 1 1 1h6c.5523 0 1-.4477 1-1 0-.55228-.4477-1-1-1z" fill-rule="evenodd"/>
                                    </svg>
                                </span>
                                <span class="text-sm text-gray-700">{!! $con['value'] ?? $con !!}</span>
                            </div>
                            @endforeach
                        </div>
                    </section>
                    @endif

                    {{-- Specifications Section --}}
                    @if($specificatiesPath)
                    <section id="section-specifications" class="scroll-mt-20">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Technische specificaties</h2>
                        <div class="bg-gray-50 rounded-lg p-6 border border-gray-100">
                            <div class="prose prose-sm max-w-none [&_table]:w-full [&_table]:border-collapse [&_th]:bg-gray-100 [&_th]:dark:bg-gray-700 [&_th]:p-3 [&_th]:text-left [&_th]:font-medium [&_th]:text-gray-900 [&_th]:dark:text-white [&_td]:p-3 [&_td]:border-b [&_td]:border-gray-200 [&_td]:dark:border-gray-600 [&_tr:last-child_td]:border-0">
                                @if(view()->exists("specifications.finished.{$specificatiesPath}"))
                                    @include("specifications.finished.{$specificatiesPath}")
                                @else
                                    <strong>Specificaties niet beschikbaar.</strong>
                                @endif
                            </div>
                        </div>
                    </section>
                    @endif

                    {{-- FAQ Section --}}
                    @if(count($faqItems) > 0)
                    <section id="section-faq" class="scroll-mt-20">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Veelgestelde vragen</h2>
                        <div class="space-y-3" x-data="{ openFaq: 0 }">
                            @foreach($faqItems as $index => $faq)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <button
                                    type="button"
                                    @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                                    class="flex items-center justify-between w-full p-5 font-medium text-left text-gray-900 bg-gray-50 hover:bg-gray-100 transition-colors"
                                    :class="openFaq === {{ $index }} ? 'bg-gray-100' : ''"
                                >
                                    <span>{{ $faq['question'] ?? '' }}</span>
                                    <svg
                                        :class="openFaq === {{ $index }} ? 'rotate-180' : ''"
                                        class="w-5 h-5 shrink-0 transition-transform duration-200 text-gray-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div
                                    x-show="openFaq === {{ $index }}"
                                    x-collapse
                                    x-cloak
                                >
                                    <div class="p-5 border-t border-gray-200 bg-white">
                                        <div class="prose prose-sm max-w-none text-gray-600">
                                            {!! $faq['answer'] ?? '' !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </section>
                    @endif

                    {{-- Pinterest Inspiration Section --}}
                    @if($pinterestUrl)
                    <section id="section-pinterest" class="scroll-mt-20">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Inspiratie</h2>
                        <p class="text-gray-600 mb-6">Bekijk het inspiratiebord met toepassingen.</p>
                        <div class="flex w-100 flex-justify-center ml-auto mr-auto" data-type="pin">
                            <a data-pin-do="embedBoard"
                               data-pin-board-width="1032"
                               data-pin-scale-height="390"
                               data-pin-scale-width="80"
                               href="{{ $pinterestUrl }}">
                                <span class="sr-only">{{ __('Bekijk Pinterest inspiratiebord') }}</span>
                            </a>
                        </div>
                    </section>
                    @endif
                </div>

                {{-- Sidebar Column (4 cols = 1/3 width) --}}
                <div class="lg:col-span-4">
                    <div class="sticky top-20 space-y-6">
                        {{-- Downloads Card --}}
                        @if(count($downloads) > 0)
                        <div id="section-downloads" class="scroll-mt-20 bg-gray-50 rounded-lg p-6 border border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 me-2 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                Downloads
                            </h3>
                            <ul class="space-y-3">
                                @foreach($downloads as $download)
                                <li>
                                    <a href="{{ $download['url'] ?? '#' }}" target="_blank" rel="noopener" class="flex items-center p-3 bg-white rounded-lg border border-gray-200 hover:border-primary-500 hover:bg-primary-50 transition-colors group">
                                        <div class="flex items-center justify-center w-8 h-8 bg-primary-100 rounded shrink-0 me-3">
                                            <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 group-hover:text-primary-600 truncate">
                                            {{ $download['title'] ?? 'Download' }}
                                        </span>
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        {{-- Quick Contact Card --}}
                        <div class="bg-primary-50 rounded-lg p-6 border border-primary-100">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Hulp nodig?</h3>
                            <p class="text-sm text-gray-600 mb-4">Onze specialisten helpen u graag bij het maken van de juiste keuze.</p>
                            <a href="#" class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
                                <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Neem contact op
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- JSON-LD Structured Data for SEO --}}
    @push('scripts')
    @php
        $productName = $product->translateAttribute('name');
        $productDescription = strip_tags($product->translateAttribute('short_description') ?? $product->translateAttribute('description') ?? '');
        $productImage = $product->thumbnail?->getUrl('large');
        $productSku = $selectedVariant?->sku ?? $product->id;
        $priceAmount = $price?->price?->decimal ?? null;
        $priceCurrency = $price?->price?->currency?->code ?? 'EUR';
        $brand = $product->brand?->name ?? 'Drukhoek';
        $categoryName = $product->productType?->name ?? 'Products';

        // Build product schema
        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $productName,
            'description' => \Illuminate\Support\Str::limit($productDescription, 500),
            'sku' => $productSku,
            'brand' => [
                '@type' => 'Brand',
                'name' => $brand,
            ],
            'category' => $categoryName,
            'url' => url()->current(),
        ];

        // Add image if available
        if ($productImage) {
            $productSchema['image'] = $productImage;
        }

        // Add offers if price is available
        if ($priceAmount) {
            $productSchema['offers'] = [
                '@type' => 'Offer',
                'url' => url()->current(),
                'priceCurrency' => $priceCurrency,
                'price' => $priceAmount,
                'priceValidUntil' => now()->addYear()->format('Y-m-d'),
                'availability' => 'https://schema.org/InStock',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'Drukhoek',
                ],
            ];
        }

        // Build breadcrumb schema
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $categoryName,
                    'item' => localizedUrl('products.index'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $productName,
                    'item' => url()->current(),
                ],
            ],
        ];

        // Build FAQ schema if FAQ items exist
        $faqSchema = null;
        if (count($faqItems) > 0) {
            $faqSchema = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => collect($faqItems)->map(fn($faq) => [
                    '@type' => 'Question',
                    'name' => $faq['question'] ?? '',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($faq['answer'] ?? ''),
                    ],
                ])->values()->toArray(),
            ];
        }

        // Build organization schema
        $organizationSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Drukhoek',
            'url' => url('/'),
            'logo' => asset('images/drukhoek-logo.svg'),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+31-85-065-3647',
                'contactType' => 'customer service',
                'availableLanguage' => ['Dutch', 'English'],
            ],
        ];
    @endphp

    {{-- Product Schema --}}
    <script type="application/ld+json">
        {!! json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- Breadcrumb Schema --}}
    <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- FAQ Schema (if FAQ items exist) --}}
    @if($faqSchema)
    <script type="application/ld+json">
        {!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    @endif

    {{-- Organization Schema --}}
    <script type="application/ld+json">
        {!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- Pinterest SDK (only load if Pinterest URL exists) --}}
    @if($pinterestUrl)
    <script async defer src="//assets.pinterest.com/js/pinit.js"></script>
    @endif
    @endpush
</div>
