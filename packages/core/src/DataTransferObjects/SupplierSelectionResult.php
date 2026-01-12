<?php

namespace Lunar\DataTransferObjects;

use Illuminate\Support\Collection;

class SupplierSelectionResult
{
    public function __construct(
        public readonly array $primary,
        public readonly Collection $fallbacks,
        public readonly Collection $allQuotes
    ) {}

    public function getReason(): string
    {
        $factors = [];

        if (isset($this->primary['score'])) {
            $factors[] = "Score: {$this->primary['score']}/100";
        }

        if (isset($this->primary['quote']['cost_price'])) {
            $price = $this->primary['quote']['cost_price'] / 100;
            $factors[] = "Cost: €{$price}";
        }

        return implode(', ', $factors);
    }
}
