<?php

namespace Lunar\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface SupplierOrder
{
    /**
     * Return the order relationship.
     */
    public function order(): BelongsTo;

    /**
     * Return the order line relationship.
     */
    public function orderLine(): BelongsTo;

    /**
     * Return the supplier relationship.
     */
    public function supplier(): BelongsTo;

    /**
     * Check if the order is pending.
     */
    public function isPending(): bool;

    /**
     * Check if the order has been submitted.
     */
    public function isSubmitted(): bool;
}
