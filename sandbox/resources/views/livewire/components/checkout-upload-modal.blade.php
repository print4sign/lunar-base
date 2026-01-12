<div
    x-data="{ open: @entangle('isOpen') }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="upload-modal-title"
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
            class="pointer-events-auto w-screen max-w-2xl"
        >
            <div class="flex h-full flex-col bg-white shadow-xl">
                {{-- Header --}}
                <div class="bg-primary-600 px-4 py-6 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 id="upload-modal-title" class="text-lg font-medium text-white">
                                {{ __('Upload Print Files') }}
                            </h2>
                            @if($productName)
                                <p class="mt-1 text-sm text-primary-200">
                                    {{ $productName }}
                                </p>
                            @endif
                        </div>
                        <button
                            type="button"
                            wire:click="close"
                            class="rounded-md text-primary-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-white"
                        >
                            <span class="sr-only">{{ __('Close') }}</span>
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Content --}}
                <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                    @if($uploadSpec)
                        {{-- Tabs --}}
                        @if($this->proboUploaderEnabled)
                            <div class="mb-6">
                                <div class="border-b border-gray-200">
                                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                        <button
                                            type="button"
                                            wire:click="setTab('custom')"
                                            @class([
                                                'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors',
                                                'border-primary-500 text-primary-600' => $activeTab === 'custom',
                                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $activeTab !== 'custom',
                                            ])
                                        >
                                            <span class="flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                                {{ __('Upload Here') }}
                                                @if($uploadStatus['custom_complete'])
                                                    <span class="inline-flex items-center justify-center w-5 h-5 bg-green-100 rounded-full">
                                                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                @endif
                                            </span>
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="setTab('probo')"
                                            @class([
                                                'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors',
                                                'border-primary-500 text-primary-600' => $activeTab === 'probo',
                                                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $activeTab !== 'probo',
                                            ])
                                        >
                                            <span class="flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                                {{ __('Probo Uploader') }}
                                                @if($uploadStatus['probo_complete'])
                                                    <span class="inline-flex items-center justify-center w-5 h-5 bg-green-100 rounded-full">
                                                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                @endif
                                            </span>
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        @endif

                        {{-- Tab content --}}
                        <div class="mb-6">
                            @if($activeTab === 'custom')
                                {{-- Custom file uploader for each uploader slot --}}
                                <div class="space-y-6">
                                    @foreach($this->uploaders as $index => $uploader)
                                        <div @class(['pt-6 border-t border-gray-200' => $index > 0])>
                                            @if(count($this->uploaders) > 1)
                                                <h4 class="text-sm font-medium text-gray-900 mb-4">
                                                    {{ __('Upload Area :number', ['number' => $index + 1]) }}
                                                </h4>
                                            @endif
                                            <livewire:components.custom-file-uploader
                                                :cart-line-id="$cartLineId"
                                                :uploader-index="$index"
                                                :uploader-requirement="$uploader"
                                                :key="'custom-uploader-' . $cartLineId . '-' . $index"
                                            />
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($activeTab === 'probo')
                                {{-- Probo uploader integration --}}
                                <livewire:components.probo-uploader-integration
                                    :cart-line-id="$cartLineId"
                                    :key="'probo-uploader-' . $cartLineId"
                                />
                            @endif
                        </div>

                        {{-- Deliver Later option --}}
                        @if($this->deliverLaterEnabled)
                            <div class="border-t border-gray-200 pt-6">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleDeliverLater"
                                        @checked($deliverLater)
                                        class="mt-1 h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">
                                            {{ __('Deliver files later') }}
                                        </span>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ __('Place your order now and upload files later via a secure link we\'ll email you.') }}
                                        </p>
                                        <p class="text-sm text-orange-600 font-medium mt-1">
                                            {{ __('+ € :amount surcharge', ['amount' => $this->surchargeAmount]) }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                        @endif
                    @else
                        {{-- No upload spec --}}
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                {{ __('No upload required') }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('This product does not require print file uploads.') }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            wire:click="close"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            {{ __('Cancel') }}
                        </button>
                        <button
                            type="button"
                            wire:click="confirmUploads"
                            @disabled(!$this->canConfirm)
                            @class([
                                'px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors',
                                'bg-primary-600 hover:bg-primary-700' => $this->canConfirm,
                                'bg-gray-300 cursor-not-allowed' => !$this->canConfirm,
                            ])
                        >
                            @if($deliverLater)
                                {{ __('Confirm & Deliver Later') }}
                            @else
                                {{ __('Confirm Uploads') }}
                            @endif
                        </button>
                    </div>

                    {{-- Status message --}}
                    @if(!$this->canConfirm && !$deliverLater)
                        <p class="mt-3 text-sm text-gray-500 text-center">
                            {{ __('Please upload all required files or select "Deliver files later" to continue.') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
