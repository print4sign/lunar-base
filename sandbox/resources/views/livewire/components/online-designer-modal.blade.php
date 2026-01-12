<div
    x-data="{ open: @entangle('isOpen') }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-hidden"
    aria-labelledby="designer-modal-title"
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
        class="absolute inset-0 bg-black/70"
    ></div>

    {{-- Fullscreen Panel --}}
    <div
        x-show="open"
        x-transition:enter="transform transition ease-in-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transform transition ease-in-out duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-4 md:inset-8 bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden"
    >
        {{-- Header --}}
        <div class="bg-primary-600 px-6 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-4">
                <div>
                    <h2 id="designer-modal-title" class="text-lg font-medium text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ __('Online Designer') }}
                    </h2>
                    @if($productName)
                        <p class="text-sm text-primary-200">{{ $productName }}</p>
                    @endif
                </div>

                {{-- Progress indicator --}}
                @if($this->totalCount > 0)
                    <div class="hidden sm:flex items-center gap-2 ml-6 px-3 py-1 bg-primary-700 rounded-full">
                        <span class="text-sm text-primary-200">
                            {{ $this->completedCount }} / {{ $this->totalCount }} {{ __('ontwerpen voltooid') }}
                        </span>
                        @if($this->allDesignsComplete)
                            <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-3">
                {{-- Confirm button in header --}}
                <button
                    type="button"
                    wire:click="confirmAllDesigns"
                    @disabled(!$this->allDesignsComplete)
                    @class([
                        'px-4 py-2 text-sm font-medium rounded-lg transition-colors',
                        'bg-white text-primary-600 hover:bg-primary-50' => $this->allDesignsComplete,
                        'bg-primary-500 text-primary-300 cursor-not-allowed' => !$this->allDesignsComplete,
                    ])
                >
                    @if($this->allDesignsComplete)
                        <svg class="w-4 h-4 mr-1.5 inline" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @endif
                    {{ __('Bevestigen & Sluiten') }}
                </button>

                {{-- Close button --}}
                <button
                    type="button"
                    wire:click="close"
                    class="p-2 rounded-lg text-primary-200 hover:text-white hover:bg-primary-700 transition-colors"
                >
                    <span class="sr-only">{{ __('Sluiten') }}</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Content Area --}}
        <div class="flex-1 overflow-y-auto bg-gray-100">
            @if($uploadSpec && $cartLineId)
                @vite(['resources/css/online-designer.css'])

                @if(count($this->uploaders) === 1)
                    {{-- Single uploader - full width --}}
                    <div class="h-full">
                        <livewire:components.online-designer
                            :cart-line-id="$cartLineId"
                            :uploader-index="0"
                            :uploader-requirement="$this->uploaders[0]"
                            :key="'designer-modal-' . $cartLineId . '-0'"
                        />
                    </div>
                @else
                    {{-- Multiple uploaders - stacked --}}
                    <div class="p-6 space-y-8">
                        @foreach($this->uploaders as $index => $uploader)
                            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                        {{ __('Ontwerp :number', ['number' => $index + 1]) }}
                                        @if($uploader['type'] === 'frontback')
                                            <span class="text-xs text-gray-500">({{ __('voor- en achterkant') }})</span>
                                        @endif
                                    </h3>
                                    @if($designStatus[$index]['complete'] ?? false)
                                        <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-full">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            {{ __('Voltooid') }}
                                        </span>
                                    @endif
                                </div>
                                <livewire:components.online-designer
                                    :cart-line-id="$cartLineId"
                                    :uploader-index="$index"
                                    :uploader-requirement="$uploader"
                                    :key="'designer-modal-' . $cartLineId . '-' . $index"
                                />
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                {{-- No upload spec --}}
                <div class="flex items-center justify-center h-full">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">
                            {{ __('Geen ontwerpspecificaties beschikbaar') }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            {{ __('Dit product heeft geen online designer configuratie.') }}
                        </p>
                        <button
                            type="button"
                            wire:click="close"
                            class="mt-6 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            {{ __('Sluiten') }}
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer with mobile-friendly confirm button --}}
        @if($uploadSpec && $this->totalCount > 0)
            <div class="sm:hidden border-t border-gray-200 bg-white px-4 py-3 shrink-0">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-gray-600">
                        {{ $this->completedCount }} / {{ $this->totalCount }} {{ __('ontwerpen voltooid') }}
                    </span>
                    @if($this->allDesignsComplete)
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
                <button
                    type="button"
                    wire:click="confirmAllDesigns"
                    @disabled(!$this->allDesignsComplete)
                    @class([
                        'w-full py-3 px-4 text-sm font-medium rounded-lg transition-colors',
                        'bg-primary-600 text-white hover:bg-primary-700' => $this->allDesignsComplete,
                        'bg-gray-200 text-gray-400 cursor-not-allowed' => !$this->allDesignsComplete,
                    ])
                >
                    {{ __('Bevestigen & Sluiten') }}
                </button>
            </div>
        @endif
    </div>
</div>
