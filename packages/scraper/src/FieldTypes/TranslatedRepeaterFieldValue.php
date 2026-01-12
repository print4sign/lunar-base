<?php

namespace Lunar\Scraper\FieldTypes;

use Illuminate\Support\Collection;
use JsonSerializable;
use Lunar\Base\FieldType;

/**
 * Generic translatable repeater field type.
 * Stores data as Collection where keys are language codes.
 * Each value is an array of repeater items.
 */
class TranslatedRepeaterFieldValue implements FieldType, JsonSerializable
{
    protected Collection $value;

    public function __construct(array|Collection $value = [])
    {
        if ($value instanceof Collection) {
            $this->value = $value;
        } else {
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
            $this->value = $value;
        } elseif (is_array($value)) {
            $this->value = collect($value);
        } else {
            $this->value = collect();
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->value->toArray();
    }

    public function getConfig(): array
    {
        return [];
    }
}
