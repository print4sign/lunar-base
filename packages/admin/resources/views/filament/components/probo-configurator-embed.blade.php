<div
    x-data="{
        supplierProductId: null,
        productId: {{ $productId ?? 'null' }},
        loadProduct(id) {
            if (!id || id === this.supplierProductId) return;
            this.supplierProductId = id;

            // Use Livewire's global dispatch to send event to nested component
            // The nested component listens for 'supplier-product-selected' event
            Livewire.dispatch('supplier-product-selected', {
                supplierProductId: parseInt(id),
                productId: this.productId
            });
        },
        init() {
            // Get data from Filament action form (stored in mountedActionsData)
            const getData = () => {
                if (this.$wire.mountedActionsData && this.$wire.mountedActionsData[0]) {
                    return this.$wire.mountedActionsData[0];
                }
                return this.$wire.data || {};
            };

            // Watch for changes in mountedActionsData (Filament actions)
            this.$watch('$wire.mountedActionsData', (value) => {
                if (value && value[0] && value[0].supplier_product_id) {
                    this.loadProduct(value[0].supplier_product_id);
                }
            }, { deep: true });

            // Get initial value
            const formData = getData();
            if (formData.supplier_product_id) {
                this.loadProduct(formData.supplier_product_id);
            }
        }
    }"
    class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4"
>
    @livewire('lunar.admin.livewire.components.probo-configurator', [
        'supplierProductId' => null,
        'productId' => $productId ?? null,
    ], key('probo-configurator-embed'))
</div>
