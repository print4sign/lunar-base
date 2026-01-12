<?php

namespace Lunar\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Lunar\Models\Contracts\SupplierOrder;

class SupplierOrderDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SupplierOrder $supplierOrder
    ) {}
}
