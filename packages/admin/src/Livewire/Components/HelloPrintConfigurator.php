<?php

namespace Lunar\Admin\Livewire\Components;

use Livewire\Attributes\Locked;
use Livewire\Component;

class HelloPrintConfigurator extends Component
{
    /**
     * The supplier product ID being configured.
     */
    #[Locked]
    public ?int $supplierProductId = null;

    /**
     * The product ID to create variants for.
     */
    #[Locked]
    public ?int $productId = null;

    /**
     * The target variant ID to update configuration for.
     */
    #[Locked]
    public ?int $targetVariantId = null;

    /**
     * Mount the component.
     */
    public function mount(?int $supplierProductId = null, ?int $productId = null, ?int $targetVariantId = null): void
    {
        $this->supplierProductId = $supplierProductId;
        $this->productId = $productId;
        $this->targetVariantId = $targetVariantId;
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('lunarpanel::livewire.components.helloprint-configurator');
    }
}
