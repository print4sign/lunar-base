<?php

namespace Lunar\DataTransferObjects;

use Lunar\Models\Contracts\SupplierOrder;

class SupplierOrderCancellationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly SupplierOrder $supplierOrder,
        public readonly bool $supplierCancelled = false,
        public readonly int $cancellationFee = 0,
        public readonly mixed $refund = null,
        public readonly ?string $error = null
    ) {}

    public static function success(SupplierOrder $supplierOrder, bool $supplierCancelled, int $cancellationFee, mixed $refund): self
    {
        return new self(
            success: true,
            supplierOrder: $supplierOrder,
            supplierCancelled: $supplierCancelled,
            cancellationFee: $cancellationFee,
            refund: $refund
        );
    }

    public static function failed(string $error, SupplierOrder $supplierOrder): self
    {
        return new self(
            success: false,
            supplierOrder: $supplierOrder,
            error: $error
        );
    }
}
