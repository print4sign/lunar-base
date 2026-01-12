<x-filament-panels::page>
    {{ $this->infolist }}

    {{-- Probo Configurator Modal - shown for both existing supplier products and new linking --}}
    <x-filament::modal
        id="probo-configurator-modal"
        width="5xl"
        slide-over
    >
        <x-slot name="heading">
            {{ __('lunarpanel::product.configurator.title') }}
        </x-slot>

        @livewire('lunar.admin.livewire.components.probo-configurator', [
            'supplierProductId' => $this->getRecord()->supplier_product_id,
            'productId' => $this->getRecord()->product_id,
            'targetVariantId' => $this->getRecord()->id,
        ], key('fulfillment-probo-configurator-' . $this->getRecord()->id))

        <x-slot name="footerActions">
            <x-filament::button
                color="primary"
                x-on:click="$dispatch('configurator-save')"
            >
                {{ __('lunarpanel::product.configurator.actions.save') }}
            </x-filament::button>
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('configurator-reset')"
            >
                {{ __('lunarpanel::product.configurator.actions.reset') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle configure-variant-with-probo event (for existing supplier products)
            Livewire.on('configure-variant-with-probo', (data) => {
                window.dispatchEvent(new CustomEvent('open-modal', {
                    detail: { id: 'probo-configurator-modal' }
                }));
            });

            // Handle open-supplier-configurator event (for new supplier linking)
            Livewire.on('open-supplier-configurator', (data) => {
                // Dispatch event to the ProboConfigurator to load the selected supplier product
                Livewire.dispatch('supplier-product-selected', {
                    supplierProductId: data.supplierProductId,
                    productId: data.productId
                });

                // Also set the target variant ID
                Livewire.dispatch('configure-variant-with-probo', {
                    variantId: data.variantId,
                    supplierProductId: data.supplierProductId,
                    productId: data.productId
                });

                // Open the modal
                window.dispatchEvent(new CustomEvent('open-modal', {
                    detail: { id: 'probo-configurator-modal' }
                }));
            });

            // Handle configurator-saved to refresh the page
            window.addEventListener('configurator-saved', function() {
                location.reload();
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
