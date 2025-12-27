<div class="py-4">
    @if ($supplierProductId)
        @switch($driver)
            @case('probo')
                @livewire('lunar.admin.livewire.components.probo-configurator', [
                    'supplierProductId' => $supplierProductId,
                    'productId' => $productId,
                    'targetVariantId' => $variantId,
                ], key('probo-configurator-' . $supplierProductId))
                @break

            @case('helloprint')
                @livewire('lunar.admin.livewire.components.helloprint-configurator', [
                    'supplierProductId' => $supplierProductId,
                    'productId' => $productId,
                    'targetVariantId' => $variantId,
                ], key('helloprint-configurator-' . $supplierProductId))
                @break

            @default
                <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                    <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-4 text-yellow-500" />
                    <p class="text-sm">
                        {{ __('lunarpanel::product.configurator.modal.unsupported_driver', ['driver' => $driver ?? 'unknown']) }}
                    </p>
                </div>
        @endswitch
    @else
        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
            {{ __('lunarpanel::product.configurator.modal.select_product') }}
        </div>
    @endif
</div>
