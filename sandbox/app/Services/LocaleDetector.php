<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleDetector
{
    public function __construct(
        protected ?Request $request = null
    ) {
        $this->request = $request ?? request();
    }

    /**
     * Detect the best locale for the current request.
     */
    public function detect(): string
    {
        $supportedLocales = config('localization.supported_locales', ['nl', 'en', 'de']);
        $defaultLocale = config('localization.default_locale', 'nl');
        $detectionOrder = config('localization.detection_order', ['session', 'cookie', 'browser']);

        // Skip 'url' detection since we're detecting for redirect
        $detectionOrder = array_filter($detectionOrder, fn ($method) => $method !== 'url');

        foreach ($detectionOrder as $method) {
            $locale = match ($method) {
                'session' => $this->getLocaleFromSession(),
                'cookie' => $this->getLocaleFromCookie(),
                'browser' => $this->getLocaleFromBrowser(),
                default => null,
            };

            if ($locale && in_array($locale, $supportedLocales)) {
                return $locale;
            }
        }

        return $defaultLocale;
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
    protected function getLocaleFromCookie(): ?string
    {
        return $this->request->cookie(config('localization.cookie.name'));
    }

    /**
     * Get locale from browser's Accept-Language header.
     */
    protected function getLocaleFromBrowser(): ?string
    {
        $acceptLanguage = $this->request->header('Accept-Language');

        if (! $acceptLanguage) {
            return null;
        }

        $browserLocaleMapping = config('localization.browser_locale_mapping', []);
        $supportedLocales = config('localization.supported_locales', []);

        $languages = $this->parseAcceptLanguage($acceptLanguage);

        foreach ($languages as $lang) {
            if (isset($browserLocaleMapping[$lang])) {
                return $browserLocaleMapping[$lang];
            }

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

        arsort($languages);

        return array_keys($languages);
    }
}
