<?php

namespace App\Generators;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Lunar\Models\Language;
use Lunar\Models\Url;

class MultilingualUrlGenerator
{
    /**
     * The instance of the model.
     */
    protected Model $model;

    /**
     * Cached languages.
     */
    protected static ?array $languages = null;

    /**
     * Handle the URL generation for all languages.
     */
    public function handle(Model $model): void
    {
        $this->model = $model;

        // Skip if URLs already exist
        if ($model->urls()->count() > 0) {
            return;
        }

        $languages = $this->getLanguages();
        $defaultLanguage = $this->getDefaultLanguage();

        foreach ($languages as $language) {
            $this->createUrlForLanguage($language, $language->id === $defaultLanguage?->id);
        }
    }

    /**
     * Create a URL for a specific language.
     */
    protected function createUrlForLanguage(Language $language, bool $isDefault = false): void
    {
        // Get the translated name for this language
        $name = $this->getTranslatedName($language->code);

        if (! $name) {
            return;
        }

        $slug = Str::slug($name);
        $uniqueSlug = $this->getUniqueSlug($slug, $language->id);

        $this->model->urls()->create([
            'default' => $isDefault,
            'language_id' => $language->id,
            'slug' => $uniqueSlug,
        ]);
    }

    /**
     * Get the translated name for the model in a specific language.
     */
    protected function getTranslatedName(string $locale): ?string
    {
        // Try to get the name attribute for this locale using Lunar's translateAttribute
        if (method_exists($this->model, 'translateAttribute')) {
            $name = $this->model->translateAttribute('name', $locale);

            if ($name) {
                return $name;
            }
        }

        // Fallback: try to get name directly
        if ($this->model->name) {
            return $this->model->name;
        }

        return null;
    }

    /**
     * Generate a unique slug for the given language.
     */
    protected function getUniqueSlug(string $slug, int $languageId): string
    {
        $separator = '-';
        $existingSlugs = $this->getExistingSlugs($slug, $separator, $languageId);

        // It is already unique
        if (! $existingSlugs->count() || ! $existingSlugs->contains($slug)) {
            return $slug;
        }

        // Check if this model already has this slug
        if ($existingSlugs->has($this->model->getKey())) {
            $currentSlug = $existingSlugs->get($this->model->getKey());

            if ($currentSlug === $slug || str_starts_with($currentSlug, $slug)) {
                return $currentSlug;
            }
        }

        // Generate suffix
        $suffix = $this->getSuffix($slug, $separator, $existingSlugs);

        return $slug . $separator . $suffix;
    }

    /**
     * Get existing slugs similar to the given slug for a language.
     */
    protected function getExistingSlugs(string $slug, string $separator, int $languageId)
    {
        return Url::where(function ($query) use ($slug, $separator) {
            $query->where('slug', $slug)
                ->orWhere('slug', 'like', $slug . $separator . '%');
        })
            ->where('language_id', $languageId)
            ->select(['element_id', 'slug'])
            ->get()
            ->toBase()
            ->pluck('slug', 'element_id');
    }

    /**
     * Calculate suffix for unique slug.
     */
    protected function getSuffix(string $slug, string $separator, $slugs): string
    {
        $len = strlen($slug . $separator);

        if ($slugs->search($slug) === $this->model->getKey()) {
            $suffix = explode($separator, $slug);

            return end($suffix);
        }

        $slugs->transform(function ($value) use ($len) {
            return (int) substr($value, $len);
        });

        $max = $slugs->max();

        return (string) ($max === 0 ? 2 : $max + 1);
    }

    /**
     * Get all languages.
     */
    protected function getLanguages(): array
    {
        if (static::$languages === null) {
            static::$languages = Language::all()->all();
        }

        return static::$languages;
    }

    /**
     * Get the default language.
     */
    protected function getDefaultLanguage(): ?Language
    {
        foreach ($this->getLanguages() as $language) {
            if ($language->default) {
                return $language;
            }
        }

        return null;
    }

    /**
     * Clear the language cache.
     */
    public static function clearCache(): void
    {
        static::$languages = null;
    }
}
