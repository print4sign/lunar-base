<?php

namespace Lunar\Traits;

use Lunar\Base\Contracts\ProvidesUploadSpec;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Facades\Suppliers;
use Lunar\Models\ProductVariant;

/**
 * Trait for models that can resolve upload specifications.
 *
 * Used by CartLine and OrderLine to resolve upload specs through a chain:
 * 1. Check meta['upload_spec'] (dynamic from configurator)
 * 2. Check purchasable->upload_spec (static on variant)
 * 3. Try supplier API if ProvidesUploadSpec
 *
 * For dimensional products (m², m, cm²), the uploader dimensions are
 * computed from the customer's configuration stored in meta.
 */
trait HasUploadSpec
{
    /**
     * Cached resolved upload spec.
     */
    protected ?UploadSpec $resolvedUploadSpec = null;

    /**
     * Whether we've attempted to resolve the upload spec.
     */
    protected bool $uploadSpecResolved = false;

    /**
     * Resolve the upload specification for this line.
     *
     * Resolution chain:
     * 1. Check meta['upload_spec'] (dynamic from configurator)
     * 2. Check purchasable->upload_spec (static on variant)
     * 3. Try supplier API if ProvidesUploadSpec
     *
     * For dimensional units (m², m, cm²), the uploader dimensions
     * are overridden from the customer's configured dimensions.
     */
    public function resolveUploadSpec(): ?UploadSpec
    {
        // Return cached result if already resolved
        if ($this->uploadSpecResolved) {
            return $this->resolvedUploadSpec;
        }

        $this->uploadSpecResolved = true;
        $spec = null;

        // 1. Check meta for dynamic upload spec (from configurator)
        $meta = $this->meta ?? [];
        if (! empty($meta['upload_spec'])) {
            $spec = is_array($meta['upload_spec'])
                ? UploadSpec::fromArray($meta['upload_spec'])
                : $meta['upload_spec'];
        }

        // 2. Check purchasable for static upload spec
        if (! $spec) {
            $purchasable = $this->purchasable;
            if ($purchasable instanceof ProductVariant && $purchasable->upload_spec) {
                $spec = $purchasable->upload_spec;
            }
        }

        // 3. Try supplier API if we still don't have a spec
        if (! $spec) {
            $spec = $this->resolveUploadSpecFromSupplier();
        }

        // Apply dimensional overrides if applicable
        if ($spec) {
            $spec = $this->applyDimensionalOverrides($spec);
        }

        $this->resolvedUploadSpec = $spec;

        return $spec;
    }

    /**
     * Check if this line requires file uploads.
     */
    public function requiresUpload(): bool
    {
        return $this->resolveUploadSpec()?->requiresUpload() ?? false;
    }

    /**
     * Try to resolve upload spec from supplier API.
     */
    protected function resolveUploadSpecFromSupplier(): ?UploadSpec
    {
        $purchasable = $this->purchasable;

        if (! $purchasable instanceof ProductVariant) {
            return null;
        }

        $supplierProduct = $purchasable->supplierProduct;
        if (! $supplierProduct) {
            return null;
        }

        $supplier = $supplierProduct->supplier;
        if (! $supplier || ! $supplier->enabled) {
            return null;
        }

        try {
            $driver = Suppliers::supplier($supplier);

            // Check if driver provides upload specs
            if (! $driver instanceof ProvidesUploadSpec) {
                return null;
            }

            // Get configuration from meta (for dynamic variants) or from variant
            $meta = $this->meta ?? [];
            $configuration = ($purchasable->isDynamic() && isset($meta['supplier_configuration']))
                ? $meta['supplier_configuration']
                : $purchasable->configuration ?? [];

            if (empty($configuration)) {
                return null;
            }

            $externalId = $supplierProduct->external_data['api_code']
                ?? $supplierProduct->external_id;

            return $driver->resolveUploadSpec($externalId, $configuration);
        } catch (\Exception $e) {
            // Log error but don't break the request
            logger()->warning('Failed to resolve upload spec from supplier', [
                'error' => $e->getMessage(),
                'purchasable_id' => $purchasable->id,
            ]);

            return null;
        }
    }

    /**
     * Apply dimensional overrides to the upload spec.
     *
     * For variants with dimensional units (m², m, cm²), the uploader
     * dimensions are computed from the customer's configuration.
     */
    protected function applyDimensionalOverrides(UploadSpec $spec): UploadSpec
    {
        $purchasable = $this->purchasable;

        if (! $purchasable instanceof ProductVariant) {
            return $spec;
        }

        // Check if the variant has a dimensional unit
        $unitCode = $purchasable->getUnitCode();
        if (! $unitCode?->isDimensional()) {
            return $spec;
        }

        // Get configured dimensions from cart line meta
        $meta = $this->meta ?? [];
        $configuredDimensions = $meta['supplier_configuration'] ?? [];

        // Only override if we have dimensions in the configuration
        if (empty($configuredDimensions['width']) && empty($configuredDimensions['height'])) {
            return $spec;
        }

        return $spec->withDimensions(
            width: $configuredDimensions['width'] ?? null,
            height: $configuredDimensions['height'] ?? null,
            length: $configuredDimensions['length'] ?? null,
        );
    }

    /**
     * Clear the cached upload spec resolution.
     *
     * Call this when meta or purchasable changes.
     */
    public function clearUploadSpecCache(): void
    {
        $this->resolvedUploadSpec = null;
        $this->uploadSpecResolved = false;
    }
}
