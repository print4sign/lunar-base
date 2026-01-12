<?php

namespace Lunar\Base\Contracts;

use Lunar\Base\DataTransferObjects\Upload\UploadSpec;

/**
 * Interface for supplier drivers that can provide upload specifications.
 *
 * Supplier drivers implementing this interface can provide dynamic upload
 * specifications based on product configuration. This is used for products
 * like custom prints where the upload requirements (dimensions, DPI, etc.)
 * are determined by the supplier API based on the product options chosen.
 */
interface ProvidesUploadSpec
{
    /**
     * Resolve the upload specification for a supplier product.
     *
     * This method should call the supplier's API to get the upload requirements
     * based on the given configuration. The configuration typically includes
     * dimensions, material choices, and other product options.
     *
     * @param  string  $externalId  The supplier's external ID for the product
     * @param  array  $configuration  The product configuration (options chosen)
     * @return UploadSpec|null The resolved upload spec, or null if none required
     */
    public function resolveUploadSpec(string $externalId, array $configuration): ?UploadSpec;

    /**
     * Check if a supplier product requires file uploads for the given configuration.
     *
     * This is a quick check that can be used before calling resolveUploadSpec()
     * to avoid unnecessary API calls when uploads aren't required.
     *
     * @param  string  $externalId  The supplier's external ID for the product
     * @param  array  $configuration  The product configuration (options chosen)
     * @return bool True if the product requires file uploads
     */
    public function requiresUpload(string $externalId, array $configuration): bool;
}
