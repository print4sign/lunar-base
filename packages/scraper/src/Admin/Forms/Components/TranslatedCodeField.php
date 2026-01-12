<?php

namespace Lunar\Scraper\Admin\Forms\Components;

use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Collection;
use Lunar\Models\Language;
use Lunar\Scraper\FieldTypes\TranslatedCodeFieldValue;

/**
 * A translated code/textarea field - shows one Textarea per language.
 * Similar to TranslatedText but specifically for code/HTML content.
 */
class TranslatedCodeField extends Field
{
    protected string $view = 'lunarscraper::forms.components.translated-code-field';

    protected ?Collection $textareaComponents = null;

    protected int $rows = 10;

    protected ?string $fieldTypeClass = null;

    public bool $expanded = false;

    public Collection $languages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languages = Language::orderBy('default', 'desc')->get();

        $this->default(fn () => $this->getLanguageDefaults());

        $this->afterStateHydrated(function (TranslatedCodeField $component, $state) {
            if ($state instanceof TranslatedCodeFieldValue) {
                $component->state($state->getValue()->toArray());
            } elseif (is_object($state) && method_exists($state, 'getValue')) {
                $value = $state->getValue();
                $arrayState = $value instanceof Collection ? $value->toArray() : (array) $value;
                $component->state($arrayState);
            }
        });

        $this->dehydrateStateUsing(function ($state) {
            if ($state instanceof TranslatedCodeFieldValue) {
                return $state;
            }

            $instance = new TranslatedCodeFieldValue;
            $instance->setValue(collect(is_array($state) ? $state : []));

            return $instance;
        });
    }

    public function prepareChildComponents(): void
    {
        if ($this->textareaComponents !== null) {
            return;
        }

        $this->textareaComponents = collect(
            $this->getLanguages()->map(fn ($lang) => $this->createTextareaForLanguage($lang->code))
        );
    }

    public function getComponentByLanguage(Language $language): ComponentContainer
    {
        $this->prepareChildComponents();

        $currentState = $this->getState();

        if ($currentState instanceof TranslatedCodeFieldValue) {
            $this->state($currentState->getValue()->toArray());
        } elseif (is_object($currentState) && method_exists($currentState, 'getValue')) {
            $value = $currentState->getValue();
            $arrayState = $value instanceof Collection ? $value->toArray() : (array) $value;
            $this->state($arrayState);
        }

        return ComponentContainer::make($this->getLivewire())
            ->parentComponent($this)
            ->components(
                $this->textareaComponents
                    ->filter(fn ($component): bool => $component->getName() === $language->code)
                    ->all()
            )
            ->getClone();
    }

    protected function createTextareaForLanguage(string $langCode): Textarea
    {
        return Textarea::make($langCode)
            ->statePath($langCode)
            ->rows($this->rows)
            ->extraAttributes(['class' => 'font-mono text-sm'])
            ->hiddenLabel();
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
        return $this->getLanguages()->mapWithKeys(fn ($language) => [$language->code => ''])->toArray();
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

    public function rows(int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    public function getRows(): int
    {
        return $this->rows;
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
