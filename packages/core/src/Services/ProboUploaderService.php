<?php

namespace Lunar\Services;

use Illuminate\Support\Facades\Log;
use Lunar\Drivers\Suppliers\ProboDriver;
use Lunar\Models\CartLine;
use Lunar\Models\PrintAsset;
use Lunar\Models\SupplierProduct;

class ProboUploaderService
{
    public function __construct(
        protected ProboDriver $driver
    ) {}

    /**
     * Create a Probo uploader session for a cart line.
     *
     * @return array{id: string, url: string, external_id: string, expires_at: string|null}
     */
    public function createSessionForCartLine(CartLine $cartLine, string $callbackUrl): array
    {
        $meta = $cartLine->meta ?? [];
        $calculationId = $meta['calculation_id'] ?? null;

        if (! $calculationId) {
            throw new \RuntimeException('Cart line does not have a calculation_id. Configure the product first.');
        }

        // Get the supplier from the cart line's purchasable
        $supplier = $this->getSupplierFromCartLine($cartLine);

        if (! $supplier) {
            throw new \RuntimeException('Cart line does not have an associated supplier.');
        }

        // Set the supplier on the driver before making API calls
        $this->driver->setSupplier($supplier);

        $externalId = (string) $cartLine->id;

        $session = $this->driver->createUploaderSession($calculationId, $callbackUrl, $externalId);

        Log::debug('ProboUploaderService: Session created', [
            'cart_line_id' => $cartLine->id,
            'uploader_id' => $session['id'] ?? null,
            'external_id' => $externalId,
        ]);

        return $session;
    }

    /**
     * Get the supplier from a cart line.
     */
    protected function getSupplierFromCartLine(CartLine $cartLine): ?\Lunar\Models\Supplier
    {
        // First try to get from meta (supplier_product_id)
        $meta = $cartLine->meta ?? [];
        $supplierProductId = $meta['supplier_product_id'] ?? null;

        if ($supplierProductId) {
            $supplierProduct = SupplierProduct::with('supplier')->find($supplierProductId);

            if ($supplierProduct?->supplier) {
                return $supplierProduct->supplier;
            }
        }

        // Fallback: try to get from purchasable's supplier product
        $purchasable = $cartLine->purchasable;

        if ($purchasable && method_exists($purchasable, 'supplierProduct')) {
            $supplierProduct = $purchasable->supplierProduct;

            if ($supplierProduct?->supplier) {
                return $supplierProduct->supplier;
            }
        }

        return null;
    }

    /**
     * Store the uploader session ID in the cart line meta.
     */
    public function storeSessionInCartLine(CartLine $cartLine, string $uploaderId, string $uploaderUrl): void
    {
        $meta = $cartLine->meta ?? [];
        $meta['upload_method'] = 'probo_uploader';
        $meta['probo_uploader_id'] = $uploaderId;
        $meta['probo_uploader_url'] = $uploaderUrl;
        $meta['probo_uploader_status'] = 'created';

        $cartLine->update(['meta' => $meta]);

        Log::debug('ProboUploaderService: Session stored in cart line', [
            'cart_line_id' => $cartLine->id,
            'uploader_id' => $uploaderId,
        ]);
    }

