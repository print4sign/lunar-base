<div
    class="space-y-4"
    wire:loading.class="opacity-60 pointer-events-none"
    x-data="{
        initialized: false,
        focusFirstInput() {
            this.$nextTick(() => {
                const firstInput = this.$el.querySelector('input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
                if (firstInput) {
                    firstInput.focus();
                }
            });
        }
    }"
    x-init="setTimeout(() => {
        initialized = true;
        $dispatch('configurator-ready');
        focusFirstInput();
    }, 150)"
    @scroll-to-next-option.window="$nextTick(() => {
        setTimeout(() => {
            const sections = $el.querySelectorAll('[data-option-section]');
            let firstIncompleteSection = null;

            for (const section of sections) {
                const alpineData = section._x_dataStack?.[0];
                const hasCheckIcon = section.querySelector('svg.text-green-500');
                const isCompleted = !!hasCheckIcon;

                if (!isCompleted && alpineData) {
                    alpineData.open = true;
                    if (!firstIncompleteSection) {
                        firstIncompleteSection = section;
                    }
                } else if (isCompleted && alpineData) {
                    alpineData.open = false;
                }
            }

            if (firstIncompleteSection) {
                firstIncompleteSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setTimeout(() => {
                    const focusable = firstIncompleteSection.querySelector('button:not([disabled]), input:not([disabled]):not([type=hidden]), select:not([disabled])');
                    if (focusable) {
                        focusable.focus();
                    }
                }, 200);
            }
        }, 100);
    })"
