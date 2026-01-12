<?php

namespace Lunar\Pipelines\CartLine;

use Closure;
use Lunar\DataTypes\Price;
use Lunar\Facades\Pricing;
use Lunar\Models\CartLine;
use Lunar\Models\Contracts\CartLine as CartLineContract;

class ApplyDimensionPricing
{
    /**
     * Adjusts cart line pricing based on dimensions stored in meta.
     *
     * This pipeline should run after GetUnitPrice. It re-fetches the price
     * using the calculated size as the quantity for tier pricing, then
     * multiplies the unit price by the calculated size.
     *
     * @param  Closure(CartLineContract): mixed  $next
     * @return Closure
     */
    public function handle(CartLineContract $cartLine, Closure $next)
    {
        /** @var CartLine $cartLine */
        $meta = $cartLine->meta ?? [];

        // Skip if no dimension data in meta
        if (empty($meta['calculated_size'])) {
            return $next($cartLine);
        }

        $purchasable = $cartLine->purchasable;
        $unitCode = $purchasable->getUnitCode();

        // Skip if not a dimensional unit code
        if (! $unitCode?->isDimensional()) {
            return $next($cartLine);
        }

        $calculatedSize = (float) $meta['calculated_size'];

        // Skip if size is 0 or negative
        if ($calculatedSize <= 0) {
            return $next($cartLine);
        }

        $cart = $cartLine->cart;
        $currency = $cart->currency;

        // Get customer groups for pricing
        if ($customer = $cart->customer) {
            $customerGroups = $customer->customerGroups;
        } else {
            $customerGroups = $cart->user?->customers->pluck('customerGroups')->flatten();
        }

        // Calculate effective quantity for tier pricing:
        // Total size = calculatedSize (per item) × quantity (number of items)
        $effectiveQuantity = (int) ceil($calculatedSize * $cartLine->quantity);

        // Re-fetch price using calculated size for tier matching
        $priceResponse = Pricing::currency($currency)
            ->qty($effectiveQuantity)
            ->customerGroups($customerGroups)
            ->for($purchasable)
            ->get();

        // Get the tier-matched unit price (price per 1 unit of measurement)
        $matchedUnitPrice = $priceResponse->matched->price->unitDecimal(false);
        $matchedUnitPriceInclTax = $priceResponse->matched->priceIncTax()->unitDecimal(false);

        // Calculate price for ONE item: unit price × calculated size
        $pricePerItem = (int) round($matchedUnitPrice * $calculatedSize * $currency->factor);

        // Update unit price to reflect the full price for one dimensioned item
        $cartLine->unitPrice = new Price(
            $pricePerItem,
            $currency,
            1, // Now represents 1 configured item
            $unitCode
        );

        // Also update the tax-inclusive price
        $pricePerItemInclTax = (int) round($matchedUnitPriceInclTax * $calculatedSize * $currency->factor);

        $cartLine->unitPriceInclTax = new Price(
            $pricePerItemInclTax,
            $currency,
            1,
            $unitCode
        );

        return $next($cartLine);
    }
}
