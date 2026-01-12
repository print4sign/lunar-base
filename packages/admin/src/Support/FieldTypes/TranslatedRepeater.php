<?php

namespace Lunar\Admin\Support\FieldTypes;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Lunar\Admin\Support\Synthesizers\TranslatedRepeaterSynth;
use Lunar\Models\Attribute;
use Lunar\Models\Language;

class TranslatedRepeater extends BaseFieldType
{
    protected static string $synthesizer = TranslatedRepeaterSynth::class;

    /**
     * Configuration fields shown in the attribute edit modal.
     */
    public static function getConfigurationFields(): array
    {
        return [
            Repeater::make('fields')
                ->label(__('lunarpanel::fieldtypes.translatedrepeaterfieldvalue.form.fields.label'))
                ->schema([
                    TextInput::make('name')
                        ->label('Field Name')
                        ->required()
                        ->helperText('The internal name (e.g., "title", "url", "message")'),
                    TextInput::make('label')
                        ->label('Label')
                        ->required()
                        ->helperText('Display label for the field'),
                    Select::make('type')
                        ->label('Type')
                        ->options([
                            'text' => 'Text Input',
                            'url' => 'URL',
                            'select' => 'Select (info/warning/success/error)',
                        ])
                        ->default('text'),
                ])
                ->defaultItems(0)
                ->addActionLabel('Add Field')
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['name'] ?? null)
                ->formatStateUsing(function ($state) {
                    // Convert Collection to array if needed
                    if ($state instanceof \Illuminate\Support\Collection) {
                        return $state->toArray();
                    }
                    return is_array($state) ? $state : [];
                }),
            TextInput::make('item_label_field')
                ->label(__('lunarpanel::fieldtypes.translatedrepeaterfieldvalue.form.item_label_field.label'))
                ->helperText('The field name to use as the label for collapsed items'),
            TextInput::make('add_button_label')
                ->label(__('lunarpanel::fieldtypes.translatedrepeaterfieldvalue.form.add_button_label.label')),
            TextInput::make('columns')
                ->label(__('lunarpanel::fieldtypes.translatedrepeaterfieldvalue.form.columns.label'))
                ->numeric()
                ->default(1),
        ];
    }

    /**
     * Build the schema from attribute configuration.
     */
    protected static function buildSchemaFromConfig(Attribute $attribute): array
    {
        $config = $attribute->configuration;
        $fields = $config->get('fields', []);

        // If no fields configured, use defaults based on handle
        if (empty($fields)) {
            return static::getDefaultSchemaForHandle($attribute->handle);
        }

        $schema = [];
        foreach ($fields as $field) {
            $name = $field['name'] ?? 'value';
            $label = $field['label'] ?? ucfirst($name);
            $type = $field['type'] ?? 'text';

            $component = match ($type) {
                'url' => TextInput::make($name)->label($label)->url(),
                'select' => Select::make($name)->label($label)->options([
                    'info' => 'Info',
                    'warning' => 'Warning',
                    'success' => 'Success',
                    'error' => 'Error',
                ])->default('info'),
                default => TextInput::make($name)->label($label),
            };

            $schema[] = $component;
        }

        return $schema;
    }

    /**
     * Default schemas for known attribute handles when no config exists.
     */
    protected static function getDefaultSchemaForHandle(string $handle): array
    {
        return match ($handle) {
            'downloads' => [
                TextInput::make('title')->label('Title')->required(),
                TextInput::make('url')->label('URL')->url(),
            ],
            'faq' => [
                TextInput::make('question')->label('Question')->required(),
                TextInput::make('answer')->label('Answer')->required(),
            ],
            'notifications' => [
                TextInput::make('message')->label('Message')->required(),
                Select::make('type')->label('Type')->options([
                    'info' => 'Info',
                    'warning' => 'Warning',
                    'success' => 'Success',
                    'error' => 'Error',
                ])->default('info'),
            ],
            'option-alerts' => [
                TextInput::make('message')->label('Message')->required(),
            ],
            'product-benefits' => [
                TextInput::make('value')->label('Benefit')->required(),
            ],
            default => [
                TextInput::make('value')->label('Value')->required(),
            ],
        };
    }

    /**
     * Get the item label field from config or default.
     */
    protected static function getItemLabelField(Attribute $attribute): string
    {
        $config = $attribute->configuration;

        if ($labelField = $config->get('item_label_field')) {
            return $labelField;
        }

        // Default based on handle
        return match ($attribute->handle) {
            'downloads' => 'title',
            'faq' => 'question',
            'notifications', 'option-alerts' => 'message',
            default => 'value',
        };
    }

    public static function getFilamentComponent(Attribute $attribute): Component
    {
        $languages = Language::orderBy('default', 'desc')->get();
        $schema = static::buildSchemaFromConfig($attribute);
        $labelField = static::getItemLabelField($attribute);
        $config = $attribute->configuration;
        $addButtonLabel = $config->get('add_button_label') ?: __('lunarpanel::components.repeater.add');

        $defaultLanguageCode = $languages->first()?->code;

        // Build sections with repeaters for each language
        $sections = $languages->map(function ($language) use ($schema, $labelField, $defaultLanguageCode, $addButtonLabel) {
            return Section::make(strtoupper($language->code))
                ->schema([
                    Repeater::make($language->code)
                        ->label('')
                        ->schema($schema)
                        ->itemLabel(fn (array $state): ?string => $state[$labelField] ?? null)
                        ->collapsible()
                        ->reorderable()
                        ->addActionLabel($addButtonLabel)
                        ->defaultItems(0),
                ])
                ->collapsible()
                ->collapsed($language->code !== $defaultLanguageCode);
        })->toArray();

        return Group::make($sections)
            ->when(filled($attribute->validation_rules), fn (Group $component) => $component->rules($attribute->validation_rules))
            ->columnSpanFull();
    }
}
