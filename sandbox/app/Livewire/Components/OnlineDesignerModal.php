<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Models\CartLine;

class OnlineDesignerModal extends Component
{
    public bool $isOpen = false;

    public ?int $cartLineId = null;

    public ?string $productName = null;

    public ?array $uploadSpec = null;

    public array $designStatus = [];

    #[On('open-designer-modal')]
    public function open(int $cartLineId): void
    {
        $this->cartLineId = $cartLineId;
        $this->isOpen = true;

        $this->loadCartLineData();
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->cartLineId = null;
        $this->uploadSpec = null;
        $this->productName = null;
        $this->designStatus = [];
    }

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

        $this->productName = $cartLine->purchasable?->product?->translateAttribute('name')
            ?? $cartLine->purchasable?->getDescription()
            ?? __('Product');

        $spec = $cartLine->resolveUploadSpec();

        if ($spec instanceof UploadSpec) {
            $this->uploadSpec = $spec->toArray();
        } else {
            $this->uploadSpec = null;
        }

        $this->updateDesignStatus();
    }

    protected function updateDesignStatus(): void
    {
        if (! $this->cartLineId || ! $this->uploadSpec) {
            return;
        }

        $cartLine = CartLine::find($this->cartLineId);
        if (! $cartLine) {
            return;
        }

        $meta = $cartLine->meta ?? [];
        $uploaders = $this->uploadSpec['uploaders'] ?? [];

        $this->designStatus = [];
        foreach ($uploaders as $index => $uploader) {
            $this->designStatus[$index] = [
                'complete' => ($meta["design_{$index}_complete"] ?? false) === true,
                'has_front' => ! empty($meta["design_{$index}_front"]),
                'has_back' => ! empty($meta["design_{$index}_back"]),
                'type' => $uploader['type'] ?? 'single',
            ];
        }
    }

    #[On('design-confirmed')]
    public function handleDesignConfirmed(int $uploaderIndex): void
    {
        $this->updateDesignStatus();
    }

    public function confirmAllDesigns(): void
    {
        if (! $this->allDesignsComplete) {
            return;
        }

        if (! $this->cartLineId) {
            return;
        }

        $cartLine = CartLine::find($this->cartLineId);
        if ($cartLine) {
            $meta = $cartLine->meta ?? [];
            $meta['upload_method'] = 'online_designer';
            $cartLine->update(['meta' => $meta]);
        }

        $this->dispatch('uploads-confirmed', [
            'cartLineId' => $this->cartLineId,
            'method' => 'online_designer',
        ]);

        $this->close();
    }

    #[Computed]
    public function uploaders(): array
    {
        return $this->uploadSpec['uploaders'] ?? [];
    }

    #[Computed]
    public function allDesignsComplete(): bool
    {
        if (empty($this->designStatus)) {
            return false;
        }

        foreach ($this->designStatus as $status) {
            if (! $status['complete']) {
                return false;
            }
        }

        return true;
    }

    #[Computed]
    public function completedCount(): int
    {
        return collect($this->designStatus)->filter(fn ($s) => $s['complete'])->count();
    }

    #[Computed]
    public function totalCount(): int
    {
        return count($this->designStatus);
    }

    public function render()
    {
        return view('livewire.components.online-designer-modal');
    }
}
