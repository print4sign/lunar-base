@php
    $defaultLang = $getDefaultLanguage();
    $moreLangs = $getMoreLanguages();
    $languages = $getLanguages();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    x-data="{ showTranslations: {{ $getExpanded() ? 'true' : 'false' }} }"
>
    <div class="space-y-4">
        {{-- Default language - always visible --}}
        <div class="flex items-start gap-2">
            @if ($moreLangs->count())
                <span
                    class="mt-2 flex items-center justify-center w-8 h-8 text-xs font-normal rounded shadow-sm bg-gray-200 text-gray-400 dark:bg-white/5 dark:text-white uppercase"
                >
                    {{ Str::upper($defaultLang->code) }}
                </span>
            @endif
            <div class="flex-1">
                {{ $getComponentByLanguage($defaultLang) }}
            </div>
        </div>

        {{-- Other languages - toggleable --}}
        @foreach ($moreLangs as $language)
            <div
                x-show="showTranslations"
                x-cloak
                class="flex items-start gap-2"
            >
                <span
                    class="mt-2 flex items-center justify-center w-8 h-8 text-xs font-normal rounded shadow-sm bg-gray-200 text-gray-400 dark:bg-white/5 dark:text-white uppercase"
                >
                    {{ Str::upper($language->code) }}
                </span>
                <div class="flex-1">
                    {{ $getComponentByLanguage($language) }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Language toggle button --}}
    @if ($moreLangs->count())
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
