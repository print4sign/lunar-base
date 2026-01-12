<?php

namespace Lunar\DataTransferObjects;

class SupplierOrderCancellationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly $supplierOrder,
        public readonly bool $supplierCancelled = false,
        public readonly int $cancellationFee = 0,
        public readonly $refund = null,
        public readonly ?string $error = null
    ) {}

    public static function success($supplierOrder, bool $supplierCancelled, int $cancellationFee, $refund): self
    {
        return new self(
            success: true,
            supplierOrder: $supplierOrder,
            supplierCancelled: $supplierCancelled,
            cancellationFee: $cancellationFee,
            refund: $refund
        );
    }

    public static function failed(string $error, $supplierOrder): self
    {
        return new self(
            success: false,
            supplierOrder: $supplierOrder,
            error: $error
        );
    }
}
