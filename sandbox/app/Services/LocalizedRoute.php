<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Lunar\Models\Collection;
use Lunar\Models\Language;
use Lunar\Models\Product;

class LocalizedRoute
{
    /**
     * Get the current locale.
     */
    public function currentLocale(): string
    {
        return app()->getLocale();
    }

    /**
     * Get supported locales.
     */
    public function supportedLocales(): array
    {
        return config('localization.supported_locales', ['nl', 'en', 'de']);
    }

    /**
     * Get locale display names.
     */
    public function localeNames(): array
    {
        return config('localization.locale_names', []);
    }

    /**
     * Get the translated route segment for a given key and locale.
     */
    public function segment(string $key, ?string $locale = null): string
    {
        $locale = $locale ?? $this->currentLocale();

        return config("localization.route_segments.{$locale}.{$key}", $key);
    }

    /**
     * Generate a localized route URL.
     */
    public function to(string $routeName, array $params = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->currentLocale();

        // Handle legacy route names for products and collections
        // These now use direct URLs without segment prefixes
        if ($routeName === 'product.view' && isset($params['slug'])) {
            return "/{$locale}/{$params['slug']}";
        }

        if ($routeName === 'collection.view' && isset($params['slug'])) {
            return "/{$locale}/{$params['slug']}";
        }

        // Add locale to params
        $params['locale'] = $locale;

        // Add translated segment based on route name
        $segmentMap = [
            'products.index' => ['key' => 'all-products', 'param' => 'allProductsSegment'],
            'collections.index' => ['key' => 'collections', 'param' => 'collectionsSegment'],
            'search.view' => ['key' => 'search', 'param' => 'searchSegment'],
            'checkout.view' => ['key' => 'checkout', 'param' => 'checkoutSegment'],
            'checkout-success.view' => ['key' => 'checkout', 'param' => 'checkoutSegment'],
            'contact.view' => ['key' => 'contact', 'param' => 'contactSegment'],
            'articles.index' => ['key' => 'articles', 'param' => 'articlesSegment'],
            'article.view' => ['key' => 'articles', 'param' => 'articlesSegment'],
            'static.delivery-specs' => ['key' => 'delivery-specs', 'param' => 'deliverySpecsSegment'],
            'login' => ['key' => 'login', 'param' => 'loginSegment'],
            'register' => ['key' => 'register', 'param' => 'registerSegment'],
            'password.request' => ['key' => 'forgot-password', 'param' => 'forgotPasswordSegment'],
            'password.reset' => ['key' => 'reset-password', 'param' => 'resetPasswordSegment'],
            'dashboard.index' => ['key' => 'dashboard', 'param' => 'dashboardSegment'],
            'dashboard.orders' => ['key' => 'dashboard', 'param' => 'dashboardSegment', 'extra' => ['ordersSegment' => 'orders']],
            'dashboard.orders.show' => ['key' => 'dashboard', 'param' => 'dashboardSegment', 'extra' => ['ordersSegment' => 'orders']],
            'dashboard.addresses' => ['key' => 'dashboard', 'param' => 'dashboardSegment', 'extra' => ['addressesSegment' => 'addresses']],
            'dashboard.saved-carts' => ['key' => 'dashboard', 'param' => 'dashboardSegment', 'extra' => ['savedCartsSegment' => 'saved-carts']],
            'dashboard.invoices' => ['key' => 'dashboard', 'param' => 'dashboardSegment', 'extra' => ['invoicesSegment' => 'invoices']],
        ];

        if (isset($segmentMap[$routeName])) {
            $mapping = $segmentMap[$routeName];
            $segmentKey = $mapping['key'];
            $segmentParam = $mapping['param'];

            if (! isset($params[$segmentParam])) {
                $params[$segmentParam] = $this->segment($segmentKey, $locale);
            }

            // Handle extra segments (for nested routes like dashboard/orders)
            if (isset($mapping['extra'])) {
                foreach ($mapping['extra'] as $extraParam => $extraKey) {
                    if (! isset($params[$extraParam])) {
                        $params[$extraParam] = $this->segment($extraKey, $locale);
                    }
                }
            }
        }

        return route($routeName, $params);
    }

