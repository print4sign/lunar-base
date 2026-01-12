<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Models\CartLine;
use Lunar\Services\DeliverLaterService;

class CheckoutUploadModal extends Component
{
    public bool $isOpen = false;

    public ?int $cartLineId = null;

    /**
     * Active tab: 'custom', 'probo', or 'designer'.
     */
    public string $activeTab = 'custom';

    /**
     * Whether the user wants to deliver files later.
     */
    public bool $deliverLater = false;

    /**
     * Upload spec for the current cart line.
     */
    public ?array $uploadSpec = null;

    /**
     * Product name for display.
     */
    public ?string $productName = null;

    /**
     * Current upload status.
     */
    public array $uploadStatus = [
        'custom_complete' => false,
        'probo_complete' => false,
        'designer_complete' => false,
    ];

    /**
     * Open the modal for a specific cart line.
     */
    #[On('open-upload-modal')]
    public function open(int $cartLineId): void
    {
        $this->cartLineId = $cartLineId;
        $this->isOpen = true;
        $this->activeTab = 'custom';
        $this->deliverLater = false;

        $this->loadCartLineData();

        Log::info('CheckoutUploadModal: Opened', [
            'cart_line_id' => $cartLineId,
        ]);
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
        $this->uploadStatus = [
            'custom_complete' => false,
            'probo_complete' => false,
            'designer_complete' => false,
        ];
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

        // Check if already marked for deliver later
        $this->deliverLater = ($cartLine->meta['upload_deliver_later'] ?? false) === true;

        // Update upload status
        $this->updateUploadStatus();
    }

    /**
     * Update the upload status.
     */
    protected function updateUploadStatus(): void
    {
        if (! $this->cartLineId) {
            return;
        }

        $cartLine = CartLine::find($this->cartLineId);

        if (! $cartLine) {
            return;
        }

        // Check custom upload completeness
        $this->uploadStatus['custom_complete'] = $cartLine->hasAllRequiredPrintAssets();

        // Check Probo upload completeness
        $proboStatus = $cartLine->meta['probo_uploader_status'] ?? null;
        $this->uploadStatus['probo_complete'] = in_array($proboStatus, ['confirmed', 'processed']);

        // Check Online Designer completeness
        $this->uploadStatus['designer_complete'] = $this->checkDesignerComplete($cartLine);
    }

    /**
     * Check if online designer has complete designs.
     */
    protected function checkDesignerComplete(CartLine $cartLine): bool
    {
        if (! $this->uploadSpec) {
            return false;
        }

        $uploaders = $this->uploadSpec['uploaders'] ?? [];
        $meta = $cartLine->meta ?? [];

        foreach ($uploaders as $index => $uploader) {
            $isComplete = $meta["design_{$index}_complete"] ?? false;

            if (! $isComplete) {
                return false;
            }
        }

        return count($uploaders) > 0;
    }

    /**
     * Set the active tab.
     */
    public function setTab(string $tab): void
    {
        if (in_array($tab, ['custom', 'probo', 'designer'])) {
            $this->activeTab = $tab;
        }
    }

    /**
     * Toggle deliver later option.
     */
    public function toggleDeliverLater(): void
    {
        $this->deliverLater = ! $this->deliverLater;

        if (! $this->cartLineId) {
            return;
        }

        $cartLine = CartLine::find($this->cartLineId);

        if (! $cartLine) {
            return;
        }

        $deliverLaterService = app(DeliverLaterService::class);

        if ($this->deliverLater) {
            $deliverLaterService->markForDeliverLater($cartLine);
            $deliverLaterService->addSurchargeToCart($cartLine->cart, $cartLine);
        } else {
            $deliverLaterService->unmarkDeliverLater($cartLine);
            $deliverLaterService->removeSurchargeFromCart($cartLine->cart, $cartLine);
        }

        // Dispatch event to update cart totals
        $this->dispatch('cart-updated');

        Log::info('CheckoutUploadModal: Deliver later toggled', [
            'cart_line_id' => $this->cartLineId,
            'deliver_later' => $this->deliverLater,
        ]);
    }

