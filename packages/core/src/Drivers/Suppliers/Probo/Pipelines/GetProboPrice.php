<?php

namespace Lunar\Drivers\Suppliers\Probo\Pipelines;

use Closure;
use Lunar\DataTypes\Price;
use Lunar\Facades\Suppliers;
use Lunar\Models\Contracts\CartLine as CartLineContract;
use Lunar\Models\ProductVariant;
use Spatie\LaravelBlink\BlinkFacade as Blink;

class GetProboPrice
{
    /**
     * Get pricing from Probo supplier for configured products.
     *
     * This pipeline handles two scenarios:
     * 1. Pre-configured variants: Uses configuration stored on the variant
     * 2. Dynamic variants: Uses configuration from cart line meta (customer choices)
     *
     * @param  Closure(CartLineContract): mixed  $next
     * @return Closure
     */
    public function handle(CartLineContract $cartLine, Closure $next)
    {
        $purchasable = $cartLine->purchasable;

        // Only handle ProductVariant purchasables
        if (! $purchasable instanceof ProductVariant) {
            return $next($cartLine);
        }

        // Check if this variant has a supplier product
        $supplierProduct = $purchasable->supplierProduct;

        if (! $supplierProduct) {
            return $next($cartLine);
        }

        // Only handle Probo supplier products
        $supplier = $supplierProduct->supplier;
        if ($supplier->driver !== 'probo') {
            return $next($cartLine);
        }

        // Check if the supplier supports pricing
        if (! $supplier->supports('pricing')) {
            return $next($cartLine);
        }

        $meta = $cartLine->meta ?? [];
        $isDynamic = $purchasable->isDynamic() && ($meta['is_dynamic'] ?? false);

        // For dynamic variants, get configuration from cart line meta
        // For pre-configured variants, get configuration from variant
        $configuration = $isDynamic
            ? ($meta['supplier_configuration'] ?? [])
            : $purchasable->configuration;

        // Skip if no configuration available
        if (empty($configuration)) {
            return $next($cartLine);
        }

        $cart = $cartLine->cart;
        $configuration['quantity'] = $cartLine->quantity;

        // Get shipping address for shipping calculation
        $address = null;
        if ($cart->shippingAddress) {
            $address = [
                'country' => $cart->shippingAddress->country?->iso2,
                'postcode' => $cart->shippingAddress->postcode,
                'city' => $cart->shippingAddress->city,
            ];
        }

        // Create cache key for this specific configuration
        $cacheKey = $this->getCacheKey($supplierProduct->id, $configuration, $address);

        try {
            $priceResponse = Blink::once($cacheKey, function () use ($supplier, $supplierProduct, $configuration, $address) {
                // Use api_code from external_data if available, otherwise fall back to external_id
                $apiCode = $supplierProduct->external_data['api_code'] ?? $supplierProduct->external_id;

                return Suppliers::supplier($supplier)
                    ->getPriceWithShipping(
                        $apiCode,
                        $configuration,
                        $address
                    );
            });

            $currency = Blink::once('currency_'.$cart->currency_id, function () use ($cart) {
                return $cart->currency;
            });

            // Get sell price - apply margin if set, otherwise use supplier's sell price
            $sellPrice = $priceResponse->sellPrice;

            // For dynamic variants with a margin set, calculate from cost
            if ($isDynamic && $purchasable->margin) {
                $sellPrice = (int) round($priceResponse->costPrice * (1 + ($purchasable->margin / 100)));
            }

            if ($priceResponse->currency !== $currency->code) {
                // Convert from supplier currency to cart currency
                $sellPrice = $this->convertCurrency(
                    $sellPrice,
                    $priceResponse->currency,
                    $currency->code
                );
            }

            // Probo returns the TOTAL price for the quantity, not the unit price.
            // We need to divide by quantity to get the actual unit price,
            // since the cart will multiply by quantity when calculating line totals.
            $quantity = $cartLine->quantity;
            $unitPrice = $quantity > 0 ? (int) round($sellPrice / $quantity) : $sellPrice;

            // Set the unit price from supplier
            $cartLine->unitPrice = new Price(
                $unitPrice,
                $currency,
                $purchasable->getUnitQuantity(),
                $purchasable->getUnitCode()
            );

            // Estimate tax-inclusive price (actual tax calculation happens later)
            $cartLine->unitPriceInclTax = new Price(
                $unitPrice,
                $currency,
                $purchasable->getUnitQuantity(),
                $purchasable->getUnitCode()
            );

            // Store supplier pricing metadata on the cart line
            $meta = $cartLine->meta ?? [];
            $meta['supplier_pricing'] = [
                'supplier_id' => $supplier->id,
                'supplier_product_id' => $supplierProduct->id,
                'cost_price' => $priceResponse->costPrice,
                'sell_price' => $priceResponse->sellPrice,
                'unit_price' => $unitPrice,
                'total_price' => $sellPrice,
                'quantity' => $quantity,
                'currency' => $priceResponse->currency,
                'breakdown' => $priceResponse->breakdown,
            ];

            // Store shipping options if available
            if (! empty($priceResponse->meta['shipping_options'])) {
                $meta['supplier_shipping_options'] = $priceResponse->meta['shipping_options'];
            }

            $cartLine->meta = $meta;

        } catch (\Exception $e) {
            // Log the error but continue with regular pricing
            logger()->warning('Failed to get Probo supplier price', [
                'supplier_product_id' => $supplierProduct->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $next($cartLine);
    }

    /**
     * Generate a cache key for the price request.
     */
    protected function getCacheKey(int $supplierProductId, array $configuration, ?array $address): string
    {
        $data = [
            'supplier_product_id' => $supplierProductId,
            'configuration' => $configuration,
            'address' => $address,
        ];

        return 'probo_price_'.md5(json_encode($data));
    }

    /**
     * Convert price from one currency to another.
     */
    protected function convertCurrency(int $amount, string $fromCurrency, string $toCurrency): int
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Get currency models and convert using exchange rates
        $from = \Lunar\Models\Currency::where('code', $fromCurrency)->first();
        $to = \Lunar\Models\Currency::where('code', $toCurrency)->first();

        if (! $from || ! $to) {
            return $amount;
        }

        // Convert to base currency, then to target currency
        $baseAmount = $amount / $from->exchange_rate;

        return (int) round($baseAmount * $to->exchange_rate);
    }
}
