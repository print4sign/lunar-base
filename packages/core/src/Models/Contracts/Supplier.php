<?php

namespace Lunar\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

interface Supplier
{
    /**
     * Return the supplier products relationship.
     */
    public function products(): HasMany;

    /**
     * Return the supplier orders relationship.
     */
    public function supplierOrders(): HasMany;

    /**
     * Return the prices relationship.
     */
    public function prices(): HasMany;

    /**
     * Check if the supplier is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Check if the supplier supports a capability.
     */
    public function supports(string $capability): bool;
}
