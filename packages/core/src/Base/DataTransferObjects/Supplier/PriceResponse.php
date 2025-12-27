<?php

namespace Lunar\Base\DataTransferObjects\Supplier;

class PriceResponse
{
    public function __construct(
        public readonly int $costPrice,
        public readonly int $sellPrice,
        public readonly string $currency,
        public readonly ?int $compareCostPrice = null,
        public readonly ?int $compareSellPrice = null,
        public readonly ?array $breakdown = null,
        public readonly ?array $meta = null,
    ) {}

    /**
     * Create a PriceResponse from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            costPrice: $data['cost_price'] ?? $data['costPrice'],
            sellPrice: $data['sell_price'] ?? $data['sellPrice'],
            currency: $data['currency'] ?? 'EUR',
            compareCostPrice: $data['compare_cost_price'] ?? $data['compareCostPrice'] ?? null,
            compareSellPrice: $data['compare_sell_price'] ?? $data['compareSellPrice'] ?? null,
            breakdown: $data['breakdown'] ?? null,
            meta: $data['meta'] ?? null,
        );
    }

    /**
     * Calculate the margin.
     */
    public function getMargin(): int
    {
        return $this->sellPrice - $this->costPrice;
    }

    /**
     * Calculate the margin percentage.
     */
    public function getMarginPercentage(): float
    {
        if ($this->sellPrice === 0) {
            return 0;
        }

        return ($this->getMargin() / $this->sellPrice) * 100;
    }
}
