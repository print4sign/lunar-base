<div>
    <!-- Hero Section -->
    <section class="relative bg-gray-800">
        <div class="absolute inset-0">
            <img
                src="{{ asset('images/drukhoek-sample-aanvragen.png') }}"
                alt="Drukhoek samples"
                class="h-full w-full object-cover opacity-60"
            >
            <div class="absolute inset-0 bg-gradient-to-r from-gray-900/70 via-gray-900/50 to-transparent"></div>
        </div>
        <div class="relative mx-auto max-w-screen-xl px-4 py-16 md:py-24 2xl:px-0">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-extrabold tracking-tight text-white md:text-4xl lg:text-5xl">
                    Gratis samples aanvragen
                </h1>
                <p class="mt-4 text-lg text-gray-300 md:text-xl">
                    Materialen moet je voelen, zien en testen. Bestel jouw stalen uit ons assortiment naar keuze.
                </p>
            </div>
        </div>
    </section>

    @if($submitted)
        <!-- Success Message -->
        <section class="bg-white py-8 md:py-16">
            <div class="mx-auto max-w-screen-md px-4 text-center">
                <div class="mb-6 flex h-20 w-20 mx-auto items-center justify-center rounded-full bg-green-100">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 md:text-3xl">Bedankt voor je aanvraag!</h2>
                <p class="mt-4 text-gray-600">
                    We hebben je sample aanvraag ontvangen. Je ontvangt binnen enkele werkdagen je samples op het opgegeven adres.
                </p>
                <a href="{{ localizedUrl('home') }}" class="mt-8 inline-flex items-center justify-center gap-2 rounded-lg bg-primary-700 px-6 py-3 text-sm font-medium text-white hover:bg-primary-800">
                    Terug naar home
                </a>
            </div>
        </section>
    @else
        <!-- Sample Request Form -->
        <section class="bg-white py-8 md:py-16">
            <div class="mx-auto max-w-screen-xl px-4 2xl:px-0">
                <form wire:submit="submit">
                    <div class="lg:grid lg:grid-cols-12 lg:gap-12">
                        <!-- Left: Sample Selection -->
                        <div class="lg:col-span-7">
                            <h2 class="text-xl font-bold text-gray-900 mb-2">Selecteer je samples</h2>
                            <p class="text-sm text-gray-500 mb-6">Kies maximaal 10 materialen die je graag wilt ontvangen.</p>

                            @error('selected_samples')
                                <div class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-600">
                                    {{ $message }}
                                </div>
                            @enderror

                            <!-- Selected count -->
                            <div class="mb-6 flex items-center justify-between rounded-lg bg-gray-50 p-4">
                                <span class="text-sm text-gray-600">Geselecteerde samples:</span>
                                <span class="font-semibold text-gray-900">{{ count($selected_samples) }} / 10</span>
                            </div>

                            <!-- Sample Categories -->
                            <div class="space-y-6" x-data="{ openCategory: 'doek' }">
                                @foreach($sampleCategories as $categoryKey => $category)
                                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                                        <button
                                            type="button"
                                            @click="openCategory = openCategory === '{{ $categoryKey }}' ? null : '{{ $categoryKey }}'"
                                            class="flex w-full items-center justify-between bg-gray-50 px-4 py-3 text-left hover:bg-gray-100"
                                        >
                                            <span class="font-semibold text-gray-900">{{ $category['label'] }}</span>
                                            <div class="flex items-center gap-2">
                                                @php
                                                    $selectedInCategory = count(array_intersect($selected_samples, array_keys($category['materials'])));
                                                @endphp
                                                @if($selectedInCategory > 0)
                                                    <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700">
                                                        {{ $selectedInCategory }} geselecteerd
                                                    </span>
                                                @endif
                                                <svg
                                                    class="h-5 w-5 text-gray-500 transition-transform"
                                                    :class="{ 'rotate-180': openCategory === '{{ $categoryKey }}' }"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>
                                        </button>
                                        <div
                                            x-show="openCategory === '{{ $categoryKey }}'"
                                            x-collapse
                                            class="border-t border-gray-200"
                                        >
                                            <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-3">
                                                @foreach($category['materials'] as $materialKey => $materialLabel)
                                                    <label
                                                        class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 transition-colors
                                                            {{ in_array($materialKey, $selected_samples) ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50' }}
                                                            {{ count($selected_samples) >= 10 && !in_array($materialKey, $selected_samples) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            wire:click="toggleSample('{{ $materialKey }}')"
                                                            @checked(in_array($materialKey, $selected_samples))
                                                            @disabled(count($selected_samples) >= 10 && !in_array($materialKey, $selected_samples))
                                                            class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                        >
                                                        <span class="text-sm text-gray-700">{{ $materialLabel }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Right: Address Form -->
                        <div class="mt-8 lg:col-span-5 lg:mt-0">
                            <div class="sticky top-4 rounded-lg border border-gray-200 bg-gray-50 p-6">
                                <h2 class="text-xl font-bold text-gray-900 mb-6">Bezorggegevens</h2>

                                <div class="space-y-4">
                                    <!-- Company Name -->
                                    <div>
                                        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Bedrijfsnaam</label>
                                        <input
                                            type="text"
                                            id="company_name"
                                            wire:model="company_name"
                                            class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                            placeholder="Optioneel"
                                        >
                                    </div>

                                    <!-- Contact Person -->
                                    <div>
                                        <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Contactpersoon</label>
                                        <input
                                            type="text"
                                            id="contact_person"
                                            wire:model="contact_person"
                                            class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                            placeholder="Optioneel"
                                        >
                                    </div>

                                    <!-- Street & House Number -->
                                    <div class="grid grid-cols-3 gap-3">
                                        <div class="col-span-2">
                                            <label for="street" class="block text-sm font-medium text-gray-700 mb-1">Straatnaam <span class="text-red-500">*</span></label>
                                            <input
                                                type="text"
                                                id="street"
                                                wire:model="street"
                                                class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 @error('street') border-red-500 @enderror"
                                            >
                                            @error('street') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="house_number" class="block text-sm font-medium text-gray-700 mb-1">Huisnr. <span class="text-red-500">*</span></label>
                                            <input
                                                type="text"
                                                id="house_number"
                                                wire:model="house_number"
                                                class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 @error('house_number') border-red-500 @enderror"
                                            >
                                            @error('house_number') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <!-- Postal Code & City -->
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postcode <span class="text-red-500">*</span></label>
                                            <input
                                                type="text"
                                                id="postal_code"
                                                wire:model="postal_code"
                                                class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 @error('postal_code') border-red-500 @enderror"
                                            >
                                            @error('postal_code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="col-span-2">
                                            <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Plaatsnaam <span class="text-red-500">*</span></label>
                                            <input
                                                type="text"
                                                id="city"
                                                wire:model="city"
                                                class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 @error('city') border-red-500 @enderror"
                                            >
                                            @error('city') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                        </div>
                                    </div>

                                    <!-- Country -->
                                    <div>
                                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Land</label>
                                        <select
                                            id="country"
                                            wire:model="country"
                                            class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                                        >
                                            <option value="NL">Nederland</option>
                                            <option value="BE">Belgie</option>
                                            <option value="DE">Duitsland</option>
                                        </select>
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mailadres <span class="text-red-500">*</span></label>
                                        <input
                                            type="email"
                                            id="email"
                                            wire:model="email"
                                            class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 @error('email') border-red-500 @enderror"
                                        >
                                        @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Submit Button -->
                                    <button
                                        type="submit"
                                        class="mt-4 w-full rounded-lg bg-primary-700 px-5 py-3 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 disabled:bg-gray-300 disabled:cursor-not-allowed"
                                        @disabled(count($selected_samples) === 0)
                                    >
                                        <span wire:loading.remove wire:target="submit">
                                            Samples aanvragen ({{ count($selected_samples) }})
                                        </span>
                                        <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Bezig met verzenden...
                                        </span>
                                    </button>

                                    <p class="mt-3 text-xs text-gray-500 text-center">
                                        Gratis verzending. Samples worden binnen 3-5 werkdagen bezorgd.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Info Section -->
        <section class="bg-gray-50 py-8 md:py-16">
            <div class="mx-auto max-w-screen-xl px-4 2xl:px-0">
                <div class="grid gap-8 md:grid-cols-3">
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-100">
                            <svg class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">Gratis samples</h3>
                        <p class="text-sm text-gray-600">Ontvang tot 10 gratis materiaalstalen om de kwaliteit zelf te ervaren.</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-100">
                            <svg class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">Snelle levering</h3>
                        <p class="text-sm text-gray-600">Je samples worden binnen 3-5 werkdagen gratis thuisbezorgd.</p>
                    </div>
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-100">
                            <svg class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">Echte kwaliteit</h3>
                        <p class="text-sm text-gray-600">Voel, zie en test onze materialen voordat je een bestelling plaatst.</p>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
