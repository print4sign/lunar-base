<?php

use App\Services\LocalizedRoute;

if (! function_exists('localizedRoute')) {
    /**
     * Get the LocalizedRoute service instance.
     */
    function localizedRoute(): LocalizedRoute
    {
        return app(LocalizedRoute::class);
    }
}

if (! function_exists('localeSegment')) {
    /**
     * Get a translated route segment.
     */
    function localeSegment(string $key, ?string $locale = null): string
    {
        return localizedRoute()->segment($key, $locale);
    }
}

if (! function_exists('localizedUrl')) {
    /**
     * Generate a localized URL for a named route.
     */
    function localizedUrl(string $routeName, array $params = [], ?string $locale = null): string
    {
        return localizedRoute()->to($routeName, $params, $locale);
    }
}

if (! function_exists('switchLocale')) {
    /**
     * Get the URL to switch to a different locale.
     */
    function switchLocale(string $locale): string
    {
        return localizedRoute()->switchTo($locale);
    }
}

if (! function_exists('currentLocale')) {
    /**
     * Get the current locale.
     */
    function currentLocale(): string
    {
        return localizedRoute()->currentLocale();
    }
}

if (! function_exists('supportedLocales')) {
    /**
     * Get all supported locales.
     */
    function supportedLocales(): array
    {
        return localizedRoute()->supportedLocales();
    }
}

if (! function_exists('localeNames')) {
    /**
     * Get locale display names.
     */
    function localeNames(): array
    {
        return localizedRoute()->localeNames();
    }
}
