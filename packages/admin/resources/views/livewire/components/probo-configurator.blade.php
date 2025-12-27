<div
    class="space-y-4"
    wire:loading.class="opacity-60 pointer-events-none"
    x-data="{ initialized: false }"
    x-init="setTimeout(() => { initialized: true; $dispatch('configurator-ready'); }, 100)"
    @configurator-saved.window="
        const closeBtn = document.querySelector('.fi-modal-close-btn');
        if (closeBtn) closeBtn.click();
    "
    @scroll-to-next-option.window="$nextTick(() => {
        setTimeout(() => {
            const sections = $el.querySelectorAll('[data-option-section]');
            for (const section of sections) {
                // Check if section is visually open by looking at the x-show content
                const content = section.querySelector('[x-show=\"open\"]');
                const isOpen = content && !content.hasAttribute('hidden') && getComputedStyle(content).display !== 'none';

                // Also check Alpine data as fallback
                const alpineData = section._x_dataStack?.[0];
                const alpineOpen = alpineData && alpineData.open === true;

                if (isOpen || alpineOpen) {
                    const container = section.closest('.fi-modal-content') || section.closest('.overflow-y-auto') || document.querySelector('.fi-modal-window');
                    if (container) {
                        const rect = section.getBoundingClientRect();
                        const containerRect = container.getBoundingClientRect();
                        const scrollTop = container.scrollTop + (rect.top - containerRect.top) - 20;
                        container.scrollTo({ top: scrollTop, behavior: 'smooth' });
                    } else {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    break;
                }
            }
        }, 150);
    })"
