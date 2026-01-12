<?php

namespace App\Livewire\Components;

use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Models\CartLine;

/**
 * Modal wrapper for DeliverySpecs component.
 *
 * Opens as a slide-over modal from the right side.
 * For standalone/inline usage, use DeliverySpecs component directly.
 */
class DeliverySpecsModal extends Component
{
    public bool $isOpen = false;

    public ?int $cartLineId = null;

    /**
     * Upload spec for the current cart line.
     */
    public ?array $uploadSpec = null;

    /**
     * Product name for display in header.
     */
    public ?string $productName = null;

    /**
     * Template download URL if available.
     */
    public ?string $templateUrl = null;

    /**
     * Open the modal for a specific cart line.
     */
    #[On('open-delivery-specs-modal')]
    public function open(int $cartLineId): void
    {
        $this->cartLineId = $cartLineId;
        $this->isOpen = true;

        $this->loadCartLineData();
    }

    /**
     * Close the modal.
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->cartLineId = null;
        $this->uploadSpec = null;
        $this->productName = null;
        $this->templateUrl = null;
    }

    /**
     * Load cart line data.
     */
    protected function loadCartLineData(): void
    {
        if (! $this->cartLineId) {
            return;
        }

        $cartLine = CartLine::with('purchasable.product')->find($this->cartLineId);

        if (! $cartLine) {
            $this->close();

            return;
        }

        // Get product name
        $this->productName = $cartLine->purchasable?->product?->translateAttribute('name')
            ?? $cartLine->purchasable?->getDescription()
            ?? __('Product');

        // Get upload spec
        $spec = $cartLine->resolveUploadSpec();

        if ($spec instanceof UploadSpec) {
            $this->uploadSpec = $spec->toArray();
        } else {
            $this->uploadSpec = null;
        }

        // Get template URL from cart line meta if available
        $this->templateUrl = $cartLine->meta['template_url'] ?? null;
    }

    public function render()
    {
        return view('livewire.components.delivery-specs-modal');
    }
}