>
    {{-- Error Message --}}
    @if ($errorMessage)
        <div class="p-4 text-sm text-red-700 bg-red-100 border border-red-200 rounded-lg">
            {{ $errorMessage }}
        </div>
    @endif

    @if (!$configurationData && $isLoading)
        {{-- Initial Loading State --}}
        <div class="flex items-center justify-center p-8">
            <svg class="animate-spin w-8 h-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="ml-2 text-sm text-gray-500">
                Configuratie laden...
            </span>
        </div>
    @else

        <div class="space-y-4">
            @php
                // Group options by type
                $dimensionCodes = ['width', 'height', 'length'];
                $dimensionOptions = collect($this->availableOptions)->filter(function($o) use ($dimensionCodes) {
                    $code = $o['code'] ?? '';
                    $type = $o['type'] ?? 'select';
                    $hasUnit = !empty($o['unit']);
                    $hasChoices = !empty($o['choices']);
                    if ($hasChoices) return false;
                    return in_array($code, $dimensionCodes) || ($type === 'number' && $hasUnit && in_array($o['unit'], ['cm', 'mm', 'm', 'inch', 'in']));
                });

                $hasDimensionOptions = $dimensionOptions->isNotEmpty();
                $dimensionsCompleted = !$hasDimensionOptions || (
                    isset($selections['width']) && $selections['width'] > 0
                    && isset($selections['height']) && $selections['height'] > 0
                );

                $quantityOption = $dimensionsCompleted ? collect($this->availableOptions)->first(function($o) {
                    $code = $o['code'] ?? '';
                    $type = $o['type'] ?? 'select';
                    $hasChoices = !empty($o['choices']);
                    return in_array($code, ['amount', 'quantity']) && $type === 'number' && !$hasChoices;
                }) : null;

                $configurableOptions = collect($this->availableOptions)->filter(function($o) use ($dimensionCodes) {
                    $code = $o['code'] ?? '';
                    $type = $o['type'] ?? 'select';
                    $hasChoices = !empty($o['choices']);
                    if (in_array($code, $dimensionCodes)) return false;
                    if (in_array($code, ['amount', 'quantity']) && $type === 'number' && !$hasChoices) return false;
                    if ($type === 'cross_sell') return false;
                    return $hasChoices || in_array($type, ['number', 'text']);
                });

                $crossSellGroups = collect($this->availableOptions)
                    ->filter(fn($o) => ($o['type'] ?? '') === 'cross_sell')
                    ->groupBy(fn($o) => $o['group_code'] ?? 'cross-sells');
            @endphp

            {{-- Dimensions Section --}}
            @if ($dimensionOptions->isNotEmpty())
                @php
                    $sizePresets = [
                        ['label' => 'A4', 'width' => 21, 'height' => 29.7],
                        ['label' => 'A3', 'width' => 29.7, 'height' => 42],
                        ['label' => 'A2', 'width' => 42, 'height' => 59.4],
                        ['label' => 'A1', 'width' => 59.4, 'height' => 84.1],
                        ['label' => 'A0', 'width' => 84.1, 'height' => 118.9],
                        ['label' => '50×70', 'width' => 50, 'height' => 70],
                        ['label' => '60×80', 'width' => 60, 'height' => 80],
                        ['label' => '70×100', 'width' => 70, 'height' => 100],
                        ['label' => '100×100', 'width' => 100, 'height' => 100],
                        ['label' => '100×200', 'width' => 100, 'height' => 200],
                        ['label' => '150×100', 'width' => 150, 'height' => 100],
                        ['label' => '200×100', 'width' => 200, 'height' => 100],
                    ];
                @endphp
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-gray-900">
                            Afmetingen
                        </span>
                    </div>

                    {{-- Size Presets --}}
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 mb-2">Standaard formaten:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($sizePresets as $preset)
                                <button
                                    type="button"
                                    wire:click="selectDimensions({{ $preset['width'] }}, {{ $preset['height'] }})"
                                    @class([
                                        'px-3 py-1.5 text-xs font-medium rounded-full transition-colors',
                                        'bg-primary-100 text-primary-700' =>
                                            (($selections['width'] ?? null) == $preset['width'] && ($selections['height'] ?? null) == $preset['height']),
                                        'bg-gray-200 text-gray-700 hover:bg-gray-300' =>
                                            !(($selections['width'] ?? null) == $preset['width'] && ($selections['height'] ?? null) == $preset['height']),
                                    ])
                                >
                                    {{ $preset['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Custom Dimensions --}}
                    <p class="text-xs text-gray-500 mb-2">Aangepast formaat:</p>
                    <div class="flex items-center gap-3" x-data @configurator-ready.window="setTimeout(() => $refs.dimensionInput0?.focus(), 50)">
                        @foreach ($dimensionOptions as $dimIndex => $option)
                            <div class="flex items-center gap-2">
                                <input
                                    type="number"
                                    x-ref="dimensionInput{{ $dimIndex }}"
                                    x-on:change="$wire.selectOption('{{ $option['code'] }}', $event.target.value)"
                                    value="{{ $selections[$option['code']] ?? $option['default'] ?? $option['min'] ?? '' }}"
                                    min="{{ $option['min'] ?? 1 }}"
                                    max="{{ $option['max'] ?? '' }}"
                                    step="{{ $option['step'] ?? 1 }}"
                                    placeholder="{{ $option['label'] }}"
                                    class="w-24 text-center border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-500">
                                    {{ $option['unit'] ?? 'cm' }}
                                </span>
                            </div>
                            @if (!$loop->last)
                                <span class="text-gray-400">×</span>
                            @endif
                        @endforeach

                        {{-- Area calculation --}}
                        @if (isset($selections['width']) && isset($selections['height']))
                            @php
                                $area = ($selections['width'] * $selections['height']) / 10000;
                            @endphp
                            <span class="ml-4 text-sm text-gray-500">
                                Totaal: {{ number_format($area, 2) }} m²
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Quantity Section --}}
            @if ($quantityOption)
                @php
                    $currentAmount = (int) ($selections['amount'] ?? 1);
                @endphp
                <div
                    class="p-4 bg-gray-50 rounded-lg border border-gray-200"
                    x-data="{ amount: {{ $currentAmount }} }"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if (isset($selections['amount']) && $selections['amount'] > 0)
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @else
                                <div class="w-5 h-5 border-2 border-gray-300 rounded-full"></div>
                            @endif
                            <span class="font-medium text-gray-900">
                                {{ $quantityOption['label'] ?? 'Aantal' }}
                            </span>
                        </div>
                        {{-- Right aligned: quantity input + submit button --}}
                        <div class="flex items-center gap-4">
                            <div class="inline-flex items-center rounded-lg border border-gray-300 bg-white shadow-sm">
                                <button
                                    type="button"
                                    @click="if (amount > 1) { amount--; }"
                                    class="flex items-center justify-center w-10 h-10 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-l-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                </button>
                                <input
                                    type="number"
                                    x-model="amount"
                                    @keydown.enter.prevent="$wire.selectOption('amount', parseInt(amount) || 1)"
                                    min="1"
                                    class="w-20 h-10 text-center text-sm font-medium border-0 bg-transparent text-gray-900 focus:outline-none focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                />
                                <button
                                    type="button"
                                    @click="amount++"
                                    class="flex items-center justify-center w-10 h-10 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-r-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </button>
                            </div>
                            {{-- Submit button - directly call Livewire, bypassing Alpine's change tracking --}}
                            <button
                                type="button"
                                @click="$wire.selectOption('amount', parseInt(amount) || 1)"
                                wire:loading.attr="disabled"
                                class="relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors whitespace-nowrap"
                            >
                                <span wire:loading.class="invisible" wire:target="selectOption">Nieuwe samenstelling</span>
                                <span wire:loading wire:target="selectOption" class="absolute inset-0 flex items-center justify-center">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Configurable Options --}}
            @foreach ($configurableOptions as $option)
                @php
                    $hasChoices = !empty($option['choices']);
                    $optionType = $option['type'] ?? 'select';
                    $isInputOption = in_array($optionType, ['number', 'text']) && !$hasChoices;

                    $inputValue = $this->getSelection($option['code']);
                    $isInputFilled = $isInputOption && $inputValue !== null && $inputValue !== '';

                    // Pass the option code to avoid false positives when the same choice value exists in multiple groups
                    $selectedChoiceValue = $hasChoices ? $this->getSelectedChoiceValue($option['choices'], $option['code'] ?? null) : null;
                    $isOptionSelected = $selectedChoiceValue !== null;

                    $isCompleted = $isInputOption ? $isInputFilled : $isOptionSelected;
                @endphp
                <div
                    class="p-4 bg-gray-50 rounded-lg border border-gray-200"
                    data-option-section
                    data-option-code="{{ $option['code'] }}"
                    wire:key="option-{{ $option['code'] }}"
                    x-data="{ open: {{ $isCompleted ? 'false' : 'true' }} }"
                >
                    <div class="flex items-center justify-between mb-3 cursor-pointer" @click="open = !open; if (open) $nextTick(() => setTimeout(() => $refs.firstChoice{{ $loop->index }}?.focus(), 50))">
                        <div class="flex items-center gap-2">
                            @if ($isCompleted)
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @else
                                <div class="w-5 h-5 border-2 border-gray-300 rounded-full"></div>
                            @endif
                            <span class="font-medium text-gray-900">
                                {{ $option['group_label'] ?? $option['label'] }}
                                @if ($isInputOption && $isInputFilled)
                                    <span class="ml-2 text-sm font-normal text-gray-500">
                                        — {{ $inputValue }}{{ !empty($option['unit']) ? ' ' . $option['unit'] : '' }}
                                    </span>
                                @elseif ($selectedChoiceValue && $hasChoices)
                                    @php
                                        $selectedChoiceLabel = collect($option['choices'])->firstWhere('value', $selectedChoiceValue)['label'] ?? '';
                                    @endphp
                                    @if ($selectedChoiceLabel)
                                        <span class="ml-2 text-sm font-normal text-gray-500">
                                            — {{ $selectedChoiceLabel }}
                                        </span>
                                    @endif
                                @endif
                            </span>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" x-bind:class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </div>

                    @php $optionIndex = $loop->index; @endphp
                    <div x-show="open" x-collapse class="pt-4">
                        @if ($hasChoices)
                            {{-- Visual Cards for choices - 3 columns --}}
                            <div class="grid grid-cols-3 gap-4">
                                @foreach ($option['choices'] as $choiceIndex => $choice)
                                    @php
                                        $isSelected = $selectedChoiceValue === $choice['value'];
                                        $isFirstChoice = $choiceIndex === 0;
                                        $hasValidImage = !empty($choice['image']) && is_string($choice['image']) && strlen(trim($choice['image'])) > 0 && !str_contains($choice['image'], 'data:image/gif;base64,R0lGOD');
                                        $choiceValues = collect($option['choices'])->pluck('value')->toArray();
                                        $choiceValuesJson = json_encode($choiceValues);
                                        $optionCode = addslashes($option['code']);
                                        $choiceValue = addslashes($choice['value']);
                                    @endphp
                                    <button
                                        type="button"
                                        @if($isFirstChoice) x-ref="firstChoice{{ $optionIndex }}" @endif
                                        wire:click="selectRadioOption('{{ $optionCode }}', '{{ $choiceValue }}', {{ $choiceValuesJson }})"
                                        wire:loading.attr="disabled"
                                        @click="setTimeout(() => { open = false }, 100)"
                                        @class([
                                            'relative flex flex-col border-2 rounded-xl transition-all duration-200 text-left overflow-hidden group cursor-pointer',
                                            'border-primary-500 bg-primary-50 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isSelected,
                                            'border-gray-200 bg-white hover:border-primary-400 hover:shadow-lg hover:scale-[1.02] hover:bg-gray-50' => !$isSelected,
                                        ])
                                    >
                                        {{-- Badge (e.g., "Most popular") --}}
                                        @if (!empty($choice['badge']))
                                            <div class="absolute top-2 left-2 z-10">
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-primary-100 text-primary-800">
                                                    {{ $choice['badge'] }}
                                                </span>
                                            </div>
                                        @endif

                                        {{-- Loading overlay - centered horizontally and vertically --}}
                                        <div
                                            wire:loading.flex
                                            wire:target="selectRadioOption"
                                            class="absolute top-0 left-0 right-0 bottom-0 bg-white/80 z-20 rounded-xl items-center justify-center"
                                        >
                                            <svg class="animate-spin w-6 h-6 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>

                                        @if ($hasValidImage)
                                            <div class="flex items-center justify-center p-4 bg-gray-50 border-b border-gray-100">
                                                <img
                                                    src="{{ $choice['image'] }}"
                                                    alt="{{ $choice['label'] }}"
                                                    class="object-contain w-full h-20 sm:h-24"
                                                    loading="lazy"
                                                />
                                            </div>
                                        @endif

                                        <div class="flex-1 p-4">
                                            <div class="flex items-start justify-between gap-2">
                                                <h5 @class([
                                                    'text-sm font-semibold',
                                                    'text-primary-700' => $isSelected,
                                                    'text-gray-900 group-hover:text-primary-600' => !$isSelected,
                                                ])>
                                                    {{ $choice['label'] }}
                                                </h5>
                                                {{-- Radio indicator --}}
                                                <div @class([
                                                    'flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center',
                                                    'border-primary-500 bg-primary-500' => $isSelected,
                                                    'border-gray-300 group-hover:border-primary-400' => !$isSelected,
                                                ])>
                                                    @if ($isSelected)
                                                        <div class="w-2 h-2 rounded-full bg-white"></div>
                                                    @endif
                                                </div>
                                            </div>
                                            @if (!empty($choice['description']))
                                                <p class="mt-2 text-xs text-gray-500 line-clamp-2">
                                                    {{ $choice['description'] }}
                                                </p>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach

                                {{-- Sibling input options --}}
                                @if (!empty($option['sibling_inputs']))
                                    @foreach ($option['sibling_inputs'] as $siblingIndex => $siblingInput)
                                        @php
                                            $siblingCode = $siblingInput['code'] ?? '';
                                            $siblingType = $siblingInput['type'] ?? 'number';
                                            $siblingValue = $this->getSelection($siblingCode) ?? $siblingInput['default'] ?? '';
                                            $isSiblingFilled = $this->hasSelection($siblingCode) && $this->getSelection($siblingCode) !== '';
                                            $hasSiblingImage = !empty($siblingInput['image']) && is_string($siblingInput['image']) && strlen(trim($siblingInput['image'])) > 0 && !str_contains($siblingInput['image'], 'data:image/gif;base64,R0lGOD');
                                        @endphp
                                        <div
                                            x-data="{ siblingValue: '{{ $siblingValue }}' }"
                                            @class([
                                                'relative flex flex-col border-2 rounded-xl transition-all duration-200 text-left overflow-hidden',
                                                'border-primary-500 bg-primary-50 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isSiblingFilled,
                                                'border-gray-200 bg-white hover:border-primary-400 hover:shadow-lg' => !$isSiblingFilled,
                                            ])
                                        >
                                            @if ($hasSiblingImage)
                                                <div class="flex items-center justify-center p-4 bg-gray-50 border-b border-gray-100">
                                                    <img
                                                        src="{{ $siblingInput['image'] }}"
                                                        alt="{{ $siblingInput['label'] }}"
                                                        class="object-contain w-full h-20 sm:h-24"
                                                        loading="lazy"
                                                    />
                                                </div>
                                            @endif
                                            <div class="flex-1 p-4">
                                                <h5 class="text-sm font-semibold text-gray-900 mb-1">
                                                    {{ $siblingInput['label'] }}
                                                </h5>
                                                @if (!empty($siblingInput['description']))
                                                    <p class="text-xs text-gray-500 mb-3">
                                                        {{ $siblingInput['description'] }}
                                                    </p>
                                                @endif
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        type="{{ $siblingType }}"
                                                        x-model="siblingValue"
                                                        min="{{ $siblingInput['min'] ?? '' }}"
                                                        max="{{ $siblingInput['max'] ?? '' }}"
                                                        step="{{ $siblingInput['step'] ?? 'any' }}"
                                                        placeholder="0"
                                                        class="w-20 border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                    />
                                                    @if (!empty($siblingInput['unit']))
                                                        <span class="text-sm text-gray-500">
                                                            {{ $siblingInput['unit'] }}
                                                        </span>
                                                    @endif
                                                    <button
                                                        type="button"
                                                        @click="$wire.selectOption('{{ $siblingCode }}', siblingValue); open = false;"
                                                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                                    >
                                                        Doorgaan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @elseif ($optionType === 'number')
                            {{-- Number input as card --}}
                            @php
                                $hasOptionImage = !empty($option['image']) && is_string($option['image']) && strlen(trim($option['image'])) > 0 && !str_contains($option['image'], 'data:image/gif;base64,R0lGOD');
                            @endphp
                            <div class="grid grid-cols-3 gap-4">
                                <div @class([
                                    'relative flex flex-col border-2 rounded-xl transition-all duration-200 text-left overflow-hidden',
                                    'border-primary-500 bg-primary-50 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isInputFilled,
                                    'border-gray-200 bg-white hover:border-primary-400 hover:shadow-lg' => !$isInputFilled,
                                ])>
                                    @if ($hasOptionImage)
                                        <div class="flex items-center justify-center p-4 bg-gray-50 border-b border-gray-100">
                                            <img
                                                src="{{ $option['image'] }}"
                                                alt="{{ $option['label'] }}"
                                                class="object-contain w-full h-24"
                                                loading="lazy"
                                            />
                                        </div>
                                    @endif

                                    <div class="p-4">
                                        <h5 class="text-sm font-semibold text-gray-900 mb-1">
                                            {{ $option['label'] }}
                                        </h5>

                                        @if (!empty($option['description']))
                                            <p class="text-xs text-gray-500 mb-3">
                                                {{ $option['description'] }}
                                            </p>
                                        @endif

                                        <div class="flex items-center gap-2" x-data="{ inputValue: '{{ $inputValue ?? $option['default'] ?? $option['min'] ?? '' }}' }">
                                            <input
                                                type="number"
                                                x-model="inputValue"
                                                min="{{ $option['min'] ?? '' }}"
                                                max="{{ $option['max'] ?? '' }}"
                                                step="{{ $option['step'] ?? 'any' }}"
                                                placeholder="{{ $option['placeholder'] ?? '0' }}"
                                                class="w-20 border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            />
                                            @if (!empty($option['unit']))
                                                <span class="text-sm text-gray-500">
                                                    {{ $option['unit'] }}
                                                </span>
                                            @endif
                                            <button
                                                type="button"
                                                @click="$wire.selectOption('{{ $option['code'] }}', inputValue); open = false;"
                                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                            >
                                                Doorgaan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($optionType === 'text')
                            {{-- Text input as card --}}
                            <div class="grid grid-cols-3 gap-4">
                                <div @class([
                                    'relative flex flex-col border-2 rounded-xl transition-all duration-200 text-left overflow-hidden p-4',
                                    'border-primary-500 bg-primary-50 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isInputFilled,
                                    'border-gray-200 bg-white hover:border-primary-400 hover:shadow-lg' => !$isInputFilled,
                                ])>
                                    <div class="flex flex-col gap-2">
                                        <label for="text-input-{{ $option['code'] }}" class="text-xs font-medium text-gray-500">
                                            {{ $option['label'] }}
                                        </label>
                                        <input
                                            type="text"
                                            id="text-input-{{ $option['code'] }}"
                                            x-on:change="$wire.selectOption('{{ $option['code'] }}', $event.target.value); open = false;"
                                            value="{{ $inputValue ?? '' }}"
                                            maxlength="{{ $option['maxLength'] ?? '' }}"
                                            placeholder="{{ $option['placeholder'] ?? $option['label'] }}"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        />
                                    </div>
                                    @if (!empty($option['description']))
                                        <p class="mt-2 text-xs text-gray-500">
                                            {{ $option['description'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Cross-sell Groups --}}
            @foreach ($crossSellGroups as $groupCode => $crossSellProducts)
                @php
                    $groupLabel = $crossSellProducts->first()['group_label'] ?? 'Accessoires';

                    $selectedCrossSells = $crossSellProducts->filter(function($product) {
                        $value = $this->getSelection($product['code']);
                        return $value !== null && $value !== 'skip' && $value > 0;
                    });
                    $isGroupSkipped = $this->getSelection($groupCode . '_skip') === 'skip';

                    $hasGroupSelection = $isGroupSkipped || $selectedCrossSells->isNotEmpty();

                    $selectionSummary = $selectedCrossSells->map(function($product) {
                        $amount = (int)($this->getSelection($product['code']) ?? 0);
                        return $amount . 'x ' . $product['label'];
                    })->implode(', ');
                @endphp
                <div
                    class="p-4 bg-gray-50 rounded-lg border border-gray-200"
                    data-option-section
                    x-data="{ open: {{ $hasGroupSelection ? 'false' : 'true' }} }"
                    x-init="if (open) $nextTick(() => setTimeout(() => $refs.crossSellSkip{{ $loop->index }}?.focus(), 50))"
                >
                    <div class="flex items-center justify-between mb-3 cursor-pointer" @click="open = !open; if (open) $nextTick(() => setTimeout(() => $refs.crossSellSkip{{ $loop->index }}?.focus(), 50))">
                        <div class="flex items-center gap-2">
                            @if ($hasGroupSelection)
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @else
                                <div class="w-5 h-5 border-2 border-gray-300 rounded-full"></div>
                            @endif
                            <span class="font-medium text-gray-900">
                                {{ $groupLabel }}
                                @if ($selectionSummary)
                                    <span class="ml-2 text-sm font-normal text-gray-500">
                                        — {{ $selectionSummary }}
                                    </span>
                                @endif
                            </span>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform" x-bind:class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </div>

                    <div x-show="open" x-collapse class="pt-4">
                        {{-- Skip option card + Product cards grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                            {{-- Skip option card --}}
                            <button
                                type="button"
                                x-ref="crossSellSkip{{ $loop->index }}"
                                wire:click="skipCrossSellGroup('{{ $groupCode }}')"
                                @click="open = false; $nextTick(() => document.getElementById('pricing-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                                @class([
                                    'relative flex flex-col items-center justify-center border-2 rounded-xl transition-all duration-200 p-6 min-h-[200px] cursor-pointer',
                                    'border-primary-500 bg-primary-50 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isGroupSkipped,
                                    'border-dashed border-gray-300 bg-white hover:border-primary-400 hover:shadow-lg' => !$isGroupSkipped,
                                ])
                            >
                                <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                </svg>
                                <span class="text-sm font-medium text-gray-600">Geen accessoires</span>
                            </button>

                            {{-- Product cards --}}
                            @foreach ($crossSellProducts as $product)
                                @php
                                    $hasProductImage = !empty($product['image']) && is_string($product['image']) && strlen(trim($product['image'])) > 0 && !str_contains($product['image'], 'data:image/gif;base64,R0lGOD');
                                    $productAmount = $this->getSelection($product['code']);
                                    $isProductSelected = $productAmount && $productAmount !== 'skip' && (int)$productAmount > 0;
                                    $currentAmount = $isProductSelected ? (int)$productAmount : ($product['default'] ?? 1);

                                    $priceValue = null;
                                    if (isset($product['price'])) {
                                        if (is_array($product['price'])) {
                                            $priceValue = $product['price']['purchase_price'] ?? $product['price']['purchase_base_price'] ?? null;
                                        } else {
                                            $priceValue = $product['price'] / 100;
                                        }
                                    }
                                    $priceFormatted = $priceValue !== null ? number_format($priceValue, 2) : null;
                                @endphp
                                <div
                                    x-data="{ amount: {{ $currentAmount }}, selected: {{ $isProductSelected ? 'true' : 'false' }} }"
                                    @class([
                                        'relative flex flex-col border-2 rounded-xl transition-all duration-200 overflow-hidden',
                                        'border-primary-500 bg-primary-50 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isProductSelected,
                                        'border-gray-200 bg-white hover:border-primary-400 hover:shadow-lg' => !$isProductSelected,
                                    ])
                                >
                                    {{-- Product image --}}
                                    @if ($hasProductImage)
                                        <div
                                            class="flex items-center justify-center p-4 bg-gray-50 border-b border-gray-100 cursor-pointer"
                                            @click="if (!selected) { selected = true; $wire.selectCrossSell('{{ $product['code'] }}', amount); }"
                                        >
                                            <img
                                                src="{{ $product['image'] }}"
                                                alt="{{ $product['label'] }}"
                                                class="object-contain w-full h-24"
                                                loading="lazy"
                                            />
                                        </div>
                                    @endif

                                    <div class="p-4 flex flex-col flex-1">
                                        {{-- Amount selector (shown when product is selected) --}}
                                        <div x-show="selected" x-collapse class="mb-3">
                                            <span class="block text-xs font-medium text-gray-500 mb-2 text-center">Aantal</span>
                                            <div class="flex items-center justify-center gap-1">
                                                <button
                                                    type="button"
                                                    @click.stop="if (amount > 1) { amount--; $wire.selectCrossSell('{{ $product['code'] }}', amount); } else { selected = false; $wire.selectCrossSell('{{ $product['code'] }}', 0); }"
                                                    class="flex items-center justify-center w-8 h-8 text-gray-600 bg-gray-200 rounded-l-md hover:bg-gray-300"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                    </svg>
                                                </button>
                                                <input
                                                    type="number"
                                                    x-model="amount"
                                                    @change="$wire.selectCrossSell('{{ $product['code'] }}', parseInt(amount))"
                                                    min="1"
                                                    class="w-12 h-8 text-sm text-center border-gray-300 border-y focus:border-primary-500 focus:ring-0"
                                                />
                                                <button
                                                    type="button"
                                                    @click.stop="amount++; $wire.selectCrossSell('{{ $product['code'] }}', amount);"
                                                    class="flex items-center justify-center w-8 h-8 text-gray-600 bg-gray-200 rounded-r-md hover:bg-gray-300"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Product name --}}
                                        <h5
                                            class="text-sm font-semibold text-gray-900 mb-1 cursor-pointer"
                                            @click="if (!selected) { selected = true; $wire.selectCrossSell('{{ $product['code'] }}', amount); }"
                                        >
                                            {{ $product['label'] }}
                                        </h5>

                                        {{-- Price --}}
                                        @if ($priceFormatted)
                                            <p class="text-sm text-primary-600 font-medium mt-auto">
                                                € {{ $priceFormatted }} / {{ $product['unit'] ?? 'st' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Continue button --}}
                        <div class="mt-4 flex justify-end">
                            <button
                                type="button"
                                @click="open = false"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                            >
                                Doorgaan
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Loading indicator --}}
            <div wire:loading wire:target="selectOption, selectRadioOption, selectDimensions" class="flex items-center justify-center py-4">
                <svg class="animate-spin w-6 h-6 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

        </div>

    @endif

    {{-- Price Summary (when canOrder is true) --}}
    @if ($this->canOrder)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="pricing-section">
        {{-- Left Column: Summary --}}
        <div class="p-5 border border-gray-200 rounded-xl bg-white">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Samenvatting</h3>

            {{-- Configuration Details --}}
            <div class="space-y-2 mb-4">
                @foreach ($this->configurationSummary as $label => $value)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">{{ $label }}</span>
                        <span class="font-medium text-gray-900">{{ $value }}</span>
                    </div>
                @endforeach
            </div>

            <hr class="my-4 border-gray-200">

            {{-- Pricing Breakdown --}}
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Verzendkosten (vanaf)</span>
                    <span class="font-medium text-gray-900">€ {{ $this->formattedShippingPrice ?? '—' }}</span>
                </div>
            </div>

            <hr class="my-4 border-gray-200">

            {{-- Total --}}
            <div class="flex justify-between items-baseline">
                <span class="text-gray-700 font-medium">Totaal excl. btw</span>
                <span class="text-2xl font-bold text-primary-600">€ {{ $this->formattedSellPrice }}</span>
            </div>

            <p class="mt-3 text-xs text-gray-500">
                <svg class="inline w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                Vraag meer aan = lagere stuksprijs!
            </p>
        </div>

        {{-- Right Column: Delivery Dates --}}
        <div class="p-5 border border-gray-200 rounded-xl bg-white">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Beschikbare leverdatums</h3>

            @if (count($this->deliveryOptions) > 0)
                <div class="space-y-3">
                    @foreach ($this->deliveryOptions as $option)
                        <div @class([
                            'flex items-center justify-between p-3 rounded-lg',
                            'bg-green-50 border border-green-200' => $option['is_standard'],
                            'bg-gray-50' => !$option['is_standard'],
                        ])>
                            <div class="flex items-center gap-3">
                                <svg @class([
                                    'w-5 h-5',
                                    'text-green-600' => $option['is_standard'],
                                    'text-orange-500' => $option['is_express'],
                                    'text-gray-500' => !$option['is_standard'] && !$option['is_express'],
                                ]) fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="font-medium text-gray-900">{{ $option['formatted_date'] }}</span>
                            </div>
                            <span @class([
                                'text-sm font-medium',
                                'text-green-600' => $option['rush_surcharge'] == 0,
                                'text-gray-700' => $option['rush_surcharge'] > 0,
                            ])>
                                @if ($option['rush_surcharge'] > 0)
                                    + € {{ number_format($option['rush_surcharge'], 2, ',', '.') }}
                                @else
                                    Geen spoedkosten
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>

                <p class="mt-4 text-xs text-gray-500">
                    Je kiest de gewenste datum tijdens het afronden van je bestelling.
                </p>
            @else
                <p class="text-sm text-gray-500">
                    Leverdata worden berekend bij het afrekenen.
                </p>
            @endif
        </div>
    </div>
    @else
    {{-- Simple pricing display when configuration incomplete --}}
    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Prijs</h3>
        @if($this->formattedSellPrice)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Prijs:</span>
                <span class="text-2xl font-bold text-primary-600">€ {{ $this->formattedSellPrice }}</span>
            </div>
        @else
            <p class="text-sm text-gray-500">Voltooi de configuratie om de prijs te zien.</p>
        @endif
    </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex flex-col gap-3">
        {{-- Add to Cart Button --}}
        <button
            type="button"
            wire:click="addToCart"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-75 cursor-not-allowed"
            @class([
                'inline-flex items-center justify-center px-5 py-3 text-base font-medium rounded-lg transition-all duration-200',
                'bg-primary-600 text-white hover:bg-primary-700 focus:ring-4 focus:ring-primary-300' => $this->canOrder,
                'bg-gray-300 text-gray-500 cursor-not-allowed' => !$this->canOrder,
            ])
            @if(!$this->canOrder) disabled @endif
        >
            <span wire:loading.remove wire:target="addToCart">
                @if($added)
                    <svg class="w-5 h-5 -ms-2 me-2 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                @else
                    <svg class="w-5 h-5 -ms-2 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                @endif
            </span>
            <svg wire:loading wire:target="addToCart" class="animate-spin w-5 h-5 -ms-2 me-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            @if($added)
                Toegevoegd!
            @elseif($this->canOrder)
                Toevoegen aan winkelwagen
            @else
                Voltooi configuratie
            @endif
        </button>

        {{-- Reset Button --}}
        @if(count(array_filter($selections ?? [])) > 0)
            <button
                type="button"
                wire:click="resetConfigurator"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-100"
            >
                <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Opnieuw beginnen
            </button>
        @endif
    </div>
</div>
