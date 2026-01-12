<?php

namespace Lunar\Scraper\Admin\Forms\Components;

use Closure;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Collection;
use Lunar\Models\Language;
use Lunar\Scraper\FieldTypes\TranslatedRepeaterFieldValue;

/**
 * A translated repeater field - shows one Repeater per language.
 * Similar to TranslatedText but with Repeaters instead of TextInputs.
 */
class TranslatedRepeaterField extends Field
{
    protected string $view = 'lunarscraper::forms.components.translated-repeater-field';

    protected ?Collection $repeaterComponents = null;

    protected array $repeaterSchema = [];

    protected ?Closure $itemLabelUsing = null;

    protected ?string $addActionLabel = null;

    protected int $repeaterColumns = 1;

    protected bool $repeaterCollapsible = true;

    protected bool $repeaterCollapsed = true;

    protected ?string $fieldTypeClass = null;

    public bool $expanded = true;

    public Collection $languages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languages = Language::orderBy('default', 'desc')->get();

        $this->default(fn () => $this->getLanguageDefaults());

        $this->afterStateHydrated(function (TranslatedRepeaterField $component, $state) {
            if ($state instanceof TranslatedRepeaterFieldValue) {
                $component->state($state->getValue()->toArray());
            } elseif (is_object($state) && method_exists($state, 'getValue')) {
                $value = $state->getValue();
                $arrayState = $value instanceof Collection ? $value->toArray() : (array) $value;
                $component->state($arrayState);
            }
        });

        $this->dehydrateStateUsing(function ($state) {
            if ($state instanceof TranslatedRepeaterFieldValue) {
                return $state;
            }

            $instance = new TranslatedRepeaterFieldValue;
            $instance->setValue(collect(is_array($state) ? $state : []));

            return $instance;
        });
    }

    public function prepareChildComponents(): void
    {
        if ($this->repeaterComponents !== null) {
            return;
        }

        $this->repeaterComponents = collect(
            $this->getLanguages()->map(fn ($lang) => $this->createRepeaterForLanguage($lang->code))
        );
    }

    public function getComponentByLanguage(Language $language): ComponentContainer
    {
        $this->prepareChildComponents();

        $currentState = $this->getState();

        if ($currentState instanceof TranslatedRepeaterFieldValue) {
            $this->state($currentState->getValue()->toArray());
        } elseif (is_object($currentState) && method_exists($currentState, 'getValue')) {
            $value = $currentState->getValue();
            $arrayState = $value instanceof Collection ? $value->toArray() : (array) $value;
            $this->state($arrayState);
        }

        return ComponentContainer::make($this->getLivewire())
            ->parentComponent($this)
            ->components(
                $this->repeaterComponents
                    ->filter(fn ($component): bool => $component->getName() === $language->code)
                    ->all()
            )
            ->getClone();
    }

    protected function createRepeaterForLanguage(string $langCode): Repeater
    {
        $schemaConfig = $this->repeaterSchema;

        $repeater = Repeater::make($langCode)
            ->statePath($langCode)
            ->schema(fn (): array => array_map(fn ($component) => clone $component, $schemaConfig))
            ->columns($this->repeaterColumns)
            ->collapsible($this->repeaterCollapsible)
            ->collapsed($this->repeaterCollapsed)
            ->defaultItems(0)
            ->hiddenLabel();

        if ($this->itemLabelUsing) {
            $repeater->itemLabel($this->itemLabelUsing);
        }

        if ($this->addActionLabel) {
            $repeater->addActionLabel($this->addActionLabel);
        }

        return $repeater;
    }

    public function getDefaultLanguage(): Language
    {
        return $this->languages->first(fn ($lang) => $lang->default);
    }

    public function getMoreLanguages(): Collection
    {
        return $this->languages->filter(fn ($lang) => ! $lang->default);
    }

    public function getLanguages(): Collection
    {
        return $this->languages;
    }

    public function getLanguageDefaults(): array
    {
        return $this->getLanguages()->mapWithKeys(fn ($language) => [$language->code => []])->toArray();
    }

    public function getExpanded(): bool
    {
        return $this->expanded;
    }

    public function expanded(bool $expanded = true): static
    {
        $this->expanded = $expanded;

        return $this;
    }

    public function repeaterSchema(array $schema): static
    {
        $this->repeaterSchema = $schema;

        return $this;
    }

    public function getRepeaterSchema(): array
    {
        return $this->repeaterSchema;
    }

    public function itemLabel(Closure $callback): static
    {
        $this->itemLabelUsing = $callback;

        return $this;
    }

    public function addActionLabel(string $label): static
    {
        $this->addActionLabel = $label;

        return $this;
    }

    public function repeaterColumns(int $columns): static
    {
        $this->repeaterColumns = $columns;

        return $this;
    }

    public function collapsible(bool $collapsible = true): static
    {
        $this->repeaterCollapsible = $collapsible;

        return $this;
    }

    public function collapsed(bool $collapsed = true): static
    {
        $this->repeaterCollapsed = $collapsed;

        return $this;
    }

    public function fieldType(string $fieldTypeClass): static
    {
        $this->fieldTypeClass = $fieldTypeClass;

        return $this;
    }

    public function getFieldTypeClass(): ?string
    {
        return $this->fieldTypeClass;
    }
}