    /**
     * Confirm uploads and close modal.
     */
    public function confirmUploads(): void
    {
        $this->updateUploadStatus();

        $isComplete = match ($this->activeTab) {
            'custom' => $this->uploadStatus['custom_complete'],
            'probo' => $this->uploadStatus['probo_complete'],
            'designer' => $this->uploadStatus['designer_complete'],
            default => false,
        };

        if (! $isComplete && ! $this->deliverLater) {
            // Cannot confirm without complete uploads or deliver-later
            return;
        }

        if (! $this->cartLineId) {
            return;
        }

        $cartLine = CartLine::find($this->cartLineId);

        if ($cartLine) {
            // Store the upload method in meta
            $meta = $cartLine->meta ?? [];
            $meta['upload_method'] = $this->deliverLater ? 'deliver_later' : $this->activeTab;
            $cartLine->update(['meta' => $meta]);
        }

        // Dispatch event
        $this->dispatch('uploads-confirmed', [
            'cartLineId' => $this->cartLineId,
            'method' => $this->deliverLater ? 'deliver_later' : $this->activeTab,
        ]);

        Log::info('CheckoutUploadModal: Uploads confirmed', [
            'cart_line_id' => $this->cartLineId,
            'method' => $this->deliverLater ? 'deliver_later' : $this->activeTab,
        ]);

        $this->close();
    }

    /**
     * Handle file uploaded event from child component.
     */
    #[On('file-uploaded')]
    public function handleFileUploaded(array $data): void
    {
        if (($data['cartLineId'] ?? null) === $this->cartLineId) {
            $this->updateUploadStatus();
        }
    }

    /**
     * Handle file removed event from child component.
     */
    #[On('file-removed')]
    public function handleFileRemoved(array $data): void
    {
        if (($data['cartLineId'] ?? null) === $this->cartLineId) {
            $this->updateUploadStatus();
        }
    }

    /**
     * Handle Probo upload complete event.
     */
    #[On('probo-upload-complete')]
    public function handleProboUploadComplete(array $data): void
    {
        if (($data['cartLineId'] ?? null) === $this->cartLineId) {
            $this->uploadStatus['probo_complete'] = true;
        }
    }

    /**
     * Get the surcharge amount formatted.
     */
    #[Computed]
    public function surchargeAmount(): string
    {
        $service = app(DeliverLaterService::class);
        $cents = $service->getSurchargeAmount();

        return number_format($cents / 100, 2, ',', '.');
    }

    /**
     * Check if Probo uploader is enabled.
     */
    #[Computed]
    public function proboUploaderEnabled(): bool
    {
        return config('lunar.cart.print_assets.probo_uploader_enabled', true);
    }

    /**
     * Check if deliver later is enabled.
     */
    #[Computed]
    public function deliverLaterEnabled(): bool
    {
        $service = app(DeliverLaterService::class);

        return $service->isEnabled();
    }

    /**
     * Check if the current selection is complete (can confirm).
     */
    #[Computed]
    public function canConfirm(): bool
    {
        if ($this->deliverLater) {
            return true;
        }

        return match ($this->activeTab) {
            'custom' => $this->uploadStatus['custom_complete'],
            'probo' => $this->uploadStatus['probo_complete'],
            'designer' => $this->uploadStatus['designer_complete'],
            default => false,
        };
    }

    /**
     * Handle design confirmed event from online designer.
     */
    #[On('design-confirmed')]
    public function handleDesignConfirmed(int $uploaderIndex): void
    {
        $this->updateUploadStatus();
    }

    /**
     * Get uploaders from the spec.
     */
    #[Computed]
    public function uploaders(): array
    {
        return $this->uploadSpec['uploaders'] ?? [];
    }

    public function render()
    {
        return view('livewire.components.checkout-upload-modal');
    }
}
