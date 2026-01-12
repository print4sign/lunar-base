<?php

namespace Lunar\Drivers\Suppliers\PrintCom\Pipelines;

use Closure;
use Lunar\Managers\SupplierManager;
use Lunar\Models\CartLine;

class GetPrintComPrice
{
    public function __construct(
        protected SupplierManager $suppliers
    ) {}

    public function handle(CartLine $cartLine, Closure $next): CartLine
    {
        $variant = $cartLine->purchasable;

        if (!$variant->supplierProduct ||
            $variant->supplierProduct->supplier->driver !== 'printcom') {
            return $next($cartLine);
        }

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
            \Log::error('Failed to get print.com price', [
                'cart_line_id' => $cartLine->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($cartLine);
    }
}
