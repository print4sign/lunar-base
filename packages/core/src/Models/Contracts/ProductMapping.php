<?php

namespace Lunar\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface ProductMapping
{
    /**
     * Return the supplier products relationship.
     */
    public function supplierProducts(): BelongsToMany;

    /**
     * Return the confirmed supplier products relationship.
     */
    public function confirmedSupplierProducts(): BelongsToMany;
}
