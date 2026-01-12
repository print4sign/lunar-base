<?php

namespace Lunar\Database\Factories;

use Lunar\Models\Supplier;

class SupplierFactory extends BaseFactory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'handle' => fake()->unique()->slug(),
            'driver' => 'offline',
            'capabilities' => ['manual'],
            'enabled' => true,
            'priority' => 100,
            'credentials' => [],
            'meta' => [],
        ];
    }
}
