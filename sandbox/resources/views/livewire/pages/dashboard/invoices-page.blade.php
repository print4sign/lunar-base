<div>
    <x-dashboard.layout>
        {{-- Page Header --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('dashboard.invoices.title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('dashboard.invoices.subtitle') }}</p>
        </div>

        {{-- Coming Soon Placeholder --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center dark:bg-gray-800 dark:border-gray-700">
            <div class="max-w-md mx-auto">
                <svg class="w-16 h-16 mx-auto mb-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    {{ __('dashboard.invoices.coming_soon') }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('dashboard.invoices.coming_soon_description') }}
                </p>
            </div>
        </div>
    </x-dashboard.layout>
</div>
