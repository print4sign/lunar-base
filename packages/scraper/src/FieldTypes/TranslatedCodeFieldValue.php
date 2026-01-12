<?php

namespace Lunar\Scraper\FieldTypes;

use Illuminate\Support\Collection;
use JsonSerializable;
use Lunar\Base\FieldType;
use Lunar\FieldTypes\Text;

/**
 * Translatable code/textarea field type.
 * Stores data as Collection where keys are language codes.
 * Each value is wrapped in a Text FieldType for compatibility with translateAttribute.
 */
class TranslatedCodeFieldValue implements FieldType, JsonSerializable
{
    protected Collection $value;

    public function __construct(array|Collection $value = [])
    {
        $this->value = new Collection;

        if ($value) {
            $this->setValue($value);
        }
    }

    public function getValue(): Collection
    {
        return $this->value ?? collect();
    }

    public function setValue(mixed $value): void
    {
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            $this->value = collect();

            return;
        }

        $collection = collect();

        foreach ($value as $locale => $content) {
            // Wrap strings in Text FieldType for translateAttribute compatibility
            if (is_string($content) || is_numeric($content)) {
                $collection[$locale] = new Text($content);
            } elseif ($content instanceof Text) {
                $collection[$locale] = $content;
            }
        }

        $this->value = $collection;
    }

    public function jsonSerialize(): mixed
    {
        // Serialize the Text objects to their string values
        return $this->value->map(fn ($text) => $text instanceof Text ? $text->getValue() : $text)->toArray();
    }

    public function getConfig(): array
    {
        return [
            'options' => [
                'rows' => 'nullable|integer|min:1',
                'language' => 'nullable|string',
            ],
        ];
    }
}
