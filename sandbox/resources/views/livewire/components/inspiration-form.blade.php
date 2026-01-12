<div class="max-w-2xl mx-auto">
    @if($submitted)
        <!-- Success State -->
        <div class="text-center py-12">
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-900">{{ __('inspiration.success.title') }}</h2>
            <p class="mt-2 text-gray-600">{{ __('inspiration.success.message') }}</p>
            <a href="{{ localizedUrl('inspirations.index') }}" class="mt-6 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                {{ __('inspiration.product.view_all') }}
            </a>
        </div>
    @elseif(!$order)
        <!-- No Order State -->
        <div class="text-center py-12">
            <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Link ongeldig of verlopen</h2>
            <p class="mt-2 text-gray-600">Deze link is niet meer geldig. Log in om je bestellingen te bekijken.</p>
        </div>
    @else
        <!-- Form -->
        <form wire:submit="submit" class="space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">{{ __('inspiration.form.title') }}</h2>

                <!-- Order Info -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">
                        <span class="font-medium">Bestelling:</span> {{ $order->reference }}
                    </p>
                    @if($order->productLines->first())
                        <p class="text-sm text-gray-600 mt-1">
                            <span class="font-medium">Product:</span> {{ $order->productLines->first()->description }}
                        </p>
                    @endif
                </div>

                <!-- Type Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('inspiration.form.type.label') }}</label>
                    <div class="grid grid-cols-2 gap-4">
                        <button
                            type="button"
                            wire:click="$set('type', 'review')"
                            class="p-4 border rounded-lg text-left transition {{ $type === 'review' ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500' : 'border-gray-200 hover:border-gray-300' }}"
                        >
                            <div class="font-medium {{ $type === 'review' ? 'text-primary-700' : 'text-gray-900' }}">{{ __('inspiration.form.type.review') }}</div>
                            <div class="text-sm text-gray-500 mt-1">Foto's + beoordeling + korte tekst</div>
                        </button>
                        <button
                            type="button"
                            wire:click="$set('type', 'case_study')"
                            class="p-4 border rounded-lg text-left transition {{ $type === 'case_study' ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-500' : 'border-gray-200 hover:border-gray-300' }}"
                        >
                            <div class="font-medium {{ $type === 'case_study' ? 'text-primary-700' : 'text-gray-900' }}">{{ __('inspiration.form.type.case_study') }}</div>
                            <div class="text-sm text-gray-500 mt-1">+ titel, bedrijfsnaam, projecttype</div>
                        </button>
                    </div>
                </div>

                <!-- Rating -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('inspiration.form.rating.label') }}</label>
                    <div class="flex gap-2">
                        @for ($i = 1; $i <= 5; $i++)
                            <button
                                type="button"
                                wire:click="$set('rating', {{ $i }})"
                                class="p-2 transition hover:scale-110"
                            >
                                <svg class="w-8 h-8 {{ $i <= $rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </button>
                        @endfor
                    </div>
                    @error('rating') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Case Study Fields -->
                @if($type === 'case_study')
                    <div class="mb-6 space-y-4 p-4 bg-purple-50 rounded-lg border border-purple-200">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">{{ __('inspiration.form.title_field.label') }}</label>
                            <input
                                type="text"
                                id="title"
                                wire:model="title"
                                placeholder="{{ __('inspiration.form.title_field.placeholder') }}"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                            >
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="companyName" class="block text-sm font-medium text-gray-700 mb-1">{{ __('inspiration.form.company_name.label') }}</label>
                                <input
                                    type="text"
                                    id="companyName"
                                    wire:model="companyName"
                                    placeholder="{{ __('inspiration.form.company_name.placeholder') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                >
                            </div>
                            <div>
                                <label for="projectType" class="block text-sm font-medium text-gray-700 mb-1">{{ __('inspiration.form.project_type.label') }}</label>
                                <input
                                    type="text"
                                    id="projectType"
                                    wire:model="projectType"
                                    placeholder="{{ __('inspiration.form.project_type.placeholder') }}"
                                    class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                >
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Review Text -->
                <div class="mb-6">
                    <label for="text" class="block text-sm font-medium text-gray-700 mb-1">{{ __('inspiration.form.text.label') }}</label>
                    <textarea
                        id="text"
                        wire:model="text"
                        rows="4"
                        placeholder="{{ __('inspiration.form.text.placeholder') }}"
                        class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                    ></textarea>
                    @error('text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Photo Upload -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('inspiration.form.photos.label') }}</label>
                    <p class="text-sm text-gray-500 mb-3">{{ __('inspiration.form.photos.helper') }}</p>

                    <!-- Dropzone -->
                    <div
                        x-data="{ isDragging: false }"
                        x-on:dragover.prevent="isDragging = true"
                        x-on:dragleave.prevent="isDragging = false"
                        x-on:drop.prevent="isDragging = false"
                        class="relative border-2 border-dashed rounded-lg p-8 text-center transition"
                        :class="isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 hover:border-gray-400'"
                    >
                        <input
                            type="file"
                            wire:model="photos"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        >
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">{{ __('inspiration.form.photos.dropzone') }}</p>
                        <p class="mt-1 text-xs text-gray-500">JPEG, PNG, WebP - max 10MB per foto</p>
                    </div>

                    <!-- Photo Previews -->
                    @if(count($photos) > 0)
                        <div class="mt-4 grid grid-cols-4 gap-4">
                            @foreach($photos as $index => $photo)
                                <div class="relative group">
                                    <img
                                        src="{{ $photo->temporaryUrl() }}"
                                        alt="Preview"
                                        class="w-full aspect-square object-cover rounded-lg"
                                    >
                                    <button
                                        type="button"
                                        wire:click="removePhoto({{ $index }})"
                                        class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('photos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('photos.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                    <!-- Loading indicator -->
                    <div wire:loading wire:target="photos" class="mt-2 text-sm text-gray-500">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-primary-600 inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Foto's uploaden...
                    </div>
                </div>

                <!-- Permission -->
                <div class="mb-6">
                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            wire:model="permissionGranted"
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <span class="text-sm text-gray-700">{{ __('inspiration.form.permission.label') }}</span>
                    </label>
                    @error('permissionGranted') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75 cursor-not-allowed"
                    class="w-full py-3 px-4 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="submit">{{ __('inspiration.form.submit') }}</span>
                    <span wire:loading wire:target="submit" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('inspiration.form.submitting') }}
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>
