<?php

namespace Lunar\Scraper\Admin\Synthesizers;

use Lunar\Admin\Support\Synthesizers\AbstractFieldSynth;
use Lunar\Scraper\FieldTypes\TranslatedCodeFieldValue;

class TranslatedCodeSynth extends AbstractFieldSynth
{
    public static $key = 'lunar_translatedcode_field';

    protected static $targetClass = TranslatedCodeFieldValue::class;

    public function dehydrate($target)
    {
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

        if (! ($collectionValue instanceof \Illuminate\Support\Collection)) {
            $collectionValue = collect($collectionValue ?? []);
        }

        $collectionValue->put($key, $value);
        $target->setValue($collectionValue);
    }
}
