<?php

namespace App\Livewire\Components;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Models\CartLine;
use Lunar\Services\ProboUploaderService;

class ProboUploaderIntegration extends Component
{
    #[Locked]
    public int $cartLineId;

    public ?string $uploaderUrl = null;

    public ?string $uploaderId = null;

    /**
     * Status: pending, creating, created, confirmed, processed, error.
     */
    public string $status = 'pending';

    public ?string $errorMessage = null;

    /**
     * Poll interval in milliseconds (5 seconds).
     */
    public int $pollInterval = 5000;

    public bool $isPolling = false;

    public function mount(int $cartLineId): void
    {
        $this->cartLineId = $cartLineId;

        Log::debug('ProboUploaderIntegration: Mounting', [
            'cart_line_id' => $cartLineId,
        ]);

        // Check if there's an existing session
        $this->loadExistingSession();

        Log::debug('ProboUploaderIntegration: After mount', [
            'cart_line_id' => $cartLineId,
            'status' => $this->status,
            'uploader_id' => $this->uploaderId,
        ]);
    }

    /**
     * Load existing Probo uploader session from cart line meta.
     */
    protected function loadExistingSession(): void
    {
        $cartLine = CartLine::find($this->cartLineId);

        if (! $cartLine) {
            return;
        }

        $meta = $cartLine->meta ?? [];

        if (isset($meta['probo_uploader_id'])) {
            $this->uploaderId = $meta['probo_uploader_id'];
            $this->uploaderUrl = $meta['probo_uploader_url'] ?? null;
            $this->status = $meta['probo_uploader_status'] ?? 'created';

            // If status is not final, start polling
            if (! in_array($this->status, ['confirmed', 'processed', 'error'])) {
                $this->startPolling();
            }
        }
    }

    /**
     * Create a new Probo uploader session.
     */
    public function createSession(): void
    {
        $this->status = 'creating';
        $this->errorMessage = null;

        try {
            $service = app(ProboUploaderService::class);
            $cartLine = CartLine::findOrFail($this->cartLineId);

            // Generate callback URL for Probo to notify us when upload is complete
            $callbackUrl = route('webhooks.probo.uploader');

            $session = $service->createSessionForCartLine($cartLine, $callbackUrl);

            $this->uploaderId = $session['id'];
            $this->uploaderUrl = $session['url'];
            $this->status = 'created';

            // Store session in cart line meta
            $service->storeSessionInCartLine($cartLine, $this->uploaderId, $this->uploaderUrl);

            Log::info('ProboUploaderIntegration: Session created', [
                'cart_line_id' => $this->cartLineId,
                'uploader_id' => $this->uploaderId,
            ]);

            // Start polling for status updates
            $this->startPolling();

            // Dispatch event
            $this->dispatch('probo-session-created', [
                'cartLineId' => $this->cartLineId,
                'uploaderId' => $this->uploaderId,
            ]);
        } catch (\Exception $e) {
            $this->status = 'error';

            // Provide user-friendly error message
            if (str_contains($e->getMessage(), 'calculation_id')) {
                $this->errorMessage = __('This product needs to be re-added to cart to use the Probo uploader. Please remove it and add it again.');
            } else {
                $this->errorMessage = $e->getMessage();
            }

            Log::error('ProboUploaderIntegration: Session creation failed', [
                'cart_line_id' => $this->cartLineId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open the uploader in a new tab.
     */
    public function openUploader(): void
    {
        if ($this->uploaderUrl) {
            $this->js("window.open('{$this->uploaderUrl}', '_blank')");
        }
    }

    /**
     * Check the status of the uploader session.
     */
    public function checkStatus(): void
    {
        if (! $this->uploaderId) {
            return;
        }

        try {
            $service = app(ProboUploaderService::class);
            $cartLine = CartLine::find($this->cartLineId);

            if (! $cartLine) {
                $this->stopPolling();

                return;
            }

            $isComplete = $service->areAllSessionsComplete($cartLine);

            if ($isComplete) {
                $this->status = 'confirmed';
                $this->stopPolling();

                Log::info('ProboUploaderIntegration: Session confirmed', [
                    'cart_line_id' => $this->cartLineId,
                    'uploader_id' => $this->uploaderId,
                ]);

                // Dispatch event
                $this->dispatch('probo-upload-complete', [
                    'cartLineId' => $this->cartLineId,
                    'uploaderId' => $this->uploaderId,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ProboUploaderIntegration: Status check failed', [
                'cart_line_id' => $this->cartLineId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Start polling for status updates.
     */
    protected function startPolling(): void
    {
        $this->isPolling = true;
    }

    /**
     * Stop polling.
     */
    protected function stopPolling(): void
    {
        $this->isPolling = false;
    }

    /**
     * Handle callback from Probo webhook (via broadcast).
     */
    #[On('probo-uploader-callback.{cartLineId}')]
    public function handleCallback(array $data): void
    {
        $status = $data['status'] ?? null;

        if ($status === 'confirmed' || $status === 'processed') {
            $this->status = $status;
            $this->stopPolling();

            $this->dispatch('probo-upload-complete', [
                'cartLineId' => $this->cartLineId,
                'uploaderId' => $this->uploaderId,
            ]);
        }
    }

    /**
     * Manually mark the upload as complete (for testing/development).
     */
    public function markAsComplete(): void
    {
        $cartLine = CartLine::find($this->cartLineId);

        if (! $cartLine) {
            return;
        }

        $meta = $cartLine->meta ?? [];
        $meta['probo_uploader_status'] = 'confirmed';
        $meta['probo_uploader_confirmed_at'] = now()->toIso8601String();
        $cartLine->update(['meta' => $meta]);

        $this->status = 'confirmed';
        $this->stopPolling();

        Log::info('ProboUploaderIntegration: Manually marked as complete', [
            'cart_line_id' => $this->cartLineId,
            'uploader_id' => $this->uploaderId,
        ]);

        $this->dispatch('probo-upload-complete', [
            'cartLineId' => $this->cartLineId,
            'uploaderId' => $this->uploaderId,
        ]);
    }

    /**
     * Reset the uploader session.
     */
    public function resetSession(): void
    {
        $cartLine = CartLine::find($this->cartLineId);

        if ($cartLine) {
            $meta = $cartLine->meta ?? [];
            unset($meta['probo_uploader_id']);
            unset($meta['probo_uploader_url']);
            unset($meta['probo_uploader_status']);
            $cartLine->update(['meta' => $meta]);
        }

        $this->uploaderId = null;
        $this->uploaderUrl = null;
        $this->status = 'pending';
        $this->errorMessage = null;
        $this->stopPolling();

        Log::info('ProboUploaderIntegration: Session reset', [
            'cart_line_id' => $this->cartLineId,
        ]);
    }

    /**
     * Check if the uploader is complete.
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['confirmed', 'processed']);
    }

    public function render()
    {
        return view('livewire.components.probo-uploader-integration');
    }
}
