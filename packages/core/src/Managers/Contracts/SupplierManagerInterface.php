<?php

namespace Lunar\Managers\Contracts;

use Lunar\Base\SupplierDriverInterface;
use Lunar\Models\Contracts\Supplier;

interface SupplierManagerInterface
{
    /**
     * Get a supplier driver instance.
     */
    public function supplier(string|Supplier $supplier): SupplierDriverInterface;

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string;
}
