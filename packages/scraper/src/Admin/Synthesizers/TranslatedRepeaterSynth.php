<?php

namespace Lunar\Scraper\Admin\Synthesizers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lunar\Admin\Support\Synthesizers\AbstractFieldSynth;
use Lunar\Models\Language;
use Lunar\Scraper\FieldTypes\TranslatedRepeaterFieldValue;

/**
 * Generic synthesizer for TranslatedRepeaterFieldValue.
 */
class TranslatedRepeaterSynth extends AbstractFieldSynth
{
    public static $key = 'lunar_translated_repeater_field';

    protected static $targetClass = TranslatedRepeaterFieldValue::class;

    public function dehydrate($target)
    {
        $value = $target->getValue();

        if ($value instanceof Collection) {
            $languages = Language::orderBy('default', 'desc')->get();

            $result = $languages->mapWithKeys(function ($language) use ($value) {
                $items = $value->get($language->code, []);

                $keyed = [];
                foreach ($items as $key => $item) {
                    if (is_array($item)) {
                        $uuid = is_string($key) && Str::isUuid($key) ? $key : (string) Str::uuid();
                        $keyed[$uuid] = $item;
                    }
                }

                return [$language->code => $keyed];
            })->toArray();

            return [$result, []];
        }

        return [[], []];
    }

    public function hydrate($value)
    {
        $instance = new TranslatedRepeaterFieldValue;
        $instance->setValue(collect(is_array($value) ? $value : []));

        return $instance;
    }

    public function get(&$target, $key)
    {
        if ($target instanceof TranslatedRepeaterFieldValue) {
            $items = $target->getValue()->get($key, []);

            if (is_array($items)) {
                $keyed = [];
                foreach ($items as $k => $item) {
                    if (is_array($item)) {
                        $uuid = is_string($k) && Str::isUuid($k) ? $k : (string) Str::uuid();
                        $keyed[$uuid] = $item;
                    }
                }

                return $keyed;
            }

            return [];
        }

        return [];
    }

    public function set(&$target, $key, $value)
    {
        if ($target instanceof TranslatedRepeaterFieldValue) {
            $collection = $target->getValue();
            $collection->put($key, $value);
            $target->setValue($collection);
        }
    }
}
