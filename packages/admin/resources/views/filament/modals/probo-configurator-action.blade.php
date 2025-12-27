<div class="py-4">
    @if ($supplierProductId)
        @livewire('lunar.admin.livewire.components.probo-configurator', [
            'supplierProductId' => $supplierProductId,
            'productId' => $productId,
            'targetVariantId' => $variantId,
        ])
    @else
        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
            {{ __('lunarpanel::product.configurator.modal.select_product') }}
        </div>
    @endif
</div>
