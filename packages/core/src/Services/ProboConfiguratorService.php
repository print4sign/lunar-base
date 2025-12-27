<?php

namespace Lunar\Services;

use Illuminate\Support\Facades\Cache;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\SupplierDriverInterface;
use Lunar\Facades\Suppliers;
use Lunar\Models\SupplierProduct;

class ProboConfiguratorService
{
    /**
     * The supplier product being configured.
     */
    protected SupplierProduct $supplierProduct;

    /**
     * The driver instance.
     */
    protected SupplierDriverInterface $driver;

    /**
     * Current configuration selections.
     */
    protected array $selections = [];

    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl = 300;

    /**
     * Create a new service instance for a supplier product.
     */
    public static function forSupplierProduct(SupplierProduct $supplierProduct): self
    {
        $instance = new self();
        $instance->supplierProduct = $supplierProduct;
        $instance->driver = Suppliers::driver($supplierProduct->supplier->driver)
            ->setSupplier($supplierProduct->supplier);

        return $instance;
    }

    /**
     * Create a new service instance from a supplier product ID.
     */
    public static function forSupplierProductId(int|string $supplierProductId): self
    {
        $supplierProduct = SupplierProduct::with('supplier')->findOrFail($supplierProductId);

        return self::forSupplierProduct($supplierProduct);
    }

    /**
     * Initialize the configurator for the product.
     */
    public function initialize(): ConfiguratorResponse
    {
        $this->selections = [];

        return $this->configure([]);
    }

    /**
     * Configure with the given selections.
     */
    public function configure(array $selections): ConfiguratorResponse
    {
        $this->selections = array_merge($this->selections, $selections);

        $cacheKey = $this->getCacheKey('configure', $this->selections);

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return $this->driver->configure(
                $this->supplierProduct->external_id,
                $this->selections
            );
        });
    }

    /**
     * Set a single option value.
     */
    public function setOption(string $key, mixed $value): ConfiguratorResponse
    {
        return $this->configure([$key => $value]);
    }

    /**
     * Reset a specific option (and dependent options).
     */
    public function resetOption(string $key): ConfiguratorResponse
    {
        unset($this->selections[$key]);

        // Clear cache and re-configure
        $this->clearCache();

        return $this->configure($this->selections);
    }

    /**
     * Get the current selections.
     */
    public function getSelections(): array
    {
        return $this->selections;
    }

    /**
     * Set all selections at once.
     */
    public function setSelections(array $selections): self
    {
        $this->selections = $selections;

        return $this;
    }

    /**
     * Set the quantity.
     */
    public function setQuantity(int $quantity): self
    {
        $this->selections['quantity'] = max(1, $quantity);

        return $this;
    }

    /**
     * Get the current quantity.
     */
    public function getQuantity(): int
    {
        return $this->selections['quantity'] ?? 1;
    }

    /**
     * Get the price for the current configuration.
     */
    public function getPrice(?array $address = null): PriceResponse
    {
        $configuration = $this->selections;

        $cacheKey = $this->getCacheKey('price', [
            'config' => $configuration,
            'address' => $address,
        ]);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($configuration, $address) {
            return $this->driver->getPriceWithShipping(
                $this->supplierProduct->external_id,
                $configuration,
                $address
            );
        });
    }

    /**
     * Finalize the configuration and return the data to store on a variant.
     */
    public function finalizeConfiguration(): array
    {
        $configuration = $this->selections;
        $configuration['hash'] = $this->generateConfigurationHash($configuration);

        return $configuration;
    }

    /**
     * Generate a unique hash for the configuration.
     */
    protected function generateConfigurationHash(array $configuration): string
    {
        // Remove hash if present to avoid recursion
        unset($configuration['hash']);

        // Sort for consistency
        ksort($configuration);

        return md5(json_encode([
            'supplier_product_id' => $this->supplierProduct->id,
            'external_id' => $this->supplierProduct->external_id,
            'configuration' => $configuration,
        ]));
    }

    /**
     * Validate if the current configuration is complete.
     */
    public function isComplete(): bool
    {
        $response = $this->configure($this->selections);

        return $response->isComplete();
    }

    /**
     * Check if configuration can be ordered.
     */
    public function canOrder(): bool
    {
        return $this->isComplete();
    }

    /**
     * Get a cache key for the given operation and data.
     */
    protected function getCacheKey(string $operation, array $data): string
    {
        $hash = md5(json_encode($data));

        return "probo_configurator.{$this->supplierProduct->id}.{$operation}.{$hash}";
    }

    /**
     * Clear the cache for this configurator.
     */
    public function clearCache(): self
    {
        $pattern = "probo_configurator.{$this->supplierProduct->id}.*";
        Cache::forget($pattern);

        return $this;
    }

    /**
     * Set the cache TTL.
     */
    public function setCacheTtl(int $seconds): self
    {
        $this->cacheTtl = $seconds;

        return $this;
    }

    /**
     * Disable caching.
     */
    public function withoutCache(): self
    {
        $this->cacheTtl = 0;

        return $this;
    }

    /**
     * Get the supplier product.
     */
    public function getSupplierProduct(): SupplierProduct
    {
        return $this->supplierProduct;
    }

    /**
     * Get the driver instance.
     */
    public function getDriver(): SupplierDriverInterface
    {
        return $this->driver;
    }

    /**
     * Create variant data from the current configuration.
     */
    public function toVariantData(): array
    {
        $configuration = $this->finalizeConfiguration();
        $price = $this->getPrice();

        return [
            'supplier_product_id' => $this->supplierProduct->id,
            'configuration' => $configuration,
            'cost_price' => $price->costPrice,
            'base_price' => $price->sellPrice,
        ];
    }
}
