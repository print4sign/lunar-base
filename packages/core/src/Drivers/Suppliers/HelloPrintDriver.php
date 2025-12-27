<?php

namespace Lunar\Drivers\Suppliers;

use Illuminate\Support\Collection;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Models\Contracts\SupplierOrder;

class HelloPrintDriver extends AbstractSupplierDriver
{
    /**
     * The base URL for the HelloPrint API.
     */
    protected string $baseUrl = 'https://api.helloprint.com/';

    /**
     * Get the capabilities of this driver.
     */
    public function capabilities(): array
    {
        return [
            'catalog_sync',
            'pricing',
            'ordering',
            'configurator',
        ];
    }

    /**
     * Sync the product catalog from HelloPrint.
     */
    public function syncCatalog(): Collection
    {
        // TODO: Implement HelloPrint catalog sync
        return collect();
    }

    /**
     * Get a single product from HelloPrint.
     */
    public function getProduct(string $externalId): ?array
    {
        // TODO: Implement HelloPrint product fetch
        return null;
    }

    /**
     * Get the configurator schema for a product.
     */
    public function getConfiguratorSchema(string $externalId): ?array
    {
        // TODO: Implement HelloPrint configurator schema
        return null;
    }

    /**
     * Configure a product iteratively.
     */
    public function configure(string $externalId, array $selections = []): ConfiguratorResponse
    {
        // TODO: Implement HelloPrint configurator
        // For now, return an empty response with placeholder data
        return new ConfiguratorResponse(
            externalId: $externalId,
            options: [],
            selections: $selections,
            canOrder: false,
            errors: null,
            rawData: [
                'message' => 'HelloPrint configurator not yet implemented',
            ]
        );
    }

    /**
     * Get the price for a product with configuration.
     */
    public function getPrice(string $externalId, array $configuration): PriceResponse
    {
        // TODO: Implement HelloPrint pricing
        return new PriceResponse(
            costPrice: 0,
            sellPrice: 0,
            currency: 'EUR',
        );
    }

    /**
     * Get bulk prices for multiple products.
     */
    public function getBulkPrices(array $items): Collection
    {
        // TODO: Implement HelloPrint bulk pricing
        return collect();
    }

    /**
     * Get price with shipping options.
     */
    public function getPriceWithShipping(string $externalId, array $configuration, ?array $address = null): PriceResponse
    {
        // TODO: Implement HelloPrint pricing with shipping
        return $this->getPrice($externalId, $configuration);
    }

    /**
     * Submit an order to HelloPrint.
     */
    public function submitOrder(SupplierOrder $supplierOrder): OrderResponse
    {
        // TODO: Implement HelloPrint order submission
        return OrderResponse::failed(
            'HelloPrint ordering not yet implemented'
        );
    }

    /**
     * Get the status of an order.
     */
    public function getOrderStatus(string $externalOrderId): StatusResponse
    {
        // TODO: Implement HelloPrint order status
        return new StatusResponse(
            status: 'unknown',
            externalId: $externalOrderId,
        );
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(string $externalOrderId): bool
    {
        // TODO: Implement HelloPrint order cancellation
        return false;
    }
}
