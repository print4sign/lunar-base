<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lunar\Models\CartLine;
use Lunar\Services\ProboUploaderService;

class ProboWebhookController extends Controller
{
    public function __construct(
        protected ProboUploaderService $uploaderService
    ) {}

    /**
     * Handle Probo uploader callback.
     *
     * This endpoint is called by Probo when a user completes their upload
     * in the Probo hosted uploader.
     */
    public function uploaderCallback(Request $request): JsonResponse
    {
        Log::info('ProboWebhookController: Received uploader callback', [
            'payload' => $request->all(),
        ]);

        $uploaderId = $request->input('uploader_id') ?? $request->input('id');
        $status = $request->input('status');
        $externalId = $request->input('external_id');

        if (! $uploaderId) {
            Log::warning('ProboWebhookController: Missing uploader_id in callback');

            return response()->json(['error' => 'Missing uploader_id'], 400);
        }

        try {
            // Process the callback
            $this->uploaderService->processCallback([
                'uploader_id' => $uploaderId,
                'status' => $status,
                'external_id' => $externalId,
                'payload' => $request->all(),
            ]);

            // Find the cart line associated with this uploader
            $cartLine = $this->findCartLineByUploaderId($uploaderId);

            if ($cartLine) {
                // Broadcast event for real-time UI update via Livewire
                // The Livewire component listens for this event
                broadcast(new \App\Events\ProboUploaderCallback(
                    $cartLine->id,
                    $uploaderId,
                    $status
                ))->toOthers();

                Log::info('ProboWebhookController: Callback processed successfully', [
                    'uploader_id' => $uploaderId,
                    'cart_line_id' => $cartLine->id,
                    'status' => $status,
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('ProboWebhookController: Failed to process callback', [
                'uploader_id' => $uploaderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Find the cart line associated with a Probo uploader ID.
     */
    protected function findCartLineByUploaderId(string $uploaderId): ?CartLine
    {
        return CartLine::where('meta->probo_uploader_id', $uploaderId)->first();
    }
}
