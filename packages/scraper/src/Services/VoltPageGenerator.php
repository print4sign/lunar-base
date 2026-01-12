<?php

namespace Lunar\Scraper\Services;

use Illuminate\Support\Str;
use Lunar\Models\SupplierProduct;

class VoltPageGenerator
{
    protected string $outputPath;

    public function __construct()
    {
        // Output to Folio pages directory for file-based routing
        $this->outputPath = config('lunar.scraper.folio_pages_path')
            ?? resource_path('views/pages/products');
    }

    /**
     * Generate a Folio page file using the product-page-template component.
     */
    public function generate(SupplierProduct $supplierProduct, string $rewrittenHtml): string
    {
        // Generate filename from URL path
        $externalData = $supplierProduct->external_data ?? [];
        $url = $externalData['url'] ?? '';

        if ($url) {
            $path = parse_url($url, PHP_URL_PATH);
            $filename = Str::slug(trim($path, '/')).'.blade.php';
        } else {
            // Fallback to product name
            $filename = Str::slug($supplierProduct->external_name).'.blade.php';
        }
        $filepath = $this->outputPath.'/'.$filename;

        // Ensure directory exists
        if (! is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }

        // Generate the Folio page content using template component
        $pageContent = $this->buildFolioContent($supplierProduct, $rewrittenHtml);

        // Write the file
        file_put_contents($filepath, $pageContent);

        return $filepath;
    }

    /**
     * Build the Blade template content using the product-page-template component.
     *
     * @param  string  $rewrittenHtml  Kept for backwards compatibility, no longer used
     */
    protected function buildFolioContent(SupplierProduct $supplierProduct, string $rewrittenHtml = ''): string
    {
        $supplierProductId = $supplierProduct->id;
        $productId = $supplierProduct->product_id ?? 'null';
        $externalData = $supplierProduct->external_data ?? [];

        // Extract product data from external_data
        $productName = $this->escapePhpString($supplierProduct->external_name ?? 'Product');
        $shortDescription = $this->escapePhpString($externalData['short_description'] ?? '');
        $description = $this->escapePhpString($externalData['description'] ?? '');

        // Build images array
        $images = $externalData['images'] ?? [];
        $imagesPhp = $this->buildImagesArray($images, $productName);

        // Build specifications array from attributes
        $attributes = $externalData['attributes'] ?? [];
        $specificationsPhp = $this->buildSpecificationsArray($attributes);

        // Build features array
        $features = $externalData['features'] ?? [];
        $featuresPhp = $this->buildFeaturesArray($features);

        // Warranty text
        $warranty = $this->escapePhpString($externalData['warranty'] ?? '');

        return <<<BLADE
@extends('layouts.product-page')

@php
    \$supplierProduct = \\Lunar\\Models\\SupplierProduct::find({$supplierProductId});
    \$product = {$productId} ? \\Lunar\\Models\\Product::find({$productId}) : null;
    \$externalData = \$supplierProduct?->external_data ?? [];

    // Product data from scraped content
    \$productName = '{$productName}';
    \$shortDescription = '{$shortDescription}';
    \$description = '{$description}';

    // Images
    \$images = {$imagesPhp};

    // Specifications
    \$specifications = {$specificationsPhp};

    // Features
    \$features = {$featuresPhp};

    // Warranty
    \$warranty = '{$warranty}';
@endphp

@section('content')
<x-product-page-template
    :product="\$product"
    :supplierProduct="\$supplierProduct"
    :images="\$images"
    :name="\$productName"
    :shortDescription="\$shortDescription"
    :description="\$description"
    :specifications="\$specifications"
    :features="\$features"
    :warranty="\$warranty"
/>

@if(\$product)
{{-- Admin Info Bar --}}
<div class="bg-gray-100 border-t border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="mx-auto max-w-screen-xl px-4 py-3 flex flex-wrap gap-4 text-sm">
        <span class="text-gray-600 dark:text-gray-400">Product ID: <strong class="text-gray-800 dark:text-white">{{ \$product->id }}</strong></span>
        <span class="text-gray-600 dark:text-gray-400">SKU: <strong class="font-mono text-gray-800 dark:text-white">{{ \$product->variants->first()?->sku }}</strong></span>
        <span class="text-gray-600 dark:text-gray-400">Status:
            <span class="px-2 py-0.5 rounded text-xs {{ \$product->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' }}">
                {{ \$product->status }}
            </span>
        </span>
        @if(\$supplierProduct)
            <span class="text-gray-600 dark:text-gray-400">External ID: <strong class="font-mono text-gray-800 dark:text-white">{{ \$supplierProduct->external_id }}</strong></span>
        @endif
    </div>
</div>
@endif
@endsection
BLADE;
    }

    /**
     * Build PHP array string for images.
     */
    protected function buildImagesArray(array $images, string $productName): string
    {
        if (empty($images)) {
            return '[]';
        }

        $items = [];
        foreach ($images as $index => $url) {
            $escapedUrl = $this->escapePhpString($url);
            $alt = $this->escapePhpString($productName.' - Image '.($index + 1));
            $items[] = "['url' => '{$escapedUrl}', 'alt' => '{$alt}']";
        }

        return "[\n        ".implode(",\n        ", $items)."\n    ]";
    }

    /**
     * Build PHP array string for specifications from attributes.
     */
    protected function buildSpecificationsArray(array $attributes): string
    {
        if (empty($attributes)) {
            return '[]';
        }

        $items = [];
        foreach ($attributes as $attr) {
            $name = $this->escapePhpString($attr['name'] ?? '');
            $value = $this->escapePhpString($attr['value'] ?? '');
            if ($name && $value) {
                $items[] = "['name' => '{$name}', 'value' => '{$value}']";
            }
        }

        if (empty($items)) {
            return '[]';
        }

        return "[\n        ".implode(",\n        ", $items)."\n    ]";
    }

    /**
     * Build PHP array string for features.
     */
    protected function buildFeaturesArray(array $features): string
    {
        if (empty($features)) {
            return '[]';
        }

        $items = [];
        foreach ($features as $feature) {
            // Handle both string features and array with 'text' key
            $text = is_array($feature) ? ($feature['text'] ?? $feature['name'] ?? '') : $feature;
            $escapedText = $this->escapePhpString($text);
            if ($escapedText) {
                $items[] = "'{$escapedText}'";
            }
        }

        if (empty($items)) {
            return '[]';
        }

        return "[\n        ".implode(",\n        ", $items)."\n    ]";
    }

    /**
     * Escape a string for use in PHP single-quoted string.
     */
    protected function escapePhpString(string $value): string
    {
        // Remove newlines and excessive whitespace
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        // Escape single quotes and backslashes
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
    }
}
