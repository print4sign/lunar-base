<?php

namespace Lunar\Drivers\Suppliers;

use Illuminate\Support\Collection;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Models\Contracts\SupplierOrder;

/**
 * Internal driver for in-house product fulfillment.
 *
 * This driver ensures ALL products flow through the same supplier order system,
 * whether they're externally sourced or internally fulfilled. It provides minimal
 * capabilities (ordering + tracking) and delegates pricing to the product variant.
 */
class InternalDriver extends AbstractSupplierDriver
{
    /**
     * Get the capabilities of this driver.
     */
    public function capabilities(): array
    {
        return ['ordering', 'tracking'];
    }

    /**
     * Sync catalog returns empty for internal suppliers.
     */
    public function syncCatalog(): Collection
    {
        return collect();
    }

    /**
     * Get product returns null for internal suppliers.
     */
    public function getProduct(string $externalId): ?array
    {
        return null;
    }

    /**
     * Get the configurator schema for a product.
     */
    public function getConfiguratorSchema(string $externalId): ?array
    {
        return null;
    }

    /**
     * Configure a product - always returns can_order=true for internal products.
     */
    public function configure(string $externalId, array $selections = []): ConfiguratorResponse
    {
        return new ConfiguratorResponse(
            productCode: $externalId,
            availableOptions: [],
            selectedOptions: $selections,
            canOrder: true,
        );
    }

    /**
     * Get the price for a product.
     * For internal suppliers, this returns zero (pricing comes from product variant).
     */
    public function getPrice(string $externalId, array $configuration): PriceResponse
    {
        return new PriceResponse(
            costPrice: 0,
            sellPrice: 0,
            currency: 'EUR',
            meta: ['internal' => true],
        );
    }

    /**
     * Get bulk prices - not applicable for internal suppliers.
     */
    public function getBulkPrices(array $items): Collection
    {
        return collect();
    }

    /**
     * Get price with shipping - delegates to getPrice.
     */
    public function getPriceWithShipping(string $externalId, array $configuration, ?array $address = null): PriceResponse
    {
        return $this->getPrice($externalId, $configuration);
    }

    /**
     * Submit an order to internal fulfillment.
     * Generates an internal reference for tracking.
     */
    public function submitOrder(SupplierOrder $supplierOrder): OrderResponse
    {
        $orderLine = $supplierOrder->orderLine;
        $order = $orderLine->order;

        // Generate internal external ID format: INTERNAL-{reference}-{line_id}
        $externalId = sprintf(
            'INTERNAL-%s-%d',
            $order->reference,
            $orderLine->id
        );

        return OrderResponse::success(
            externalId: $externalId,
            status: 'submitted',
            data: ['internal_fulfillment' => true],
        );
    }

    /**
     * Get the status of an internal order.
     */
    public function getOrderStatus(string $externalOrderId): StatusResponse
    {
        return new StatusResponse(
            status: 'processing',
            externalId: $externalOrderId,
            data: ['internal_fulfillment' => true],
        );
    }

    /**
     * Cancel an internal order - always returns true.
     */
    public function cancelOrder(string $externalOrderId): bool
    {
        return true;
    }

    /**
     * Get hidden sections for dynamic products (not applicable for internal).
     */
    public function getHiddenSectionsForDynamic(): array
    {
        return [];
    }

    /**
     * Get configurator component (not applicable for internal).
     */
    public function getConfiguratorComponent(): ?string
    {
        return null;
    }

    /**
     * Get dynamic price - delegates to getPrice.
     */
    public function getDynamicPrice(string $externalId, array $configuration): PriceResponse
    {
        return $this->getPrice($externalId, $configuration);
    }

    /**
     * Get cart line pipelines (not applicable for internal).
     */
    public function getCartLinePipelines(): array
    {
        return [];
    }
}
