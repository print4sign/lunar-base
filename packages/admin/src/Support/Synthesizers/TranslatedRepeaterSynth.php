<?php

namespace Lunar\Admin\Support\Synthesizers;

use Lunar\Scraper\FieldTypes\TranslatedRepeaterFieldValue;

class TranslatedRepeaterSynth extends AbstractFieldSynth
{
    public static $key = 'lunar_translatedrepeater_field';

    protected static $targetClass = TranslatedRepeaterFieldValue::class;

    public function dehydrate($target)
    {
        // Convert the Collection to array for Livewire
        $value = $target->getValue();

        if ($value instanceof \Illuminate\Support\Collection) {
            return [$value->toArray(), []];
        }

        return [$value ?? [], []];
    }

    public function hydrate($value)
    {
        $instance = new static::$targetClass;
        $instance->setValue($value ?? []);

        return $instance;
    }

    public function get(&$target, $key)
    {
        $value = $target->getValue();

        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->get($key);
        }

        return $value[$key] ?? null;
    }

    public function set(&$target, $key, $value)
    {
        $collectionValue = $target->getValue();

        if (!($collectionValue instanceof \Illuminate\Support\Collection)) {
            $collectionValue = collect($collectionValue ?? []);
        }

        $collectionValue->put($key, $value);
        $target->setValue($collectionValue);
    }
}
