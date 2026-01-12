<?php

namespace Lunar\Drivers\Suppliers\HelloPrint\Pipelines;

use Closure;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Managers\SupplierManager;
use Lunar\Models\CartLine;

class GetHelloPrintPrice
{
    public function __construct(
        protected SupplierManager $suppliers
    ) {}

    public function handle(CartLine $cartLine, Closure $next): CartLine
    {
        $variant = $cartLine->purchasable;

        // Only run if variant has HelloPrint supplier product
        if (!$variant->supplierProduct ||
            $variant->supplierProduct->supplier->driver !== 'helloprint') {
            return $next($cartLine);
        }

        // Only run for dynamic variants
        if (!$variant->is_dynamic) {
            return $next($cartLine);
        }

        try {
            $driver = $this->suppliers->supplier($variant->supplierProduct->supplier);

            $priceResponse = $driver->getDynamicPrice(
                $variant->supplierProduct->external_id,
                $variant->configuration ?? [],
                $cartLine->quantity
            );

            // Update cart line with HelloPrint price
            $cartLine->unit_price = $priceResponse->sellPrice;

            $meta = $cartLine->meta ?? [];
            $meta['supplier_pricing'] = [
                'cost_price' => $priceResponse->costPrice,
                'sell_price' => $priceResponse->sellPrice,
                'currency' => $priceResponse->currency,
                'fetched_at' => now()->toIso8601String(),
            ];
            $cartLine->meta = $meta;

        } catch (\Exception $e) {
            \Log::error('Failed to get HelloPrint price', [
                'cart_line_id' => $cartLine->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($cartLine);
    }
}
