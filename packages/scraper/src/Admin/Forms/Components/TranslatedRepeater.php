<?php

namespace Lunar\Scraper\Admin\Forms\Components;

use Closure;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lunar\Base\FieldType;
use Lunar\Models\Language;

/**
 * A Repeater component that works with Lunar's translated FieldTypes.
 *
 * This extends Filament's native Repeater and handles the FieldType
 * to array conversion that Lunar's AttributeData wrapper expects.
 */
class TranslatedRepeater extends Repeater
{
    protected ?string $fieldTypeClass = null;

    protected ?string $languageCode = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Handle FieldType state after all other hydration
        $this->afterStateHydrated(function (TranslatedRepeater $component, $state) {
            $langCode = $component->getLanguageCode();

            // If state is a FieldType, extract the language-specific data
            if ($state instanceof FieldType) {
                $value = $state->getValue();
                if ($value instanceof Collection && $langCode) {
                    $langData = $value->get($langCode, []);
                    $component->state(static::addUuidKeys(is_array($langData) ? $langData : []));

                    return;
                }
            }

            // If state is already an array, ensure UUID keys
            if (is_array($state)) {
                $component->state(static::addUuidKeys($state));
            }
        });
    }

    public function fieldType(string $fieldTypeClass): static
    {
        $this->fieldTypeClass = $fieldTypeClass;

        return $this;
    }

    public function getFieldTypeClass(): ?string
    {
        return $this->fieldTypeClass;
    }

    public function language(string $code): static
    {
        $this->languageCode = $code;

        return $this;
    }

    public function getLanguageCode(): ?string
    {
        return $this->languageCode;
    }

    public static function addUuidKeys(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $keyed = [];

        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $uuid = is_string($key) && Str::isUuid($key) ? $key : (string) Str::uuid();
                $keyed[$uuid] = $item;
            }
        }

        return $keyed;
    }
}
