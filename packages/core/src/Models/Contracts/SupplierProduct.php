<?php

namespace Lunar\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface SupplierProduct
{
    /**
     * Return the supplier relationship.
     */
    public function supplier(): BelongsTo;

    /**
     * Return the product relationship.
     */
    public function product(): BelongsTo;

    /**
     * Return the product variant relationship.
     */
    public function productVariant(): BelongsTo;

    /**
     * Return the variants relationship.
     */
    public function variants(): HasMany;

    /**
     * Return the product mappings relationship.
     */
    public function mappings(): BelongsToMany;
}