    /**
     * Process a callback from the Probo uploader.
     *
     * @param  array{uploader_id: string, external_id: string|null, status: string, files?: array}  $payload
     */
    public function processCallback(array $payload): void
    {
        $uploaderId = $payload['uploader_id'];
        $externalId = $payload['external_id'] ?? null;
        $status = $payload['status'];
        $files = $payload['files'] ?? [];

        Log::info('ProboUploaderService: Processing callback', [
            'uploader_id' => $uploaderId,
            'external_id' => $externalId,
            'status' => $status,
            'files_count' => count($files),
        ]);

        // Find the cart line by external_id (which is the cart_line_id)
        $cartLine = $externalId ? CartLine::find($externalId) : null;

        if (! $cartLine) {
            // Try finding by uploader_id in meta
            $cartLine = CartLine::where('meta->probo_uploader_id', $uploaderId)->first();
        }

        if (! $cartLine) {
            Log::warning('ProboUploaderService: Cart line not found for callback', [
                'uploader_id' => $uploaderId,
                'external_id' => $externalId,
            ]);

            return;
        }

        // Update the cart line meta with the status
        $meta = $cartLine->meta ?? [];
        $meta['probo_uploader_status'] = $status;

        if ($status === 'confirmed') {
            $meta['probo_uploader_confirmed_at'] = now()->toIso8601String();
        }

        if ($status === 'processed' && ! empty($files)) {
            $meta['probo_uploader_files'] = $files;
        }

        $cartLine->update(['meta' => $meta]);

        Log::info('ProboUploaderService: Cart line updated with callback status', [
            'cart_line_id' => $cartLine->id,
            'status' => $status,
        ]);
    }

    /**
     * Check if all Probo uploader sessions for a cart line are complete.
     *
     * NOTE: Probo does not have a status polling endpoint. Status updates are
     * delivered via the callback webhook. This method checks the locally stored status.
     */
    public function areAllSessionsComplete(CartLine $cartLine): bool
    {
        // Re-fetch the cart line to get the latest meta (in case webhook updated it)
        // Note: We don't use refresh() because CartLine uses CachesProperties trait
        // which can cause type errors with nullable typed properties like taxBreakdown
        $freshCartLine = CartLine::find($cartLine->id);

        if (! $freshCartLine) {
            return false;
        }

        $meta = $freshCartLine->meta ?? [];
        $uploadMethod = $meta['upload_method'] ?? null;

        if ($uploadMethod !== 'probo_uploader') {
            return false;
        }

        $uploaderId = $meta['probo_uploader_id'] ?? null;

        if (! $uploaderId) {
            return false;
        }

        // Check the locally stored status (updated via webhook callback)
        $status = $meta['probo_uploader_status'] ?? 'created';

        Log::debug('ProboUploaderService: Checking uploader status', [
            'cart_line_id' => $cartLine->id,
            'uploader_id' => $uploaderId,
            'status' => $status,
        ]);

        return in_array($status, ['confirmed', 'processed']);
    }

    /**
     * Get the uploader data for order submission to Probo.
     *
     * @return array{id: string, external_id: string}|null
     */
    public function getUploaderDataForOrder(CartLine $cartLine): ?array
    {
        $meta = $cartLine->meta ?? [];
        $uploadMethod = $meta['upload_method'] ?? null;

        if ($uploadMethod !== 'probo_uploader') {
            return null;
        }

        $uploaderId = $meta['probo_uploader_id'] ?? null;

        if (! $uploaderId) {
            return null;
        }

        return [
            'id' => $uploaderId,
            'external_id' => (string) $cartLine->id,
        ];
    }

    /**
     * Get the current status of an uploader session.
     */
    public function getSessionStatus(CartLine $cartLine): ?string
    {
        $meta = $cartLine->meta ?? [];

        return $meta['probo_uploader_status'] ?? null;
    }

    /**
     * Get the uploader URL for a cart line.
     */
    public function getUploaderUrl(CartLine $cartLine): ?string
    {
        $meta = $cartLine->meta ?? [];

        return $meta['probo_uploader_url'] ?? null;
    }

    /**
     * Clear the Probo uploader session from a cart line.
     */
    public function clearSession(CartLine $cartLine): void
    {
        $meta = $cartLine->meta ?? [];

        unset(
            $meta['probo_uploader_id'],
            $meta['probo_uploader_url'],
            $meta['probo_uploader_status'],
            $meta['probo_uploader_confirmed_at'],
            $meta['probo_uploader_files']
        );

        // Only clear upload_method if it was probo_uploader
        if (($meta['upload_method'] ?? null) === 'probo_uploader') {
            unset($meta['upload_method']);
        }

        $cartLine->update(['meta' => $meta]);
    }
}
