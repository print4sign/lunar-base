<?php

namespace Lunar\Scraper\FieldTypes;

use JsonSerializable;
use Lunar\Base\FieldType;

/**
 * Generic repeater field type (non-translated).
 * Stores an array of repeater items.
 */
class RepeaterFieldValue implements FieldType, JsonSerializable
{
    protected array $value = [];

    public function __construct(array $value = [])
    {
        $this->setValue($value);
    }

    public function getValue(): array
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = is_array($value) ? $value : [];
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    public function getConfig(): array
    {
        return [];
    }
}
