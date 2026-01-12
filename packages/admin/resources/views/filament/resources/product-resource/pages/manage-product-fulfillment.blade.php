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
            'supplierProductId' => $this->getVariant()->supplier_product_id,
            'productId' => $this->getRecord()->id,
            'targetVariantId' => $this->getVariant()->id,
        ], key('fulfillment-probo-configurator-' . $this->getVariant()->id))

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
            // Handle configurator-saved to refresh the page
            window.addEventListener('configurator-saved', function() {
                location.reload();
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
