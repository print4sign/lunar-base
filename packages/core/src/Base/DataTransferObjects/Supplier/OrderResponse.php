<?php

namespace Lunar\Base\DataTransferObjects\Supplier;

class OrderResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalId = null,
        public readonly ?string $status = null,
        public readonly ?string $message = null,
        public readonly ?array $data = null,
    ) {}

    /**
     * Create a successful order response.
     */
    public static function success(string $externalId, ?string $status = null, ?array $data = null): self
    {
        return new self(
            success: true,
            externalId: $externalId,
            status: $status,
            data: $data,
        );
    }

    /**
     * Create a failed order response.
     */
    public static function failed(string $message, ?array $data = null): self
    {
        return new self(
            success: false,
            message: $message,
            data: $data,
        );
    }

    /**
     * Check if the order was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }
}
