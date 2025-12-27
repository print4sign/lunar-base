<div class="space-y-6 p-4">
    {{-- Placeholder Header --}}
    <div class="text-center py-8">
        <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
            <x-heroicon-o-cog-6-tooth class="w-8 h-8 text-gray-400 dark:text-gray-500" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            {{ __('lunarpanel::supplier.configurator.helloprint.title') }}
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
            {{ __('lunarpanel::supplier.configurator.helloprint.description') }}
        </p>
    </div>

    {{-- Placeholder Configuration Area --}}
    <div class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-8">
        <div class="text-center text-gray-500 dark:text-gray-400">
            <x-heroicon-o-wrench-screwdriver class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
            <p class="text-sm">
                {{ __('lunarpanel::supplier.configurator.helloprint.coming_soon') }}
            </p>
        </div>
    </div>

    {{-- Debug Info --}}
    @if (config('app.debug'))
        <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs font-mono text-gray-600 dark:text-gray-400">
            <span class="font-semibold">Supplier Product ID:</span> {{ $supplierProductId ?? 'null' }} |
            <span class="font-semibold">Product ID:</span> {{ $productId ?? 'null' }} |
            <span class="font-semibold">Target Variant ID:</span> {{ $targetVariantId ?? 'null' }}
        </div>
    @endif
</div>
