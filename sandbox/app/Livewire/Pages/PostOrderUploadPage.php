<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Models\OrderLine;
use Lunar\Services\DeliverLaterService;

#[Layout('layouts.storefront')]
class PostOrderUploadPage extends Component
{
    use HasLocale;

    public string $token;

    public ?OrderLine $orderLine = null;

    public bool $isComplete = false;

    public bool $isExpired = false;

    public ?array $uploadSpec = null;

    public ?string $productName = null;

    public array $uploadStatus = [];

    public function mount(string $locale, string $token): void
    {
        $this->initializeLocale($locale);
        $this->token = $token;

        $this->loadOrderLine();
    }

    /**
     * Load and validate the order line from the token.
     */
    protected function loadOrderLine(): void
    {
        $deliverLaterService = app(DeliverLaterService::class);

        $this->orderLine = $deliverLaterService->validateToken($this->token);

        if (! $this->orderLine) {
            // Check if it's expired vs invalid
            $expiredOrderLine = OrderLine::where('upload_token', $this->token)->first();

            if ($expiredOrderLine) {
                $this->isExpired = $deliverLaterService->isTokenExpired($expiredOrderLine);
                $this->isComplete = $deliverLaterService->isUploadComplete($expiredOrderLine);
            }

            return;
        }

        // Load product info
        $this->productName = $this->orderLine->purchasable?->getDescription()
            ?? $this->orderLine->description
            ?? __('Product');

        // Get upload spec from order line meta
        $specData = $this->orderLine->meta['upload_spec'] ?? null;

        if ($specData) {
            $this->uploadSpec = $specData;
        } else {
            // Try to resolve from purchasable
            $spec = $this->orderLine->purchasable?->uploadSpec();

            if ($spec instanceof UploadSpec) {
                $this->uploadSpec = $spec->toArray();
            }
        }

        // Check current upload status
        $this->updateUploadStatus();
    }

    /**
     * Update the upload status.
     */
    protected function updateUploadStatus(): void
    {
        if (! $this->orderLine) {
            return;
        }

        $assets = $this->orderLine->printAssets ?? collect();
        $spec = $this->uploadSpec ? UploadSpec::fromArray($this->uploadSpec) : null;

        if (! $spec || ! $spec->requiresUpload()) {
            $this->uploadStatus = [
                'complete' => true,
                'uploaded' => 0,
                'required' => 0,
            ];

            return;
        }

        $requiredCount = 0;

        foreach ($spec->uploaders as $uploader) {
            $requiredCount += $uploader->getRequiredFileCount();
        }

        $this->uploadStatus = [
            'complete' => $assets->count() >= $requiredCount,
            'uploaded' => $assets->count(),
            'required' => $requiredCount,
        ];
    }

    /**
     * Handle file uploaded event.
     */
    #[On('file-uploaded')]
    public function handleFileUploaded(array $data): void
    {
        $this->updateUploadStatus();
    }

    /**
     * Handle file removed event.
     */
    #[On('file-removed')]
    public function handleFileRemoved(array $data): void
    {
        $this->updateUploadStatus();
    }

    /**
     * Confirm uploads and complete the process.
     */
    public function confirmUploads(): void
    {
        if (! $this->orderLine) {
            return;
        }

        $this->updateUploadStatus();

        if (! ($this->uploadStatus['complete'] ?? false)) {
            Notification::make()
                ->title(__('upload.post_order.incomplete'))
                ->body(__('upload.post_order.upload_all_files'))
                ->danger()
                ->send();

            return;
        }

        $deliverLaterService = app(DeliverLaterService::class);
        $deliverLaterService->markUploadsComplete($this->orderLine);

        $this->isComplete = true;

        Notification::make()
            ->title(__('upload.post_order.success'))
            ->body(__('upload.post_order.files_received'))
            ->success()
            ->send();
    }

    /**
     * Get uploaders from the spec.
     */
    #[Computed]
    public function uploaders(): array
    {
        return $this->uploadSpec['uploaders'] ?? [];
    }

    /**
     * Check if uploads can be confirmed.
     */
    #[Computed]
    public function canConfirm(): bool
    {
        return $this->uploadStatus['complete'] ?? false;
    }

    /**
     * Get the order reference.
     */
    #[Computed]
    public function orderReference(): ?string
    {
        return $this->orderLine?->order?->reference;
    }

    public function render()
    {
        return view('livewire.pages.post-order-upload-page', [
            'title' => __('upload.post_order.title'),
        ]);
    }
}
