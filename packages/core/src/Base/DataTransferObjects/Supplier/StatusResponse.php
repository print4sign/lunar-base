<?php

namespace Lunar\Base\DataTransferObjects\Supplier;

class StatusResponse
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $externalId = null,
        public readonly ?array $tracking = null,
        public readonly ?string $estimatedDelivery = null,
        public readonly ?array $data = null,
    ) {}

    /**
     * Create a StatusResponse from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'],
            externalId: $data['external_id'] ?? $data['externalId'] ?? null,
            tracking: $data['tracking'] ?? null,
            estimatedDelivery: $data['estimated_delivery'] ?? $data['estimatedDelivery'] ?? null,
            data: $data['data'] ?? null,
        );
    }

    /**
     * Check if the order is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'draft', 'awaiting']);
    }

    /**
     * Check if the order is processing.
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['processing', 'in_production', 'production']);
    }

    /**
     * Check if the order has shipped.
     */
    public function isShipped(): bool
    {
        return in_array($this->status, ['shipped', 'dispatched', 'in_transit']);
    }

    /**
     * Check if the order has been delivered.
     */
    public function isDelivered(): bool
    {
        return in_array($this->status, ['delivered', 'completed']);
    }

    /**
     * Check if the order has failed.
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'error', 'cancelled']);
    }

    /**
     * Get tracking numbers.
     */
    public function getTrackingNumbers(): array
    {
        return $this->tracking['numbers'] ?? [];
    }

    /**
     * Get tracking URL.
     */
    public function getTrackingUrl(): ?string
    {
        return $this->tracking['url'] ?? null;
    }
}
