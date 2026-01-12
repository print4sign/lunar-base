<?php

namespace App\Services;

use Lunar\Models\Collection;
use Lunar\Models\Product;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapGenerator
{
    public function __construct(
        protected LocalizedRoute $localizedRoute
    ) {}

    /**
     * Generate the sitemap with all localized URLs.
     */
    public function generate(): Sitemap
    {
        $sitemap = Sitemap::create();

        $this->addStaticPages($sitemap);
        $this->addProducts($sitemap);
        $this->addCollections($sitemap);

        return $sitemap;
    }

    /**
     * Add static pages (home, products index, collections index) for all locales.
     */
    protected function addStaticPages(Sitemap $sitemap): void
    {
        $routes = ['home', 'products.index', 'collections.index'];
        $locales = config('localization.supported_locales');

        foreach ($routes as $routeName) {
            foreach ($locales as $locale) {
                $url = Url::create($this->localizedRoute->to($routeName, [], $locale))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority($routeName === 'home' ? 1.0 : 0.8);

                // Add hreflang alternates for all supported locales
                foreach ($locales as $altLocale) {
                    $url->addAlternate(
                        $this->localizedRoute->to($routeName, [], $altLocale),
                        $altLocale
                    );
                }

                $sitemap->add($url);
            }
        }
    }

    /**
     * Add all published products with their localized URLs.
     */
    protected function addProducts(Sitemap $sitemap): void
    {
        $locales = config('localization.supported_locales');

        Product::with('urls.language')
            ->where('status', 'published')
            ->chunk(100, function ($products) use ($sitemap, $locales) {
                foreach ($products as $product) {
                    foreach ($locales as $locale) {
                        $url = Url::create($this->localizedRoute->product($product, $locale))
                            ->setLastModificationDate($product->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                            ->setPriority(0.9);

                        // Add hreflang alternates for all supported locales
                        foreach ($locales as $altLocale) {
                            $url->addAlternate(
                                $this->localizedRoute->product($product, $altLocale),
                                $altLocale
                            );
                        }

                        $sitemap->add($url);
                    }
                }
            });
    }

    /**
     * Add all collections with their localized URLs.
     */
    protected function addCollections(Sitemap $sitemap): void
    {
        $locales = config('localization.supported_locales');

        Collection::with('urls.language')
            ->chunk(100, function ($collections) use ($sitemap, $locales) {
                foreach ($collections as $collection) {
                    foreach ($locales as $locale) {
                        $url = Url::create($this->localizedRoute->collection($collection, $locale))
                            ->setLastModificationDate($collection->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.8);

                        // Add hreflang alternates for all supported locales
                        foreach ($locales as $altLocale) {
                            $url->addAlternate(
                                $this->localizedRoute->collection($collection, $altLocale),
                                $altLocale
                            );
                        }

                        $sitemap->add($url);
                    }
                }
            });
    }

    /**
     * Write the sitemap to a file.
     */
    public function writeToFile(string $path = null): void
    {
        $path = $path ?? public_path('sitemap.xml');
        $this->generate()->writeToFile($path);
    }
}
