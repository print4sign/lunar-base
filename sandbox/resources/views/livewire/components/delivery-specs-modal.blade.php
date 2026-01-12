<div
    x-data="{ open: @entangle('isOpen') }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="delivery-specs-modal-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-black/50"
        @click="$wire.close()"
    ></div>

    {{-- Slide-over panel --}}
    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 sm:pl-16">
        <div
            x-show="open"
            x-transition:enter="transform transition ease-in-out duration-500"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-500"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="pointer-events-auto w-screen max-w-lg"
        >
            <div class="flex h-full flex-col bg-white shadow-xl">
                {{-- Header --}}
                <div class="bg-white px-6 py-5 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 id="delivery-specs-modal-title" class="text-xl font-semibold text-gray-900">
                                {{ __('delivery_specs.title') }}
                            </h2>
                        </div>
                        <button
                            type="button"
                            wire:click="close"
                            class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Content - Use the reusable DeliverySpecs component --}}
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    @if($uploadSpec)
                        <livewire:components.delivery-specs
                            :upload-spec="$uploadSpec"
                            :template-url="$templateUrl"
                            :show-template="true"
                            :show-more-info-link="true"
                            :key="'delivery-specs-' . ($cartLineId ?? 'none')"
                        />
                    @else
                        {{-- No upload spec --}}
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                {{ __('delivery_specs.no_specs') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('delivery_specs.no_specs_description') }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-200 px-6 py-4">
                    <button
                        type="button"
                        wire:click="close"
                        class="w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    >
                        {{ __('delivery_specs.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
