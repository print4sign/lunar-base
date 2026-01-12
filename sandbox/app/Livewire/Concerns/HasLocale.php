<?php

namespace App\Livewire\Concerns;

use App\Services\LocalizedRoute;
use Livewire\Attributes\Locked;
use Lunar\Models\Collection;
use Lunar\Models\Product;

trait HasLocale
{
    #[Locked]
    public string $locale = 'nl';

    /**
     * Initialize the locale from route parameter.
     */
    public function initializeLocale(string $locale = 'nl'): void
    {
        $this->locale = $locale;
        $this->ensureLocale();
    }

    /**
     * Ensure the locale is set before rendering.
     * This is called on every render to ensure translations work correctly.
     */
    public function hydrate(): void
    {
        $this->ensureLocale();
    }

    /**
     * Ensure the application locale is set correctly.
     */
    protected function ensureLocale(): void
    {
        if ($this->locale !== app()->getLocale()) {
            app()->setLocale($this->locale);

            // Also sync with Lunar's Language model in session
            $language = \Lunar\Models\Language::where('code', $this->locale)->first();
            if ($language) {
                session(['lunar_language' => $language->code]);
            }
        }
    }

    /**
     * Get a localized URL for a product.
     */
    protected function productUrl(Product $product): string
    {
        return app(LocalizedRoute::class)->product($product, $this->locale);
    }

    /**
     * Get a localized URL for a collection.
     */
    protected function collectionUrl(Collection $collection): string
    {
        return app(LocalizedRoute::class)->collection($collection, $this->locale);
    }

    /**
     * Get a localized URL for a named route.
     */
    protected function localizedUrl(string $routeName, array $params = []): string
    {
        return app(LocalizedRoute::class)->to($routeName, $params, $this->locale);
    }
}
