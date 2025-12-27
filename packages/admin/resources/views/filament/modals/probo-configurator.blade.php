<div
    x-data="{
        supplierProductId: null,
        productId: {{ $productId ?? 'null' }},
        init() {
            // Watch for changes to the supplier_product_id field
            this.supplierProductId = this.$wire.get('data.supplier_product_id');
            this.$watch('$wire.data.supplier_product_id', (value) => {
                this.supplierProductId = value;
            });
        }
    }"
    class="py-4"
>
    <template x-if="supplierProductId">
        <div>
            <livewire:lunar-admin::components.probo-configurator
                :supplier-product-id="null"
                :product-id="null"
                x-bind:key="'probo-configurator-' + supplierProductId"
            />
        </div>
    </template>
    <template x-if="!supplierProductId">
        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
            {{ __('lunarpanel::product.configurator.modal.select_product') }}
        </div>
    </template>
</div>
