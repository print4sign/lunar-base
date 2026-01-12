<?php

namespace Lunar\Scraper\Admin\Forms\Components;

use Closure;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Str;
use Lunar\Base\FieldType;

/**
 * Custom Repeater that can handle Lunar FieldType objects as state.
 * Uses deferred state transformation to work with Lunar's AttributeData wrapper.
 */
class FieldTypeRepeater extends Repeater
{
    /**
     * Override callAfterStateHydrated to handle FieldType conversion LAST.
     * This ensures our conversion runs after Lunar's formatStateUsing.
     */
    public function callAfterStateHydrated(): static
    {
        // Let parent (and all chained callbacks including Lunar's) run first
        parent::callAfterStateHydrated();

        // Now convert FieldType to array if needed
        $state = $this->getState();

        if ($state instanceof FieldType) {
            $items = $state->getValue();
            $keyed = $this->addUuidKeys($items);
            $this->state($keyed);
        } elseif (is_array($state)) {
            // Ensure UUID keys even if already an array
            $keyed = $this->addUuidKeys($state);
            if ($keyed !== $state) {
                $this->state($keyed);
            }
        }

        return $this;
    }

    /**
     * Add UUID keys to items if they don't have them.
     */
    protected function addUuidKeys(array $items): array
    {
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
