<?php

namespace Lunar\Database\Factories;

use Lunar\Models\OrderLine;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierOrder;

class SupplierOrderFactory extends BaseFactory
{
    protected $model = SupplierOrder::class;

    public function definition(): array
    {
        return [
            'order_line_id' => OrderLine::factory(),
            'supplier_id' => Supplier::factory(),
            'external_order_id' => null,
            'status' => 'pending',
            'submitted_at' => null,
            'external_data' => [],
        ];
    }
}
