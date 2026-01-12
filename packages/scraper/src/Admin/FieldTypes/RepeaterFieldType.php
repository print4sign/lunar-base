<?php

namespace Lunar\Scraper\Admin\FieldTypes;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Lunar\Admin\Support\FieldTypes\BaseFieldType;
use Lunar\Admin\Support\Synthesizers\ListSynth;
use Lunar\Models\Attribute;
use Lunar\Scraper\Admin\Forms\Components\FieldTypeRepeater;

/**
 * A configurable repeater field type (non-translated).
 *
 * Configure the schema via attribute configuration:
 * - schema: array of field definitions [{name, type, label, required, options}]
 * - item_label_field: which field to use for collapsed item label
 * - add_label: label for the add button
 * - columns: number of columns (default 1)
 */
class RepeaterFieldType extends BaseFieldType
{
    protected static string $synthesizer = ListSynth::class;

    public static function getFilamentComponent(Attribute $attribute): Component
    {
        $config = $attribute->configuration;
        $schema = self::buildSchemaFromConfig($config->get('schema', []));
        $itemLabelField = $config->get('item_label_field', null);
        $addLabel = $config->get('add_label', __('lunarscraper::fieldtypes.repeater.add'));
        $columns = $config->get('columns', 1);

        $component = FieldTypeRepeater::make($attribute->handle)
            ->label($attribute->translate('name'))
            ->schema($schema)
            ->columns($columns)
            ->collapsible()
            ->collapsed()
            ->addActionLabel($addLabel)
            ->default([])
            ->helperText($attribute->translate('description'));

        if ($itemLabelField) {
            $component->itemLabel(fn (array $state): ?string => $state[$itemLabelField] ?? null);
        }

        return $component;
    }

    /**
     * Build Filament schema from configuration array.
     */
    protected static function buildSchemaFromConfig(array $schemaConfig): array
    {
        $schema = [];

        foreach ($schemaConfig as $field) {
            $name = $field['name'] ?? null;
            $type = $field['type'] ?? 'text';
            $label = $field['label'] ?? $name;
            $required = $field['required'] ?? false;
            $options = $field['options'] ?? [];

            if (! $name) {
                continue;
            }

            $component = match ($type) {
                'textarea' => Textarea::make($name)
                    ->label($label)
                    ->rows($options['rows'] ?? 3),
                'url' => TextInput::make($name)
                    ->label($label)
                    ->url(),
                'email' => TextInput::make($name)
                    ->label($label)
                    ->email(),
                'number' => TextInput::make($name)
                    ->label($label)
                    ->numeric(),
                'toggle' => Toggle::make($name)
                    ->label($label),
                default => TextInput::make($name)
                    ->label($label),
            };

            if ($required) {
                $component->required();
            }

            $schema[] = $component;
        }

        return $schema;
    }

    public static function getConfigurationFields(): array
    {
        return [
            Repeater::make('schema')
                ->label(__('lunarscraper::fieldtypes.repeater.config.schema.label'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('lunarscraper::fieldtypes.repeater.config.schema.name'))
                        ->required(),
                    \Filament\Forms\Components\Select::make('type')
                        ->label(__('lunarscraper::fieldtypes.repeater.config.schema.type'))
                        ->options([
                            'text' => 'Text',
                            'textarea' => 'Textarea',
                            'url' => 'URL',
                            'email' => 'Email',
                            'number' => 'Number',
                            'toggle' => 'Toggle',
                        ])
                        ->default('text'),
                    TextInput::make('label')
                        ->label(__('lunarscraper::fieldtypes.repeater.config.schema.field_label')),
                    Toggle::make('required')
                        ->label(__('lunarscraper::fieldtypes.repeater.config.schema.required')),
                ])
                ->columns(4)
                ->defaultItems(1)
                ->addActionLabel(__('lunarscraper::fieldtypes.repeater.config.schema.add_field')),
            TextInput::make('item_label_field')
                ->label(__('lunarscraper::fieldtypes.repeater.config.item_label_field'))
                ->helperText(__('lunarscraper::fieldtypes.repeater.config.item_label_field_help')),
            TextInput::make('add_label')
                ->label(__('lunarscraper::fieldtypes.repeater.config.add_label')),
            TextInput::make('columns')
                ->label(__('lunarscraper::fieldtypes.repeater.config.columns'))
                ->numeric()
                ->default(1),
        ];
    }
}
