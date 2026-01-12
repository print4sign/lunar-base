<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    x-data="{ showTranslations: {{ $getExpanded() ? 'true' : 'false' }} }"
>
    <div x-data="{ state: $wire.entangle('{{ $getStatePath() }}') }">
        {{-- Default language repeater --}}
        <div class="flex items-start gap-2">
            @if ($getMoreLanguages()->count())
                <span x-show="showTranslations"
                    class="mt-2 flex items-center justify-center w-8 h-8 text-xs font-normal rounded shadow-sm bg-gray-200 text-gray-400 dark:bg-white/5 dark:text-white uppercase">
                    {{ Str::upper($getDefaultLanguage()->code) }}
                </span>
            @endif
            <div class="flex-1">
                {{ $getRepeaterForLanguage($getDefaultLanguage()) }}
            </div>
        </div>

        {{-- Additional language repeaters --}}
        @if ($getMoreLanguages()->count())
            @foreach ($getMoreLanguages() as $language)
                <div x-show="showTranslations" class="flex items-start gap-2 mt-4">
                    <span class="mt-2 flex items-center justify-center w-8 h-8 text-xs font-normal rounded shadow-sm bg-gray-200 text-gray-400 dark:bg-white/5 dark:text-white uppercase">
                        {{ Str::upper($language->code) }}
                    </span>
                    <div class="flex-1">
                        {{ $getRepeaterForLanguage($language) }}
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Language toggle button --}}
    @if ($getMoreLanguages()->count())
        <div class="mt-2">
            <x-filament::button
                x-on:click.prevent="showTranslations = !showTranslations"
                size="xs"
                color="gray"
            >
                <x-filament::icon
                    alias="lunar::languages"
                    @class(['w-3.5 h-3.5 inline-flex'])
                />
                <span class="ml-2">
                    {{ __('lunarpanel::fieldtypes.translatedtext.form.locales') }}
                </span>
            </x-filament::button>
        </div>
    @endif
</x-dynamic-component>
