<?php

namespace Lunar\Base;

use Illuminate\Support\Collection;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Models\Contracts\Supplier;
use Lunar\Models\Contracts\SupplierOrder;

interface SupplierDriverInterface
{
    /**
     * Set the supplier instance.
     */
    public function setSupplier(Supplier $supplier): self;

    /**
     * Get the supplier instance.
     */
    public function getSupplier(): Supplier;

    /**
     * Set any configuration on the driver.
     */
    public function setConfig(array $config): self;

    // Catalog methods

    /**
     * Sync the product catalog from the supplier.
     */
    public function syncCatalog(): Collection;

    /**
     * Get a single product from the supplier.
     */
    public function getProduct(string $externalId): ?array;

    /**
     * Get the configurator schema for a product.
     */
    public function getConfiguratorSchema(string $externalId): ?array;

    /**
     * Configure a product iteratively.
     *
     * This method is used for products that require step-by-step configuration
     * where available options depend on previous selections.
     */
    public function configure(string $externalId, array $selections = []): ConfiguratorResponse;

    // Pricing methods

    /**
     * Get the price for a product with configuration.
     */
    public function getPrice(string $externalId, array $configuration): PriceResponse;

    /**
     * Get bulk prices for multiple products.
     */
    public function getBulkPrices(array $items): Collection;

    /**
     * Get price with shipping options for a configured product.
     *
     * This extends getPrice() to include shipping options from the supplier.
     */
    public function getPriceWithShipping(string $externalId, array $configuration, ?array $address = null): PriceResponse;

    // Ordering methods

    /**
     * Submit an order to the supplier.
     */
    public function submitOrder(SupplierOrder $supplierOrder): OrderResponse;

    /**
     * Get the status of an order.
     */
    public function getOrderStatus(string $externalOrderId): StatusResponse;

    /**
     * Cancel an order.
     */
    public function cancelOrder(string $externalOrderId): bool;

    // Capabilities

    /**
     * Get the capabilities of this driver.
     */
    public function capabilities(): array;

    /**
     * Check if the driver supports a capability.
     */
    public function supports(string $capability): bool;
}
