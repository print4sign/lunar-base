<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 py-4">
    {{-- Option 1: Clear cart --}}
    <button
        type="button"
        wire:click="clearAndRestore"
        class="group flex flex-col items-center p-6 border-2 border-gray-200 rounded-xl hover:border-primary-500 hover:bg-primary-50 transition-all duration-200 text-center"
    >
        <div class="w-16 h-16 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center mb-4 group-hover:bg-primary-200 transition-colors">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>
        <h3 class="font-semibold text-gray-900 mb-1">{{ __('dashboard.saved_carts.conflict.clear') }}</h3>
        <p class="text-sm text-gray-500">{{ __('dashboard.saved_carts.conflict.clear_description') }}</p>
    </button>

    {{-- Option 2: Save current cart --}}
    <button
        type="button"
        wire:click="saveAndRestore"
        class="group flex flex-col items-center p-6 border-2 border-gray-200 rounded-xl hover:border-primary-500 hover:bg-primary-50 transition-all duration-200 text-center"
    >
        <div class="w-16 h-16 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center mb-4 group-hover:bg-primary-200 transition-colors">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
            </svg>
        </div>
        <h3 class="font-semibold text-gray-900 mb-1">{{ __('dashboard.saved_carts.conflict.save') }}</h3>
        <p class="text-sm text-gray-500">{{ __('dashboard.saved_carts.conflict.save_description') }}</p>
    </button>

    {{-- Option 3: Merge carts --}}
    <button
        type="button"
        wire:click="mergeAndRestore"
        class="group flex flex-col items-center p-6 border-2 border-gray-200 rounded-xl hover:border-primary-500 hover:bg-primary-50 transition-all duration-200 text-center"
    >
        <div class="w-16 h-16 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center mb-4 group-hover:bg-primary-200 transition-colors">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
            </svg>
        </div>
        <h3 class="font-semibold text-gray-900 mb-1">{{ __('dashboard.saved_carts.conflict.merge') }}</h3>
        <p class="text-sm text-gray-500">{{ __('dashboard.saved_carts.conflict.merge_description') }}</p>
    </button>
</div>
