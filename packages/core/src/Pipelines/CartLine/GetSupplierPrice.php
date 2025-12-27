<?php

namespace Lunar\Pipelines\CartLine;

use Closure;
use Lunar\DataTypes\Price;
use Lunar\Facades\Suppliers;
use Lunar\Models\Contracts\CartLine as CartLineContract;
use Lunar\Models\ProductVariant;
use Spatie\LaravelBlink\BlinkFacade as Blink;

class GetSupplierPrice
{
    /**
     * Get pricing from supplier for configured products.
     *
     * This pipeline runs BEFORE GetUnitPrice and will override the unit price
     * for products that are linked to a supplier with configuration.
     *
     * @param  Closure(CartLineContract): mixed  $next
     * @return Closure
     */
    public function handle(CartLineContract $cartLine, Closure $next)
    {
        $purchasable = $cartLine->purchasable;

        // Only handle ProductVariant purchasables with supplier configuration
        if (! $purchasable instanceof ProductVariant) {
            return $next($cartLine);
        }

        // Check if this variant has a supplier product with configuration
        $supplierProduct = $purchasable->supplierProduct;

        if (! $supplierProduct || empty($purchasable->configuration)) {
            return $next($cartLine);
        }

        $supplier = $supplierProduct->supplier;

        // Check if the supplier supports pricing
        if (! $supplier->supports('pricing')) {
            return $next($cartLine);
        }

        $cart = $cartLine->cart;
        $configuration = $purchasable->configuration;
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
                return Suppliers::supplier($supplier)
                    ->getPriceWithShipping(
                        $supplierProduct->external_id,
                        $configuration,
                        $address
                    );
            });

            $currency = Blink::once('currency_'.$cart->currency_id, function () use ($cart) {
                return $cart->currency;
            });

            // Convert supplier price to cart currency if needed
            $sellPrice = $priceResponse->sellPrice;

            if ($priceResponse->currency !== $currency->code) {
                // Convert from supplier currency to cart currency
                $sellPrice = $this->convertCurrency(
                    $sellPrice,
                    $priceResponse->currency,
                    $currency->code
                );
            }

            // Set the unit price from supplier
            $cartLine->unitPrice = new Price(
                $sellPrice,
                $currency,
                $purchasable->getUnitQuantity()
            );

            // Estimate tax-inclusive price (actual tax calculation happens later)
            $cartLine->unitPriceInclTax = new Price(
                $sellPrice,
                $currency,
                $purchasable->getUnitQuantity()
            );

            // Store supplier pricing metadata on the cart line
            $meta = $cartLine->meta ?? [];
            $meta['supplier_pricing'] = [
                'supplier_id' => $supplier->id,
                'supplier_product_id' => $supplierProduct->id,
                'cost_price' => $priceResponse->costPrice,
                'sell_price' => $priceResponse->sellPrice,
                'currency' => $priceResponse->currency,
                'breakdown' => $priceResponse->breakdown,
            ];

            // Store shipping options if available
            if (! empty($priceResponse->meta['shipping_options'])) {
                $meta['supplier_shipping_options'] = $priceResponse->meta['shipping_options'];
            }

            $cartLine->meta = $meta;

            // Skip the regular GetUnitPrice pipeline for this line
            // by marking it as supplier-priced
            $cartLine->setAttribute('supplier_priced', true);

        } catch (\Exception $e) {
            // Log the error but continue with regular pricing
            logger()->warning('Failed to get supplier price', [
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

        return 'supplier_price_'.md5(json_encode($data));
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
