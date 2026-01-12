<?php

namespace Lunar\DataTransferObjects;

use Lunar\Models\Contracts\SupplierProduct;

class ProductMatch
{
    public function __construct(
        public readonly SupplierProduct $supplierProduct,
        public readonly int $score,
        public readonly string $reasoning,
        public readonly array $matches = [],
        public readonly array $concerns = []
    ) {}

    /**
     * Get score color based on value
     */
    public function getScoreColor(): string
    {
        return match (true) {
            $this->score >= 90 => 'success',
            $this->score >= 75 => 'info',
            $this->score >= 60 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Get score label based on value
     */
    public function getScoreLabel(): string
    {
        return match (true) {
            $this->score >= 90 => 'Excellent Match',
            $this->score >= 75 => 'Good Match',
            $this->score >= 60 => 'Moderate Match',
            default => 'Poor Match',
        };
    }

    /**
     * Check if this is a recommended match
     */
    public function isRecommended(): bool
    {
        return $this->score >= 75;
    }

    /**
     * Get matched fields as formatted list
     */
    public function getMatchedFields(): array
    {
        return collect($this->matches)
            ->filter(fn ($matched) => $matched === true)
            ->keys()
            ->map(fn ($key) => str_replace('_', ' ', ucfirst($key)))
            ->toArray();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'supplier_product_id' => $this->supplierProduct->id,
            'supplier_name' => $this->supplierProduct->supplier->name,
            'product_name' => $this->supplierProduct->external_name,
            'external_id' => $this->supplierProduct->external_id,
            'score' => $this->score,
            'score_label' => $this->getScoreLabel(),
            'score_color' => $this->getScoreColor(),
            'reasoning' => $this->reasoning,
            'matches' => $this->matches,
            'matched_fields' => $this->getMatchedFields(),
            'concerns' => $this->concerns,
            'is_recommended' => $this->isRecommended(),
        ];
    }
}
