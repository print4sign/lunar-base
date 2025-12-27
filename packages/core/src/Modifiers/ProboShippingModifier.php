<?php

namespace Lunar\Modifiers;

use Closure;
use Lunar\Base\ShippingManifest;
use Lunar\Base\ShippingModifier;
use Lunar\DataTypes\Price;
use Lunar\DataTypes\ShippingOption;
use Lunar\Models\Contracts\Cart;
use Lunar\Models\Currency;
use Lunar\Models\TaxClass;

class ProboShippingModifier extends ShippingModifier
{
    /**
     * Add Probo shipping options to the manifest.
     *
     * This modifier collects shipping options from cart lines that have
     * supplier_shipping_options in their meta (set by GetSupplierPrice pipeline).
     *
     * @return mixed
     */
    public function handle(Cart $cart, Closure $next)
    {
        $manifest = app(ShippingManifest::class);

        // Collect all unique Probo shipping options from cart lines
        $proboShippingOptions = $this->collectProboShippingOptions($cart);

        if ($proboShippingOptions->isEmpty()) {
            return $next($cart);
        }

        // Get default tax class for shipping
        $taxClass = TaxClass::getDefault();
        $currency = $cart->currency ?? Currency::getDefault();

        // Add each Probo shipping option to the manifest
        foreach ($proboShippingOptions as $option) {
            $shippingOption = new ShippingOption(
                name: $option['name'],
                description: $option['description'] ?? null,
                identifier: $option['identifier'],
                price: new Price(
                    $option['price_in_cents'],
                    $currency,
                    1
                ),
                taxClass: $taxClass,
                taxReference: null,
                option: $option['estimated_delivery'] ?? null,
                collect: false,
                meta: [
                    'supplier' => 'probo',
                    'original_currency' => $option['currency'] ?? 'EUR',
                    'delivery_date_from' => $option['delivery_date_from'] ?? null,
                    'delivery_date_to' => $option['delivery_date_to'] ?? null,
                ],
            );

            $manifest->addOption($shippingOption);
        }

        return $next($cart);
    }

    /**
     * Collect unique Probo shipping options from all cart lines.
     */
    protected function collectProboShippingOptions(Cart $cart): \Illuminate\Support\Collection
    {
        $options = collect();

        foreach ($cart->lines as $line) {
            $meta = $line->meta ?? [];

            if (empty($meta['supplier_shipping_options'])) {
                continue;
            }

            foreach ($meta['supplier_shipping_options'] as $option) {
                // Use identifier as unique key
                $identifier = $option['identifier'] ?? null;

                if (! $identifier) {
                    continue;
                }

                // Skip if we already have this option (use the first one found)
                if ($options->has($identifier)) {
                    continue;
                }

                $options->put($identifier, $option);
            }
        }

        return $options->values();
    }

    /**
     * Get the aggregated shipping price for a specific option across all lines.
     *
     * Note: For Probo, shipping is typically calculated for the entire order,
     * not per line. This method exists for potential future use where
     * individual line shipping might be needed.
     */
    protected function getAggregatedShippingPrice(Cart $cart, string $identifier): int
    {
        $totalPrice = 0;

        foreach ($cart->lines as $line) {
            $meta = $line->meta ?? [];
            $shippingOptions = $meta['supplier_shipping_options'] ?? [];

            foreach ($shippingOptions as $option) {
                if (($option['identifier'] ?? null) === $identifier) {
                    $totalPrice += $option['price_in_cents'] ?? 0;
                    break;
                }
            }
        }

        return $totalPrice;
    }
}