>
    {{-- Error Message --}}
    @if ($errorMessage)
        <div class="p-4 text-sm text-red-700 bg-red-100 border border-red-200 rounded-lg dark:bg-red-900/20 dark:text-red-400 dark:border-red-800">
            {{ $errorMessage }}
        </div>
    @endif

    @if (!$configurationData && $isLoading)
        {{-- Initial Loading State (only shown when no data yet) --}}
        <div class="flex items-center justify-center p-8">
            <x-filament::loading-indicator class="w-8 h-8" />
            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('lunarpanel::product.configurator.loading') }}
            </span>
        </div>
    @else

        <div class="space-y-4">
            @php
                // Group options by type for better display
                // Dimension options: width, height, length codes OR number type with unit (cm, mm, etc.)
                // Only if they are actual number inputs (not selects with children)
                $dimensionCodes = ['width', 'height', 'length'];
                $dimensionOptions = collect($this->availableOptions)->filter(function($o) use ($dimensionCodes) {
                    $code = $o['code'] ?? '';
                    $type = $o['type'] ?? 'select';
                    $hasUnit = !empty($o['unit']);
                    $hasChoices = !empty($o['choices']);
                    // Only match dimensions if they are number inputs (not selects)
                    if ($hasChoices) return false; // If it has choices, it's a select, not a dimension input
                    return in_array($code, $dimensionCodes) || ($type === 'number' && $hasUnit && in_array($o['unit'], ['cm', 'mm', 'm', 'inch', 'in']));
                });

                // Check if dimensions have been filled in
                $dimensionsCompleted = isset($selections['width']) && $selections['width'] > 0
                    && isset($selections['height']) && $selections['height'] > 0;

                // Quantity option: code 'amount' or 'quantity' BUT only if it's a number type (not a select with choices)
                // Only show quantity AFTER dimensions have been completed
                $quantityOption = $dimensionsCompleted ? collect($this->availableOptions)->first(function($o) {
                    $code = $o['code'] ?? '';
                    $type = $o['type'] ?? 'select';
                    $hasChoices = !empty($o['choices']);
                    // Only treat as quantity input if it's NOT a select with choices
                    return in_array($code, ['amount', 'quantity']) && $type === 'number' && !$hasChoices;
                }) : null;

                // Configurable options: all options except dimensions, quantity, and cross-sell
                // Cross-sell options are grouped separately
                $configurableOptions = collect($this->availableOptions)->filter(function($o) use ($dimensionCodes) {
                    $code = $o['code'] ?? '';
                    $type = $o['type'] ?? 'select';
                    $hasChoices = !empty($o['choices']);

                    // Exclude only actual dimension options by code (width, height, length)
                    if (in_array($code, $dimensionCodes)) return false;
                    // Exclude quantity options
                    if (in_array($code, ['amount', 'quantity']) && $type === 'number' && !$hasChoices) return false;
                    // Exclude cross-sell options (handled separately as groups)
                    if ($type === 'cross_sell') return false;

                    // Include everything else: selects with choices, input options
                    return $hasChoices || in_array($type, ['number', 'text']);
                });

                // Group cross-sell options by their group_code
                $crossSellGroups = collect($this->availableOptions)
                    ->filter(fn($o) => ($o['type'] ?? '') === 'cross_sell')
                    ->groupBy(fn($o) => $o['group_code'] ?? 'cross-sells');
            @endphp

            {{-- Debug: Show dimension options --}}
            {{-- @if (config('app.debug'))
                <details class="p-3 text-xs bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg mb-4">
                    <summary class="cursor-pointer font-medium text-amber-800 dark:text-amber-300">
                        Debug: Dimension ({{ $dimensionOptions->count() }}) | Configurable ({{ $configurableOptions->count() }}) | CrossSell groups ({{ $crossSellGroups->count() }}) | All ({{ collect($this->availableOptions)->count() }}) | Dims completed: {{ $dimensionsCompleted ? 'Yes' : 'No' }}
                    </summary>
                    <div class="mt-3 space-y-2">
                        <div>
                            <span class="font-semibold text-amber-700 dark:text-amber-400">Dimensions:</span>
                            <span class="text-gray-700 dark:text-gray-300">W={{ $selections['width'] ?? 'not set' }} × H={{ $selections['height'] ?? 'not set' }} (completed: {{ $dimensionsCompleted ? 'yes' : 'no' }})</span>
                        </div>
                        <div>
                            <span class="font-semibold text-amber-700 dark:text-amber-400">Quantity option:</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $quantityOption ? $quantityOption['code'] : 'hidden (waiting for dimensions)' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-amber-700 dark:text-amber-400">All option codes:</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ collect($this->availableOptions)->pluck('code')->implode(', ') ?: 'none' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-amber-700 dark:text-amber-400">Configurable options:</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $configurableOptions->pluck('code')->implode(', ') ?: 'none' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-amber-700 dark:text-amber-400">Cross-sell groups:</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $crossSellGroups->keys()->implode(', ') ?: 'none' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-amber-700 dark:text-amber-400">Options with choices:</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ collect($this->availableOptions)->filter(fn($o) => !empty($o['choices']))->pluck('code')->implode(', ') ?: 'none' }}</span>
                        </div>
                    </div>
                </details>
            @endif --}}

            {{-- Dimensions Section (Width × Height) --}}
            @if ($dimensionOptions->isNotEmpty())
                @php
                    // Common size presets (width x height in cm)
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
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ __('lunarpanel::product.configurator.dimensions') }}
                        </span>
                    </div>

                    {{-- Size Presets --}}
                    <div class="mb-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ __('lunarpanel::product.configurator.size_presets') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($sizePresets as $preset)
                                <button
                                    type="button"
                                    wire:click="selectDimensions({{ $preset['width'] }}, {{ $preset['height'] }})"
                                    @class([
                                        'px-3 py-1.5 text-xs font-medium rounded-full transition-colors',
                                        'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' =>
                                            (($selections['width'] ?? null) == $preset['width'] && ($selections['height'] ?? null) == $preset['height']),
                                        'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' =>
                                            !(($selections['width'] ?? null) == $preset['width'] && ($selections['height'] ?? null) == $preset['height']),
                                    ])
                                >
                                    {{ $preset['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Custom Dimensions Input --}}
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ __('lunarpanel::product.configurator.custom_size') }}</p>
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
                                    class="w-24 text-center border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $option['unit'] ?? 'cm' }}
                                </span>
                            </div>
                            @if (!$loop->last)
                                <span class="text-gray-400">×</span>
                            @endif
                        @endforeach

                        {{-- Calculate and show area if both width and height are set --}}
                        @if (isset($selections['width']) && isset($selections['height']))
                            @php
                                $area = ($selections['width'] * $selections['height']) / 10000; // cm² to m²
                            @endphp
                            <span class="ml-4 text-sm text-gray-500 dark:text-gray-400">
                                Totaal: {{ number_format($area, 2) }} m²
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Quantity Section (only shown after dimensions are completed) --}}
            @if ($quantityOption)
                @php
                    $currentAmount = (int) ($selections['amount'] ?? 1);
                @endphp
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700" x-data="{ amount: {{ $currentAmount }} }" x-init="$nextTick(() => $refs.amountInput?.focus())">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if (isset($selections['amount']) && $selections['amount'] > 0)
                                <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                            @else
                                <div class="w-5 h-5 border-2 border-gray-300 rounded-full dark:border-gray-600"></div>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $quantityOption['label'] ?? __('lunarpanel::product.configurator.quantity') }}
                            </span>
                        </div>
                        <div class="inline-flex items-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm">
                            <button
                                type="button"
                                @click="if (amount > 1) { amount--; $wire.selectOption('amount', amount); }"
                                class="flex items-center justify-center w-10 h-10 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-l-lg transition-colors"
                            >
                                <x-heroicon-m-minus class="w-4 h-4" />
                            </button>
                            <input
                                type="number"
                                x-ref="amountInput"
                                x-model="amount"
                                x-on:change="$wire.selectOption('amount', parseInt(amount) || 1)"
                                x-on:keydown.enter="$wire.selectOption('amount', parseInt(amount) || 1)"
                                min="1"
                                class="w-14 h-10 text-center text-sm font-medium border-x border-gray-300 dark:border-gray-600 bg-transparent text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                            />
                            <button
                                type="button"
                                @click="amount++; $wire.selectOption('amount', amount);"
                                class="flex items-center justify-center w-10 h-10 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-r-lg transition-colors"
                            >
                                <x-heroicon-m-plus class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Configurable Options (Select and Input options in original API order) --}}
            @foreach ($configurableOptions as $option)
                @php
                    $hasChoices = !empty($option['choices']);
                    $optionType = $option['type'] ?? 'select';
                    $isInputOption = in_array($optionType, ['number', 'text']) && !$hasChoices;
                    $isCrossSell = $optionType === 'cross_sell';

                    // For input options: check if filled
                    $isInputFilled = $isInputOption && isset($selections[$option['code']]) && $selections[$option['code']] !== '';

                    // For cross-sell options: check if selected or skipped
                    $crossSellValue = $selections[$option['code']] ?? null;
                    $isCrossSellCompleted = $isCrossSell && $crossSellValue !== null;
                    $isCrossSellSkipped = $isCrossSell && $crossSellValue === 'skip';
                    $crossSellAmount = $isCrossSell && $crossSellValue && $crossSellValue !== 'skip' ? (int)$crossSellValue : 0;

                    // For select options: check if any choice is selected
                    $isOptionSelected = isset($selections[$option['code']]);
                    $selectedChoiceValue = null;
                    if ($hasChoices) {
                        foreach ($option['choices'] as $choice) {
                            if (isset($selections[$choice['value']])) {
                                $isOptionSelected = true;
                                $selectedChoiceValue = $choice['value'];
                                break;
                            }
                        }
                    }

                    // Determine if section should be open
                    $isCompleted = $isCrossSell ? $isCrossSellCompleted : ($isInputOption ? $isInputFilled : $isOptionSelected);
                @endphp
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700" data-option-section x-data="{ open: {{ $isCompleted ? 'false' : 'true' }} }" x-init="if (open) $nextTick(() => setTimeout(() => $refs.firstChoice{{ $loop->index }}?.focus(), 50))">
                    <div class="flex items-center justify-between mb-3 cursor-pointer" @click="open = !open; if (open) $nextTick(() => setTimeout(() => $refs.firstChoice{{ $loop->index }}?.focus(), 50))">
                        <div class="flex items-center gap-2">
                            @if ($isCompleted)
                                <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                            @else
                                <div class="w-5 h-5 border-2 border-gray-300 rounded-full dark:border-gray-600"></div>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $option['group_label'] ?? $option['label'] }}
                                @if ($isCrossSell && $isCrossSellCompleted)
                                    @if ($isCrossSellSkipped)
                                        {{-- No indicator needed for skipped --}}
                                    @else
                                        <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                            — {{ $crossSellAmount }}x {{ $option['label'] }}
                                        </span>
                                    @endif
                                @elseif ($isInputOption && $isInputFilled)
                                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                        — {{ $selections[$option['code']] }}{{ !empty($option['unit']) ? ' ' . $option['unit'] : '' }}
                                    </span>
                                @elseif ($selectedChoiceValue && $hasChoices)
                                    @php
                                        $selectedChoiceLabel = collect($option['choices'])->firstWhere('value', $selectedChoiceValue)['label'] ?? '';
                                    @endphp
                                    @if ($selectedChoiceLabel)
                                        <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                            — {{ $selectedChoiceLabel }}
                                        </span>
                                    @endif
                                @endif
                            </span>
                        </div>
                        <x-heroicon-m-chevron-up class="w-5 h-5 text-gray-400 transition-transform" x-bind:class="{ 'rotate-180': !open }" />
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
                                    @endphp
                                    <button
                                        type="button"
                                        @if($isFirstChoice) x-ref="firstChoice{{ $optionIndex }}" @endif
                                        wire:click="selectRadioOption('{{ $option['code'] }}', '{{ $choice['value'] }}', {{ json_encode(collect($option['choices'])->pluck('value')->toArray()) }})"
                                        wire:loading.attr="disabled"
                                        @click="open = false"
                                        @class([
                                            'relative flex flex-col border-2 rounded-xl transition-all duration-200 text-left overflow-hidden group cursor-pointer',
                                            'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isSelected,
                                            'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-400 dark:hover:border-primary-500 hover:shadow-lg hover:scale-[1.02] hover:bg-gray-50 dark:hover:bg-gray-750' => !$isSelected,
                                        ])
                                    >
                                        {{-- Loading overlay for this specific button --}}
                                        <div
                                            wire:loading
                                            wire:target="selectRadioOption('{{ $option['code'] }}', '{{ $choice['value'] }}')"
                                            class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 flex items-center justify-center z-10 rounded-xl"
                                        >
                                            <x-filament::loading-indicator class="w-6 h-6 text-primary-500" />
                                        </div>
                                        @php
                                            $hasValidImage = !empty($choice['image']) && is_string($choice['image']) && strlen(trim($choice['image'])) > 0 && !str_contains($choice['image'], 'data:image/gif;base64,R0lGOD');
                                        @endphp
                                        @if ($hasValidImage)
                                            <div class="flex items-center justify-center p-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
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
                                                    'text-primary-700 dark:text-primary-400' => $isSelected,
                                                    'text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400' => !$isSelected,
                                                ])>
                                                    {{ $choice['label'] }}
                                                </h5>
                                                {{-- Radio indicator --}}
                                                <div @class([
                                                    'flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center',
                                                    'border-primary-500 bg-primary-500' => $isSelected,
                                                    'border-gray-300 dark:border-gray-600 group-hover:border-primary-400' => !$isSelected,
                                                ])>
                                                    @if ($isSelected)
                                                        <div class="w-2 h-2 rounded-full bg-white"></div>
                                                    @endif
                                                </div>
                                            </div>
                                            @if (!empty($choice['description']))
                                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                                                    {{ $choice['description'] }}
                                                </p>
                                            @endif
                                        </div>
                                    </button>
                                @endforeach

                                {{-- Sibling input options (e.g., "Ringen aangegeven in bestand" for position-rings) --}}
                                @if (!empty($option['sibling_inputs']))
                                    @foreach ($option['sibling_inputs'] as $siblingIndex => $siblingInput)
                                        @php
                                            $siblingCode = $siblingInput['code'] ?? '';
                                            $siblingType = $siblingInput['type'] ?? 'number';
                                            $siblingValue = $selections[$siblingCode] ?? $siblingInput['default'] ?? '';
                                            $isSiblingFilled = isset($selections[$siblingCode]) && $selections[$siblingCode] !== '';
                                            $hasSiblingImage = !empty($siblingInput['image']) && is_string($siblingInput['image']) && strlen(trim($siblingInput['image'])) > 0 && !str_contains($siblingInput['image'], 'data:image/gif;base64,R0lGOD');
                                            $isFirstSibling = $siblingIndex === 0;
                                        @endphp
                                        <div
                                            x-data="{ siblingValue: '{{ $siblingValue }}' }"
                                            x-init="@if($isFirstSibling) $nextTick(() => { if (open) $refs.siblingInput{{ $siblingIndex }}.focus(); }); $watch('open', (value) => { if (value) $nextTick(() => $refs.siblingInput{{ $siblingIndex }}.focus()); }) @endif"
                                            @class([
                                                'relative flex flex-col border-2 rounded-xl transition-all duration-200 text-left overflow-hidden',
                                                'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isSiblingFilled,
                                                'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-400 dark:hover:border-primary-500 hover:shadow-lg' => !$isSiblingFilled,
                                            ])
                                        >
                                            @if ($hasSiblingImage)
                                                <div class="flex items-center justify-center p-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                                    <img
                                                        src="{{ $siblingInput['image'] }}"
                                                        alt="{{ $siblingInput['label'] }}"
                                                        class="object-contain w-full h-20 sm:h-24"
                                                        loading="lazy"
                                                    />
                                                </div>
                                            @endif
                                            <div class="flex-1 p-4">
                                                <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                                                    {{ $siblingInput['label'] }}
                                                </h5>
                                                @if (!empty($siblingInput['description']))
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                                        {{ $siblingInput['description'] }}
                                                    </p>
                                                @endif
                                                <div class="flex items-center gap-2">
                                                    @if ($siblingType === 'number')
                                                        <input
                                                            type="number"
                                                            x-ref="siblingInput{{ $siblingIndex }}"
                                                            x-model="siblingValue"
                                                            min="{{ $siblingInput['min'] ?? '' }}"
                                                            max="{{ $siblingInput['max'] ?? '' }}"
                                                            step="{{ $siblingInput['step'] ?? 'any' }}"
                                                            placeholder="0"
                                                            class="w-20 border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                                                        />
                                                        @if (!empty($siblingInput['unit']))
                                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                                {{ $siblingInput['unit'] }}
                                                            </span>
                                                        @endif
                                                    @else
                                                        <input
                                                            type="text"
                                                            x-ref="siblingInput{{ $siblingIndex }}"
                                                            x-model="siblingValue"
                                                            placeholder="{{ $siblingInput['label'] }}"
                                                            class="flex-1 border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                                                        />
                                                    @endif
                                                    <button
                                                        type="button"
                                                        @click="$wire.selectOption('{{ $siblingCode }}', siblingValue); open = false;"
                                                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                                    >
                                                        {{ __('lunarpanel::product.configurator.cross_sell.continue') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @elseif ($optionType === 'number')
                            {{-- Number input as card in grid-3 layout with image like Probo's design --}}
                            @php
                                $hasOptionImage = !empty($option['image']) && is_string($option['image']) && strlen(trim($option['image'])) > 0 && !str_contains($option['image'], 'data:image/gif;base64,R0lGOD');
                            @endphp
                            <div class="grid grid-cols-3 gap-4">
                                <div @class([
                                    'relative flex flex-col border-2 rounded-xl transition-all duration-200 text-left overflow-hidden',
                                    'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isInputFilled,
                                    'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-400 dark:hover:border-primary-500 hover:shadow-lg' => !$isInputFilled,
                                ])>
                                    {{-- Option image if available --}}
                                    @if ($hasOptionImage)
                                        <div class="flex items-center justify-center p-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
                                            <img
                                                src="{{ $option['image'] }}"
                                                alt="{{ $option['label'] }}"
                                                class="object-contain w-full h-24"
                                                loading="lazy"
                                            />
                                        </div>
                                    @endif

                                    <div class="p-4">
                                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                                            {{ $option['label'] }}
                                        </h5>

                                        @if (!empty($option['description']))
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                                {{ $option['description'] }}
                                            </p>
                                        @endif

                                        <div class="flex items-center gap-2" x-data="{ inputValue: '{{ $selections[$option['code']] ?? $option['default'] ?? $option['min'] ?? '' }}' }">
                                            <input
                                                type="number"
                                                id="input-number-{{ $option['code'] }}"
                                                x-model="inputValue"
                                                min="{{ $option['min'] ?? '' }}"
                                                max="{{ $option['max'] ?? '' }}"
                                                step="{{ $option['step'] ?? 'any' }}"
                                                placeholder="{{ $option['placeholder'] ?? '0' }}"
                                                class="w-20 border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                                            />
                                            @if (!empty($option['unit']))
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $option['unit'] }}
                                                </span>
                                            @endif
                                            <button
                                                type="button"
                                                @click="$wire.selectOption('{{ $option['code'] }}', inputValue); open = false;"
                                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                            >
                                                Continue
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif ($optionType === 'text')
                            {{-- Text input as card in grid-3 layout --}}
                            <div class="grid grid-cols-3 gap-4">
                                <div @class([
                                    'relative flex flex-col border-2 rounded-xl transition-all duration-200 text-left overflow-hidden p-4',
                                    'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isInputFilled,
                                    'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-400 dark:hover:border-primary-500 hover:shadow-lg' => !$isInputFilled,
                                ])>
                                    <div class="flex flex-col gap-2">
                                        <label for="input-text-{{ $option['code'] }}" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                            {{ $option['label'] }}
                                        </label>
                                        <input
                                            type="text"
                                            id="input-text-{{ $option['code'] }}"
                                            x-on:change="$wire.selectOption('{{ $option['code'] }}', $event.target.value); open = false;"
                                            value="{{ $selections[$option['code']] ?? '' }}"
                                            maxlength="{{ $option['maxLength'] ?? '' }}"
                                            placeholder="{{ $option['placeholder'] ?? $option['label'] }}"
                                            class="w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                                        />
                                    </div>
                                    @if (!empty($option['description']))
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $option['description'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @else
                            {{-- Fallback: show message --}}
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                                Complete previous options to see available choices.
                            </p>
                            {{-- @if (config('app.debug'))
                                <details class="mt-2 text-xs text-gray-400">
                                    <summary>Debug: Option data</summary>
                                    <pre class="mt-1 p-2 bg-gray-100 dark:bg-gray-900 rounded overflow-auto">{{ json_encode($option, JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            @endif --}}
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Cross-sell Groups --}}
            @foreach ($crossSellGroups as $groupCode => $crossSellProducts)
                @php
                    $groupLabel = $crossSellProducts->first()['group_label'] ?? 'Accessories';

                    // Check if any cross-sell in this group has a selection
                    $selectedCrossSells = $crossSellProducts->filter(function($product) use ($selections) {
                        $value = $selections[$product['code']] ?? null;
                        return $value !== null && $value !== 'skip' && $value > 0;
                    });
                    $isGroupSkipped = isset($selections[$groupCode . '_skip']) && $selections[$groupCode . '_skip'] === 'skip';

                    // Check if user has made any selection in this group (either skip or selected products)
                    $hasGroupSelection = $isGroupSkipped || $selectedCrossSells->isNotEmpty();

                    // Build selection summary for header
                    $selectionSummary = $selectedCrossSells->map(function($product) use ($selections) {
                        $amount = (int)($selections[$product['code']] ?? 0);
                        return $amount . 'x ' . $product['label'];
                    })->implode(', ');
                @endphp
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700" data-option-section x-data="{ open: {{ $hasGroupSelection ? 'false' : 'true' }} }" x-init="if (open) $nextTick(() => setTimeout(() => $refs.crossSellSkip{{ $loop->index }}?.focus(), 50))">
                    <div class="flex items-center justify-between mb-3 cursor-pointer" @click="open = !open; if (open) $nextTick(() => setTimeout(() => $refs.crossSellSkip{{ $loop->index }}?.focus(), 50))">
                        <div class="flex items-center gap-2">
                            @if ($hasGroupSelection)
                                <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                            @else
                                <div class="w-5 h-5 border-2 border-gray-300 rounded-full dark:border-gray-600"></div>
                            @endif
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ $groupLabel }}
                                @if ($selectionSummary)
                                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                        — {{ $selectionSummary }}
                                    </span>
                                @endif
                            </span>
                        </div>
                        <x-heroicon-m-chevron-up class="w-5 h-5 text-gray-400 transition-transform" x-bind:class="{ 'rotate-180': !open }" />
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
                                    'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isGroupSkipped,
                                    'border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-primary-400 dark:hover:border-primary-500 hover:shadow-lg' => !$isGroupSkipped,
                                ])
                            >
                                <x-heroicon-o-no-symbol class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" />
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ __('lunarpanel::product.configurator.cross_sell.no_accessories') }}</span>
                            </button>

                            {{-- Product cards --}}
                            @foreach ($crossSellProducts as $product)
                                @php
                                    $hasProductImage = !empty($product['image']) && is_string($product['image']) && strlen(trim($product['image'])) > 0 && !str_contains($product['image'], 'data:image/gif;base64,R0lGOD');
                                    $productAmount = $selections[$product['code']] ?? null;
                                    $isProductSelected = $productAmount && $productAmount !== 'skip' && (int)$productAmount > 0;
                                    $currentAmount = $isProductSelected ? (int)$productAmount : ($product['default'] ?? 1);

                                    // Price formatting
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
                                        'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-2 ring-primary-500 ring-offset-2 shadow-md' => $isProductSelected,
                                        'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-400 dark:hover:border-primary-500 hover:shadow-lg' => !$isProductSelected,
                                    ])
                                >
                                    {{-- Product image --}}
                                    @if ($hasProductImage)
                                        <div
                                            class="flex items-center justify-center p-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 cursor-pointer"
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
                                            <span class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 text-center">{{ __('lunarpanel::product.configurator.cross_sell.amount') }}</span>
                                            <div class="flex items-center justify-center gap-1">
                                                <button
                                                    type="button"
                                                    @click.stop="if (amount > 1) { amount--; $wire.selectCrossSell('{{ $product['code'] }}', amount); } else { selected = false; $wire.selectCrossSell('{{ $product['code'] }}', 0); }"
                                                    class="flex items-center justify-center w-8 h-8 text-gray-600 bg-gray-200 rounded-l-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
                                                >
                                                    <x-heroicon-m-minus class="w-3 h-3" />
                                                </button>
                                                <input
                                                    type="number"
                                                    x-model="amount"
                                                    @change="$wire.selectCrossSell('{{ $product['code'] }}', parseInt(amount))"
                                                    min="1"
                                                    class="w-12 h-8 text-sm text-center border-gray-300 border-y dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-0"
                                                />
                                                <button
                                                    type="button"
                                                    @click.stop="amount++; $wire.selectCrossSell('{{ $product['code'] }}', amount);"
                                                    class="flex items-center justify-center w-8 h-8 text-gray-600 bg-gray-200 rounded-r-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
                                                >
                                                    <x-heroicon-m-plus class="w-3 h-3" />
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Product name --}}
                                        <h5
                                            class="text-sm font-semibold text-gray-900 dark:text-white mb-1 cursor-pointer"
                                            @click="if (!selected) { selected = true; $wire.selectCrossSell('{{ $product['code'] }}', amount); }"
                                        >
                                            {{ $product['label'] }}
                                        </h5>

                                        {{-- Price --}}
                                        @if ($priceFormatted)
                                            <p class="text-sm text-red-500 font-medium mt-auto">
                                                € {{ $priceFormatted }} / {{ $product['unit'] ?? 'pc' }}
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
                                {{ __('lunarpanel::product.configurator.cross_sell.continue') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Loading indicator for next options --}}
            <div wire:loading wire:target="selectOption, selectRadioOption, selectDimensions" class="flex items-center justify-center py-4">
                <x-filament::loading-indicator class="w-6 h-6 text-primary-500" />
            </div>

        </div>

        {{-- Debug: canOrder status --}}
        {{-- @if (config('app.debug'))
            <div class="mt-4 p-3 text-xs bg-gray-100 dark:bg-gray-900 rounded-lg">
                <span class="font-semibold">canOrder:</span> {{ $this->canOrder ? 'true' : 'false' }} |
                <span class="font-semibold">can_order (API):</span> {{ ($configurationData['can_order'] ?? false) ? 'true' : 'false' }} |
                <span class="font-semibold">pricingData:</span> {{ $pricingData ? 'set' : 'null' }} |
                <span class="font-semibold">targetVariantId:</span> {{ $targetVariantId ?? 'null' }}
            </div>
        @endif --}}
    @endif

    {{-- Pricing injected into modal footer --}}
    <div
        x-data="pricingInjector(@entangle('isSaving'), {{ $this->canOrder ? 'true' : 'false' }}, {{ $this->hasConfiguration ? 'true' : 'false' }})"
        x-ref="pricingInjector"
        data-can-order="{{ $this->canOrder ? 'true' : 'false' }}"
        data-has-configuration="{{ $this->hasConfiguration ? 'true' : 'false' }}"
        wire:key="pricing-injector-{{ $this->formattedSellPrice ?? 'none' }}-{{ count(array_filter($selections ?? [])) }}-{{ $isSaving ? 'saving' : 'idle' }}-{{ $this->canOrder ? 'can' : 'cannot' }}-{{ $this->hasConfiguration ? 'has' : 'no' }}"
        class="hidden"
    >
        <div x-ref="pricingSource">
            <div class="flex items-center justify-between gap-4 text-sm">
                <div class="flex items-center gap-6">
                    @if ($this->formattedSellPrice)
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500">{{ __('lunarpanel::product.configurator.cost_price') }}:</span>
                                <span class="font-medium text-gray-900">{{ $this->formattedCostPrice }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{ __('lunarpanel::product.configurator.sell_price') }}:</span>
                                <span class="text-lg font-bold text-primary-600">{{ $this->formattedSellPrice }}</span>
                            </div>
                        </div>
                    @else
                        <span class="text-gray-500">{{ __('lunarpanel::product.configurator.complete_config_for_price') }}</span>
                    @endif
                </div>
                @php $selectionsCount = count(array_filter($selections ?? [])); @endphp
                @if ($selectionsCount > 0)
                    <span class="text-gray-500">{{ $selectionsCount }} {{ __('lunarpanel::product.configurator.selections_made') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('pricingInjector', (isSaving, canOrder, hasConfiguration) => ({
        isSaving: isSaving,
        canOrder: canOrder,
        hasConfiguration: hasConfiguration,
        init() {
            // Initial update after a short delay
            setTimeout(() => this.doUpdate(), 200);

            // Watch for isSaving changes
            this.$watch('isSaving', (value) => {
                this.updateSaveButton();
            });

            // Also update on Livewire morph (when component re-renders)
            Livewire.hook('morph.updated', ({ component }) => {
                this.$nextTick(() => {
                    // Update canOrder from the data attribute
                    const canOrderAttr = this.$el.getAttribute('data-can-order');
                    if (canOrderAttr !== null) {
                        this.canOrder = canOrderAttr === 'true';
                    }
                    // Update hasConfiguration from the data attribute
                    const hasConfigAttr = this.$el.getAttribute('data-has-configuration');
                    if (hasConfigAttr !== null) {
                        this.hasConfiguration = hasConfigAttr === 'true';
                    }
                    this.doUpdate();
                });
            });

            // Listen for configurator-save event to immediately show loading
            window.addEventListener('configurator-save', () => {
                // Immediately show loading state when save is triggered (before Livewire responds)
                this.showSaveLoading();
            });
        },
        showSaveLoading() {
            const footer = this.getFooter();
            if (!footer) return;

            // Try multiple selectors to find the save button
            const selectors = [
                '.fi-modal-footer-actions button.fi-btn-color-primary',
                '.fi-modal-footer-actions button:first-child',
                'button.fi-btn-color-primary'
            ];

            let saveBtn = null;
            for (const selector of selectors) {
                try {
                    saveBtn = footer.querySelector(selector);
                    if (saveBtn) break;
                } catch (e) {}
            }

            if (!saveBtn) return;

            saveBtn.disabled = true;
            saveBtn.classList.add('opacity-70', 'cursor-wait');
            if (!saveBtn.querySelector('.fi-btn-loading-indicator')) {
                const spinner = document.createElement('span');
                spinner.className = 'fi-btn-loading-indicator inline-flex mr-2';
                spinner.innerHTML = '<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                saveBtn.insertBefore(spinner, saveBtn.firstChild);
            }
        },
        getFooter() {
            const selectors = [
                '.fi-modal-footer',
                '.fi-modal-footer-actions',
                '[class*="fi-modal"] [class*="footer"]',
                '.fi-modal [class*="sticky"]'
            ];
            for (const selector of selectors) {
                const el = document.querySelector(selector);
                if (el) {
                    if (el.classList.contains('fi-modal-footer-actions')) {
                        return el.parentElement;
                    }
                    return el;
                }
            }
            return null;
        },
        doUpdate() {
            const footer = this.getFooter();
            const source = this.$refs.pricingSource;
            if (footer && source) {
                footer.style.flexDirection = 'column';
                footer.style.alignItems = 'stretch';
                let target = footer.querySelector('#configurator-pricing');
                if (!target) {
                    target = document.createElement('div');
                    target.id = 'configurator-pricing';
                    target.className = 'w-full border-b border-gray-200 dark:border-gray-700 pb-4 mb-4';
                    footer.insertBefore(target, footer.firstChild);
                }
                target.innerHTML = source.innerHTML;
            }
            this.updateSaveButton(footer);
            this.updateUnlinkButton(footer);
        },
        updateUnlinkButton(footer) {
            if (!footer) footer = this.getFooter();
            if (!footer) return;

            // Find the unlink button (danger colored button)
            const selectors = [
                'button[x-on\\:click*="configurator-unlink"]',
                'button[@click*="configurator-unlink"]',
                '.fi-modal-footer-actions button.fi-btn-color-danger',
                'button.fi-btn-color-danger'
            ];

            let unlinkBtn = null;
            for (const selector of selectors) {
                try {
                    unlinkBtn = footer.querySelector(selector);
                    if (unlinkBtn) break;
                } catch (e) {
                    // Invalid selector, skip
                }
            }

            if (!unlinkBtn) return;

            if (this.hasConfiguration) {
                // Enable when there's a configuration to unlink
                unlinkBtn.disabled = false;
                unlinkBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                // Disable when there's no configuration
                unlinkBtn.disabled = true;
                unlinkBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        },
        updateSaveButton(footer) {
            if (!footer) footer = this.getFooter();
            if (!footer) return;

            // Try multiple selectors to find the save button
            const selectors = [
                'button[x-on\\:click*="configurator-save"]',
                'button[@click*="configurator-save"]',
                '.fi-modal-footer-actions button.fi-btn-color-primary',
                '.fi-modal-footer-actions button:first-child',
                'button.fi-btn-color-primary'
            ];

            let saveBtn = null;
            for (const selector of selectors) {
                try {
                    saveBtn = footer.querySelector(selector);
                    if (saveBtn) break;
                } catch (e) {
                    // Invalid selector, skip
                }
            }

            if (!saveBtn) return;

            if (this.isSaving) {
                // Show loading state
                saveBtn.disabled = true;
                saveBtn.classList.add('opacity-70', 'cursor-wait');
                saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                if (!saveBtn.querySelector('.fi-btn-loading-indicator')) {
                    const spinner = document.createElement('span');
                    spinner.className = 'fi-btn-loading-indicator inline-flex mr-2';
                    spinner.innerHTML = '<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                    saveBtn.insertBefore(spinner, saveBtn.firstChild);
                }
            } else if (!this.canOrder) {
                // Disable when configuration is incomplete
                saveBtn.disabled = true;
                saveBtn.classList.add('opacity-50', 'cursor-not-allowed');
                saveBtn.classList.remove('opacity-70', 'cursor-wait');
                const spinner = saveBtn.querySelector('.fi-btn-loading-indicator');
                if (spinner) spinner.remove();
            } else {
                // Enable when ready
                saveBtn.disabled = false;
                saveBtn.classList.remove('opacity-70', 'cursor-wait', 'opacity-50', 'cursor-not-allowed');
                const spinner = saveBtn.querySelector('.fi-btn-loading-indicator');
                if (spinner) spinner.remove();
            }
        }
    }));
</script>
@endscript
