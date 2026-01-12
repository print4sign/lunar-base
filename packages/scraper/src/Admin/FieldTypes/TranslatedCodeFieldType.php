<?php

namespace Lunar\Scraper\Admin\FieldTypes;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Lunar\Admin\Support\FieldTypes\BaseFieldType;
use Lunar\Models\Attribute;
use Lunar\Scraper\Admin\Forms\Components\TranslatedCodeField;
use Lunar\Scraper\Admin\Synthesizers\TranslatedCodeSynth;
use Lunar\Scraper\FieldTypes\TranslatedCodeFieldValue;

/**
 * A translatable code/textarea field type.
 * Perfect for HTML content, specifications, or any long text that needs translation.
 */
class TranslatedCodeFieldType extends BaseFieldType
{
    protected static string $synthesizer = TranslatedCodeSynth::class;

    public static function getFilamentComponent(Attribute $attribute): Component
    {
        $config = $attribute->configuration;
        $rows = $config->get('rows', 10);

        return TranslatedCodeField::make($attribute->handle)
            ->fieldType(TranslatedCodeFieldValue::class)
            ->statePath($attribute->handle)
            ->rows($rows)
            ->helperText($attribute->translate('description'));
    }

    public static function getConfigurationFields(): array
    {
        return [
            TextInput::make('rows')
                ->label(__('lunarscraper::fieldtypes.code.config.rows'))
                ->numeric()
                ->default(10)
                ->helperText(__('lunarscraper::fieldtypes.code.config.rows_help')),
        ];
    }
}
