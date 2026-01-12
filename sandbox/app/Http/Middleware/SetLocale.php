<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Lunar\Models\Language;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);

        // Set Laravel's locale
        app()->setLocale($locale);

        // Store in session for persistence
        session([config('localization.session_key') => $locale]);

        // Sync with Lunar's Language model
        $language = Language::where('code', $locale)->first();
        if ($language) {
            session(['lunar_language' => $language->code]);
        }

        // Set cookie for long-term preference
        $cookieConfig = config('localization.cookie');
        Cookie::queue(
            $cookieConfig['name'],
            $locale,
            $cookieConfig['lifetime']
        );

        return $next($request);
    }

    /**
     * Detect the locale based on the configured detection order.
     */
    protected function detectLocale(Request $request): string
    {
        $supportedLocales = config('localization.supported_locales', ['nl', 'en', 'de']);
        $defaultLocale = config('localization.default_locale', 'nl');
        $detectionOrder = config('localization.detection_order', ['url', 'session', 'cookie', 'browser']);

        foreach ($detectionOrder as $method) {
            $locale = match ($method) {
                'url' => $this->getLocaleFromUrl($request),
                'session' => $this->getLocaleFromSession(),
                'cookie' => $this->getLocaleFromCookie($request),
                'browser' => $this->getLocaleFromBrowser($request),
                default => null,
            };

            if ($locale && in_array($locale, $supportedLocales)) {
                return $locale;
            }
        }

        return $defaultLocale;
    }

    /**
     * Get locale from URL prefix (e.g., /nl/products).
     */
    protected function getLocaleFromUrl(Request $request): ?string
    {
        $locale = $request->route('locale');

        if ($locale && in_array($locale, config('localization.supported_locales', []))) {
            return $locale;
        }

        return null;
    }

    /**
     * Get locale from session.
     */
    protected function getLocaleFromSession(): ?string
    {
        return session(config('localization.session_key'));
    }

    /**
     * Get locale from cookie.
     */
    protected function getLocaleFromCookie(Request $request): ?string
    {
        return $request->cookie(config('localization.cookie.name'));
    }

    /**
     * Get locale from browser's Accept-Language header.
     */
    protected function getLocaleFromBrowser(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (! $acceptLanguage) {
            return null;
        }

        $browserLocaleMapping = config('localization.browser_locale_mapping', []);
        $supportedLocales = config('localization.supported_locales', []);

        // Parse Accept-Language header
        $languages = $this->parseAcceptLanguage($acceptLanguage);

        foreach ($languages as $lang) {
            // Check direct mapping
            if (isset($browserLocaleMapping[$lang])) {
                return $browserLocaleMapping[$lang];
            }

            // Check if base language (e.g., 'en' from 'en-US') is supported
            $baseLang = explode('-', $lang)[0];
            if (in_array($baseLang, $supportedLocales)) {
                return $baseLang;
            }
        }

        return null;
    }

    /**
     * Parse Accept-Language header and return sorted list of languages.
     */
    protected function parseAcceptLanguage(string $header): array
    {
        $languages = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);

            if (str_contains($part, ';q=')) {
                [$lang, $q] = explode(';q=', $part);
                $languages[$lang] = (float) $q;
            } else {
                $languages[$part] = 1.0;
            }
        }

        // Sort by quality value (descending)
        arsort($languages);

        return array_keys($languages);
    }
}
