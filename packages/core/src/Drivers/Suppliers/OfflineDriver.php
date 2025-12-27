<?php

namespace Lunar\Drivers\Suppliers;

use Illuminate\Support\Collection;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Models\Contracts\SupplierOrder;

/**
 * Offline driver for manual supplier management.
 * This driver is used when suppliers don't have API integration.
 */
class OfflineDriver extends AbstractSupplierDriver
{
    /**
     * Get the capabilities of this driver.
     */
    public function capabilities(): array
    {
        return ['manual'];
    }

    /**
     * Sync catalog returns empty for offline suppliers.
     */
    public function syncCatalog(): Collection
    {
        return collect();
    }

    /**
     * Get product returns null for offline suppliers.
     */
    public function getProduct(string $externalId): ?array
    {
        return null;
    }

    /**
     * Get the price for a product.
     * For offline suppliers, this returns a placeholder that should be manually set.
     */
    public function getPrice(string $externalId, array $configuration): PriceResponse
    {
        return new PriceResponse(
            costPrice: 0,
            sellPrice: 0,
            currency: 'EUR',
            meta: ['manual' => true],
        );
    }

    /**
     * Submit order returns a manual response.
     */
    public function submitOrder(SupplierOrder $supplierOrder): OrderResponse
    {
        return OrderResponse::success(
            externalId: 'MANUAL-'.uniqid(),
            status: 'manual',
            data: ['requires_manual_processing' => true],
        );
    }

    /**
     * Get order status for offline suppliers.
     */
    public function getOrderStatus(string $externalOrderId): StatusResponse
    {
        return new StatusResponse(
            status: 'manual',
            externalId: $externalOrderId,
            data: ['requires_manual_tracking' => true],
        );
    }

    /**
     * Cancel order for offline suppliers.
     */
    public function cancelOrder(string $externalOrderId): bool
    {
        return true;
    }
}