    /**
     * Generate a localized URL for a product.
     */
    public function product(Product $product, ?string $locale = null): string
    {
        $locale = $locale ?? $this->currentLocale();

        // Try to get locale-specific URL
        $language = Language::where('code', $locale)->first();
        $url = null;

        if ($language) {
            $url = $product->urls()
                ->where('language_id', $language->id)
                ->first();
        }

        // Fallback to default URL
        if (! $url) {
            $url = $product->defaultUrl;
        }

        $slug = $url?->slug ?? $product->id;

        // Direct URL without segment
        return "/{$locale}/{$slug}";
    }

    /**
     * Generate a localized URL for a collection.
     */
    public function collection(Collection $collection, ?string $locale = null): string
    {
        $locale = $locale ?? $this->currentLocale();

        // Try to get locale-specific URL
        $language = Language::where('code', $locale)->first();
        $url = null;

        if ($language) {
            $url = $collection->urls()
                ->where('language_id', $language->id)
                ->first();
        }

        // Fallback to default URL
        if (! $url) {
            $url = $collection->defaultUrl;
        }

        $slug = $url?->slug ?? $collection->id;

        // Direct URL without segment
        return "/{$locale}/{$slug}";
    }

    /**
     * Switch to a different locale while preserving the current page.
     */
    public function switchTo(string $newLocale): string
    {
        $currentPath = request()->path();
        $currentLocale = $this->currentLocale();

        // Get route parameters
        $route = Route::current();

        if (! $route) {
            return "/{$newLocale}";
        }

        $routeName = $route->getName();

        // If on a product or collection page via slug resolver
        if ($routeName === 'slug.resolve') {
            $slug = $route->parameter('slug');

            // Try product first
            $product = $this->findProductBySlug($slug);
            if ($product) {
                return $this->product($product, $newLocale);
            }

            // Then try collection
            $collection = $this->findCollectionBySlug($slug);
            if ($collection) {
                return $this->collection($collection, $newLocale);
            }
        }

        // Legacy route names (kept for backwards compatibility)
        if ($routeName === 'product.view') {
            $slug = $route->parameter('slug');
            $product = $this->findProductBySlug($slug);

            if ($product) {
                return $this->product($product, $newLocale);
            }
        }

        // If on a collection page, get the collection and its localized URL
        if ($routeName === 'collection.view') {
            $slug = $route->parameter('slug');
            $collection = $this->findCollectionBySlug($slug);

            if ($collection) {
                return $this->collection($collection, $newLocale);
            }
        }

        // For other pages, generate the route with new locale
        if ($routeName) {
            $params = $route->parameters();
            unset($params['locale']);

            // Remove segment parameters as they'll be regenerated
            unset(
                $params['productsSegment'],
                $params['collectionsSegment'],
                $params['searchSegment'],
                $params['checkoutSegment'],
                $params['articlesSegment']
            );

            return $this->to($routeName, $params, $newLocale);
        }

        // Fallback: just swap the locale prefix
        return preg_replace(
            "/^\/{$currentLocale}/",
            "/{$newLocale}",
            "/{$currentPath}"
        ) ?: "/{$newLocale}";
    }

    /**
     * Find a product by its URL slug for the current locale.
     */
    protected function findProductBySlug(string $slug): ?Product
    {
        $language = Language::where('code', $this->currentLocale())->first();

        $query = \Lunar\Models\Url::where('slug', $slug)
            ->where('element_type', (new Product)->getMorphClass());

        if ($language) {
            $query->where('language_id', $language->id);
        }

        $url = $query->first();

        return $url?->element;
    }

    /**
     * Find a collection by its URL slug for the current locale.
     */
    protected function findCollectionBySlug(string $slug): ?Collection
    {
        $language = Language::where('code', $this->currentLocale())->first();

        $query = \Lunar\Models\Url::where('slug', $slug)
            ->where('element_type', (new Collection)->getMorphClass());

        if ($language) {
            $query->where('language_id', $language->id);
        }

        $url = $query->first();

        return $url?->element;
    }

    /**
     * Get all alternate URLs for hreflang tags.
     */
    public function alternateUrls(): array
    {
        $urls = [];

        foreach ($this->supportedLocales() as $locale) {
            $urls[$locale] = url($this->switchTo($locale));
        }

        return $urls;
    }
}
