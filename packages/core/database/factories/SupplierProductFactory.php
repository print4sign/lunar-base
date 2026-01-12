<?php

namespace Lunar\Database\Factories;

use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;

class SupplierProductFactory extends BaseFactory
{
    protected $model = SupplierProduct::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'external_id' => fake()->unique()->bothify('EXT-###-???'),
            'external_name' => fake()->words(3, true),
            'external_data' => [],
            'active' => true,
            'synced' => false,
            'last_synced_at' => null,
        ];
    }
}
