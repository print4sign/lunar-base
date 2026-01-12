<?php

namespace Lunar\Drivers\Suppliers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Base\SupplierDriverInterface;
use Lunar\Exceptions\Suppliers\SupplierNotSupportedException;
use Lunar\Models\Contracts\Supplier;
use Lunar\Models\Contracts\SupplierOrder;

abstract class AbstractSupplierDriver implements SupplierDriverInterface
{
    /**
     * Section identifiers for dynamic visibility control.
     */
    public const SECTION_UPLOAD = 'upload';
    public const SECTION_PRICING = 'pricing';
    public const SECTION_FULFILLMENT = 'fulfillment';

    /**
     * The supplier instance.
     */
    protected Supplier $supplier;

    /**
     * The driver configuration.
     */
    protected array $config = [];

    /**
     * The base URL for API requests.
     */
    protected string $baseUrl = '';

    /**
     * Set the supplier instance.
     */
    public function setSupplier(Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Get the supplier instance.
     */
    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    /**
     * Set the configuration.
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get a configuration value.
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get a credential from the supplier.
     */
    protected function getCredential(string $key): ?string
    {
        return $this->supplier->getCredential($key);
    }

    /**
     * Create an HTTP client instance.
     */
    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->getBaseUrl())
            ->withHeaders($this->getDefaultHeaders())
            ->timeout(30);
    }

    /**
     * Get the base URL for API requests.
     */
    protected function getBaseUrl(): string
    {
        return $this->getConfig('base_url', $this->baseUrl);
    }

    /**
     * Get the default headers for API requests.
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Check if the driver supports a capability.
     */
    public function supports(string $capability): bool
    {
        return in_array($capability, $this->capabilities());
    }

    /**
     * Throw an exception for unsupported capabilities.
     */
    protected function notSupported(string $capability): never
    {
        throw new SupplierNotSupportedException(
            "The capability '{$capability}' is not supported by this supplier driver."
        );
    }

    // Default implementations that throw not supported exceptions

    /**
     * Sync the product catalog from the supplier.
     */
    public function syncCatalog(): Collection
    {
        $this->notSupported('catalog_sync');
    }

    /**
     * Get a single product from the supplier.
     */
    public function getProduct(string $externalId): ?array
    {
        $this->notSupported('catalog_sync');
    }

    /**
     * Get the configurator schema for a product.
     */
    public function getConfiguratorSchema(string $externalId): ?array
    {
        $this->notSupported('configurator');
    }

    /**
     * Configure a product iteratively.
     */
    public function configure(string $externalId, array $selections = []): ConfiguratorResponse
    {
        $this->notSupported('configurator');
    }

    /**
     * Get the price for a product with configuration.
     */
    public function getPrice(string $externalId, array $configuration): PriceResponse
    {
        $this->notSupported('pricing');
    }

    /**
     * Get bulk prices for multiple products.
     */
    public function getBulkPrices(array $items): Collection
    {
        $this->notSupported('pricing');
    }

    /**
     * Get price with shipping options for a configured product.
     */
    public function getPriceWithShipping(string $externalId, array $configuration, ?array $address = null): PriceResponse
    {
        $this->notSupported('pricing');
    }

    /**
     * Submit an order to the supplier.
     */
    public function submitOrder(SupplierOrder $supplierOrder): OrderResponse
    {
        $this->notSupported('ordering');
    }

    /**
     * Get the status of an order.
     */
    public function getOrderStatus(string $externalOrderId): StatusResponse
    {
        $this->notSupported('tracking');
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(string $externalOrderId): bool
    {
        $this->notSupported('ordering');
    }

    /**
     * Get the capabilities of this driver.
     */
    abstract public function capabilities(): array;
}
