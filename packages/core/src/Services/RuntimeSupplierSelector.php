<?php

namespace Lunar\Services;

use Illuminate\Support\Collection;
use Lunar\DataTransferObjects\SupplierSelectionResult;
use Lunar\Exceptions\Suppliers\NoSuppliersAvailableException;
use Lunar\Managers\SupplierManager;
use Lunar\Models\Contracts\Address;
use Lunar\Models\Contracts\Order;
use Lunar\Models\Contracts\OrderLine;
use Lunar\Models\Contracts\ProductVariant;

class RuntimeSupplierSelector
{
    public function __construct(
        protected SupplierManager $suppliers
    ) {}

    /**
     * Select the best supplier for an order line at order placement time
     */
    public function selectBestSupplier(
        OrderLine $orderLine,
        Order $order,
        array $criteria = []
    ): SupplierSelectionResult {

        $variant = $orderLine->purchasable;
        $quantity = $orderLine->quantity;
        $deliveryAddress = $order->shippingAddress;

        // Get all supplier options for this variant
        $supplierOptions = $this->getAvailableSuppliers($variant);

        if ($supplierOptions->isEmpty()) {
            throw new NoSuppliersAvailableException();
        }

        // Fetch real-time data from ALL suppliers in parallel
        $supplierQuotes = $this->fetchRealTimeQuotes(
            $supplierOptions,
            $variant,
            $quantity,
            $deliveryAddress
        );

        // Score each supplier based on criteria
        $scoredSuppliers = $supplierQuotes->map(function($quote) use ($criteria, $order) {
            return [
                'supplier' => $quote['supplier'],
                'supplier_product' => $quote['supplier_product'],
                'quote' => $quote,
                'score' => $this->calculateSupplierScore($quote, $criteria, $order),
            ];
        })->sortByDesc('score')->values();

        if ($scoredSuppliers->isEmpty()) {
            throw new NoSuppliersAvailableException();
        }

        return new SupplierSelectionResult(
            primary: $scoredSuppliers->first(),
            fallbacks: $scoredSuppliers->skip(1)->take(2),
            allQuotes: $scoredSuppliers
        );
    }

    /**
     * Get available suppliers for a variant
     */
    protected function getAvailableSuppliers(ProductVariant $variant): Collection
    {
        // If variant has a specific supplier product, use that
        if ($variant->supplier_product_id) {
            return collect([$variant->supplierProduct])
                ->filter(fn($sp) => $sp && $sp->active && $sp->supplier->enabled);
        }

        // Otherwise, get all supplier products for this variant
        return \Lunar\Models\SupplierProduct::query()
            ->where('product_variant_id', $variant->id)
            ->whereHas('supplier', fn($q) => $q->where('enabled', true))
            ->where('active', true)
            ->with('supplier')
            ->get();
    }

    /**
     * Fetch real-time quotes from multiple suppliers
     */
    protected function fetchRealTimeQuotes(
        Collection $supplierOptions,
        ProductVariant $variant,
        int $quantity,
        ?Address $address
    ): Collection {

        $quotes = collect();

        foreach ($supplierOptions as $supplierProduct) {
            try {
                $driver = $this->suppliers->supplier($supplierProduct->supplier);

                $priceResponse = $driver->getPriceWithShipping(
                    $supplierProduct->external_id,
                    $variant->configuration ?? [],
                    $address?->toArray()
                );

                $quotes->push([
                    'supplier_product' => $supplierProduct,
                    'supplier' => $supplierProduct->supplier,
                    'available' => true,
                    'cost_price' => $priceResponse->costPrice,
                    'sell_price' => $priceResponse->sellPrice,
                    'shipping_options' => $priceResponse->meta['shipping_options'] ?? [],
                    'lead_time' => $priceResponse->breakdown['production_hours'] ?? null,
                    'fetched_at' => now(),
                ]);

            } catch (\Exception $e) {
                \Log::warning('Failed to get quote from supplier', [
                    'supplier_product_id' => $supplierProduct->id,
                    'supplier' => $supplierProduct->supplier->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $quotes;
    }

    /**
     * Calculate supplier score based on weighted criteria
     */
    protected function calculateSupplierScore(
        array $quote,
        array $criteria,
        Order $order
    ): float {

        $weights = $criteria['weights'] ?? [
            'price' => 40,
            'lead_time' => 25,
            'priority' => 20,
            'shipping_cost' => 15,
        ];

        $scores = [];

        // Price score (lower is better, normalize to 0-100)
        $scores['price'] = $this->calculatePriceScore(
            $quote['cost_price'],
            $criteria['price_range'] ?? []
        );

        // Lead time score (faster is better)
        $scores['lead_time'] = $this->calculateLeadTimeScore(
            $quote['lead_time'] ?? 96
        );

        // Priority score (from supplier product configuration)
        $scores['priority'] = ($quote['supplier_product']->priority ?? 50);

        // Shipping cost score
        $scores['shipping_cost'] = $this->calculateShippingScore(
            $quote['shipping_options'] ?? []
        );

        // Calculate weighted total
        $totalScore = 0;
        foreach ($weights as $criterion => $weight) {
            $totalScore += ($scores[$criterion] ?? 0) * ($weight / 100);
        }

        return round($totalScore, 2);
    }

    protected function calculatePriceScore(int $costPrice, array $range = []): float
    {
        // If we have a range, score relative to min/max
        if (isset($range['min']) && isset($range['max'])) {
            $normalized = ($range['max'] - $costPrice) / ($range['max'] - $range['min']);
            return max(0, min(100, $normalized * 100));
        }

        // Otherwise, just use inverse of price (lower is better)
        return max(0, 100 - ($costPrice / 1000));
    }

    protected function calculateLeadTimeScore(int $hours): float
    {
        // 24h = 100, 168h (week) = 50, 336h (2 weeks) = 0
        return max(0, 100 - (($hours - 24) / 312 * 100));
    }

    protected function calculateShippingScore(array $options): float
    {
        if (empty($options)) {
            return 50;
        }

        // Find cheapest shipping option
        $cheapest = collect($options)->min('priceInCents');

        // €0 = 100, €20 = 0
        return max(0, 100 - ($cheapest / 20));
    }
}
