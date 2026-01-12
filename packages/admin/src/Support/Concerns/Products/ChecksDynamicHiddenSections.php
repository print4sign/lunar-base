<?php

namespace Lunar\Admin\Support\Concerns\Products;

use Lunar\Facades\Suppliers;
use Lunar\Models\ProductVariant;

trait ChecksDynamicHiddenSections
{
    /**
     * Check if a section should be hidden for a dynamic variant.
     *
     * @param  array  $parameters  The page parameters containing the record
     * @param  string  $section  The section constant to check
     */
    protected static function shouldHideSectionForDynamic(array $parameters, string $section): bool
    {
        $record = $parameters['record'] ?? null;

        if (! $record) {
            return false;
        }

        // Get the first variant (for single-variant products)
        $variant = $record->variants()->withTrashed()->first();

        if (! $variant) {
            return false;
        }

        return static::isVariantDynamicWithHiddenSection($variant, $section);
    }

    /**
     * Check if a variant is dynamic and has the given section hidden.
     */
    protected static function isVariantDynamicWithHiddenSection(ProductVariant $variant, string $section): bool
    {
        // Check if variant is dynamic
        if (! $variant->isDynamic()) {
            return false;
        }

        // Get the supplier
        $supplier = $variant->supplierProduct?->supplier;

        if (! $supplier) {
            return false;
        }

        // Check if the driver hides this section for dynamic products
        $hiddenSections = Suppliers::supplier($supplier)->getHiddenSectionsForDynamic();

        return in_array($section, $hiddenSections);
    }

    /**
     * Get all hidden sections for a dynamic variant.
     */
    protected static function getHiddenSectionsForDynamicVariant(ProductVariant $variant): array
    {
        if (! $variant->isDynamic()) {
            return [];
        }

        $supplier = $variant->supplierProduct?->supplier;

        if (! $supplier) {
            return [];
        }

        return Suppliers::supplier($supplier)->getHiddenSectionsForDynamic();
    }
}
