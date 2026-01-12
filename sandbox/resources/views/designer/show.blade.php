<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('online_designer.title') }} - {{ $productName }}</title>

    {{-- Standalone page: Load CSS and standalone designer (which includes its own Alpine) --}}
    @vite(['resources/css/app.css', 'resources/css/online-designer.css', 'resources/js/standalone-designer.js'])
</head>
<body class="bg-gray-100 min-h-screen">
    <div
        x-data="standaloneDesigner({
            config: @js($canvasConfig),
            designs: @js($designs),
            isFrontBack: @js($isFrontBack),
            cartLineId: @js($cartLineId),
            uploaderIndex: @js($uploaderIndex),
            saveUrl: '{{ route('designer.save', ['locale' => app()->getLocale(), 'designerSegment' => __('routes.designer'), 'cartLineId' => $cartLineId, 'uploaderIndex' => $uploaderIndex]) }}',
            confirmUrl: '{{ route('designer.confirm', ['locale' => app()->getLocale(), 'designerSegment' => __('routes.designer'), 'cartLineId' => $cartLineId, 'uploaderIndex' => $uploaderIndex]) }}',
            csrfToken: '{{ csrf_token() }}',
        })"
        class="min-h-screen flex flex-col"
    >
        {{-- Header --}}
        <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-4">
                <a href="{{ route('checkout.view', ['locale' => app()->getLocale(), 'checkoutSegment' => __('routes.checkout')]) }}"
                   class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">{{ __('online_designer.title') }}</h1>
                    <p class="text-sm text-gray-500">{{ $productName }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Auto-save indicator --}}
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <template x-if="isSaving">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __('online_designer.saving') }}
                        </span>
                    </template>
                    <template x-if="!isSaving && lastSaved">
                        <span class="flex items-center gap-1 text-green-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ __('online_designer.saved') }}
                        </span>
                    </template>
                </div>

                {{-- Confirm button --}}
                <button
                    type="button"
                    x-on:click="confirmAndClose()"
                    :disabled="!canConfirm || isConfirming"
                    :class="{
                        'bg-primary-600 text-white hover:bg-primary-700': canConfirm && !isConfirming,
                        'bg-gray-200 text-gray-400 cursor-not-allowed': !canConfirm || isConfirming,
                    }"
                    class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                >
                    <span x-show="!isConfirming">{{ __('online_designer.actions.confirm_and_close') }}</span>
                    <span x-show="isConfirming" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('online_designer.confirming') }}
                    </span>
                </button>
            </div>
        </header>

        {{-- Front/Back Tabs (for frontback type) --}}
        @if($isFrontBack)
            <div class="bg-white border-b border-gray-200 px-4">
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        x-on:click="switchSide('front')"
                        :class="{
                            'border-primary-600 text-primary-600': currentSide === 'front',
                            'border-transparent text-gray-500 hover:text-gray-700': currentSide !== 'front',
                        }"
                        class="px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors"
                    >
                        {{ __('online_designer.side.front') }}
                        <span x-show="hasDesign('front')" class="ml-1 text-green-500">&#10003;</span>
                    </button>
                    <button
                        type="button"
                        x-on:click="switchSide('back')"
                        :class="{
                            'border-primary-600 text-primary-600': currentSide === 'back',
                            'border-transparent text-gray-500 hover:text-gray-700': currentSide !== 'back',
                        }"
                        class="px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors"
                    >
                        {{ __('online_designer.side.back') }}
                        <span x-show="hasDesign('back')" class="ml-1 text-green-500">&#10003;</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- Main Content --}}
        <div class="flex-1 flex min-h-0">
            {{-- Left Toolbar --}}
            <div class="w-14 bg-white border-r border-gray-200 flex flex-col gap-1 p-2 shrink-0">
                {{-- Select Tool --}}
                <button
                    type="button"
                    x-on:click="setTool('select')"
                    :class="{ 'bg-primary-100 text-primary-600': currentTool === 'select' }"
                    class="p-2 rounded hover:bg-gray-100 transition-colors"
                    title="{{ __('online_designer.tools.select') }}"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                    </svg>
                </button>

                {{-- Text Tool --}}
                <button
                    type="button"
                    x-on:click="addText()"
                    class="p-2 rounded hover:bg-gray-100 transition-colors"
                    title="{{ __('online_designer.tools.text') }}"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>

                {{-- Shape Dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button
                        type="button"
                        x-on:click="open = !open"
                        class="p-2 rounded hover:bg-gray-100 transition-colors"
                        title="{{ __('online_designer.tools.shapes') }}"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5z" />
                        </svg>
                    </button>
                    <div
                        x-show="open"
                        x-on:click.away="open = false"
                        x-transition
                        class="absolute left-full ml-2 top-0 bg-white shadow-lg rounded-lg p-1 z-20 min-w-[140px] border border-gray-200"
                    >
                        <button type="button" x-on:click="addShape('rect'); open = false" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded">{{ __('online_designer.shapes.rectangle') }}</button>
                        <button type="button" x-on:click="addShape('circle'); open = false" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded">{{ __('online_designer.shapes.circle') }}</button>
                        <button type="button" x-on:click="addShape('triangle'); open = false" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded">{{ __('online_designer.shapes.triangle') }}</button>
                        <button type="button" x-on:click="addShape('line'); open = false" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded">{{ __('online_designer.shapes.line') }}</button>
                    </div>
                </div>

                {{-- Image Upload --}}
                <label class="p-2 rounded hover:bg-gray-100 transition-colors cursor-pointer" title="{{ __('online_designer.tools.image') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <input type="file" x-on:change="uploadImage($event)" accept="image/*" class="hidden" />
                </label>

                <div class="border-t border-gray-200 my-2"></div>

                {{-- Undo --}}
                <button
                    type="button"
                    x-on:click="undo()"
                    :disabled="!canUndo"
                    :class="{ 'opacity-40 cursor-not-allowed': !canUndo }"
                    class="p-2 rounded hover:bg-gray-100 transition-colors"
                    title="{{ __('online_designer.actions.undo') }} (Ctrl+Z)"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                </button>

                {{-- Redo --}}
                <button
                    type="button"
                    x-on:click="redo()"
                    :disabled="!canRedo"
                    :class="{ 'opacity-40 cursor-not-allowed': !canRedo }"
                    class="p-2 rounded hover:bg-gray-100 transition-colors"
                    title="{{ __('online_designer.actions.redo') }} (Ctrl+Shift+Z)"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6" />
                    </svg>
                </button>

                {{-- Delete --}}
                <button
                    type="button"
                    x-on:click="deleteSelected()"
                    :disabled="!hasSelection"
                    :class="{ 'opacity-40 cursor-not-allowed': !hasSelection }"
                    class="p-2 rounded hover:bg-gray-100 transition-colors text-red-600"
                    title="{{ __('online_designer.actions.delete') }} (Del)"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>

            {{-- Canvas Area --}}
            <div class="flex-1 relative bg-gray-200 overflow-auto">
                {{-- Zone Legend --}}
                @php
                    $hasBleed = ($canvasConfig['bleedTop'] ?? 0) > 0 || ($canvasConfig['bleedRight'] ?? 0) > 0 || ($canvasConfig['bleedBottom'] ?? 0) > 0 || ($canvasConfig['bleedLeft'] ?? 0) > 0;
                @endphp
                <div class="absolute top-4 left-4 bg-white/95 backdrop-blur rounded-lg px-3 py-2 text-xs z-10 shadow-sm border border-gray-200">
                    <div class="flex items-center gap-4">
                        @if($hasBleed)
                            <span class="flex items-center gap-1.5">
                                <span class="w-4 h-3 border-2 border-dashed border-red-500 rounded-sm"></span>
                                {{ __('online_designer.zones.bleed') }}
                            </span>
                        @endif
                        <span class="flex items-center gap-1.5">
                            <span class="w-4 h-3 border-2 border-cyan-500 rounded-sm"></span>
                            {{ __('online_designer.zones.trim') }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-4 h-3 border-2 border-dashed border-green-500 rounded-sm"></span>
                            {{ __('online_designer.zones.safe') }}
                        </span>
                    </div>
                </div>

                {{-- Dimensions Badge --}}
                <div class="absolute top-4 right-4 bg-white/95 backdrop-blur rounded-lg px-3 py-2 text-xs z-10 shadow-sm border border-gray-200">
                    <span class="font-medium">{{ $canvasConfig['widthMm'] }} &times; {{ $canvasConfig['heightMm'] }} mm</span>
                    @if(($canvasConfig['bleedTop'] ?? 0) > 0 || ($canvasConfig['bleedRight'] ?? 0) > 0 || ($canvasConfig['bleedBottom'] ?? 0) > 0 || ($canvasConfig['bleedLeft'] ?? 0) > 0)
                        <span class="text-gray-500 ml-1">
                            (+{{ $canvasConfig['bleedTop'] }}/{{ $canvasConfig['bleedRight'] }}/{{ $canvasConfig['bleedBottom'] }}/{{ $canvasConfig['bleedLeft'] }} {{ __('online_designer.bleed') }})
                        </span>
                    @endif
                </div>

                {{-- Canvas Container --}}
                <div class="flex items-center justify-center min-h-full p-8">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>

            {{-- Right Panel: Layers & Properties --}}
            <div class="w-64 bg-white border-l border-gray-200 flex flex-col shrink-0 overflow-hidden">
                {{-- Layers Panel --}}
                <div class="border-b border-gray-200">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 font-medium text-sm">
                        {{ __('online_designer.panels.layers') }}
                    </div>
                    <div class="max-h-48 overflow-y-auto">
                        <template x-for="layer in layers" :key="layer.id">
                            <div
                                x-on:click="selectLayer(layer.id)"
                                :class="{ 'bg-primary-50 border-l-2 border-primary-500': selectedLayerId === layer.id }"
                                class="px-4 py-2 text-sm cursor-pointer hover:bg-gray-50 flex items-center justify-between"
                            >
                                <span x-text="layer.name" class="truncate"></span>
                                <div class="flex items-center gap-0.5">
                                    <button type="button" x-on:click.stop="moveLayerUp(layer.id)" class="p-1 hover:bg-gray-200 rounded" title="Move up">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                                    </button>
                                    <button type="button" x-on:click.stop="moveLayerDown(layer.id)" class="p-1 hover:bg-gray-200 rounded" title="Move down">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div x-show="layers.length === 0" class="px-4 py-6 text-sm text-gray-400 text-center italic">
                            {{ __('online_designer.panels.no_layers') }}
                        </div>
                    </div>
                </div>

                {{-- Properties Panel --}}
                <div x-show="hasSelection" x-transition class="flex-1 overflow-y-auto">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 font-medium text-sm">
                        {{ __('online_designer.panels.properties') }}
                    </div>
                    <div class="p-4 space-y-4">
                        {{-- Fill Color --}}
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('online_designer.properties.fill') }}</label>
                            <input type="color" x-model="selectedProps.fill" x-on:input="updateSelectedProperty('fill', $event.target.value)" class="w-full h-10 rounded border border-gray-300 cursor-pointer" />
                        </div>

                        {{-- Stroke Color --}}
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('online_designer.properties.stroke') }}</label>
                            <input type="color" x-model="selectedProps.stroke" x-on:input="updateSelectedProperty('stroke', $event.target.value)" class="w-full h-10 rounded border border-gray-300 cursor-pointer" />
                        </div>

                        {{-- Opacity --}}
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ __('online_designer.properties.opacity') }}: <span x-text="Math.round(selectedProps.opacity * 100) + '%'"></span></label>
                            <input type="range" min="0" max="1" step="0.05" x-model="selectedProps.opacity" x-on:input="updateSelectedProperty('opacity', parseFloat($event.target.value))" class="w-full" />
                        </div>

                        {{-- Text Properties --}}
                        <template x-if="selectedProps.type === 'textbox' || selectedProps.type === 'i-text'">
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">{{ __('online_designer.properties.font_size') }}</label>
                                    <input type="number" x-model.number="selectedProps.fontSize" x-on:input="updateSelectedProperty('fontSize', parseInt($event.target.value))" min="8" max="200" class="w-full px-3 py-2 border border-gray-300 rounded text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">{{ __('online_designer.properties.font_weight') }}</label>
                                    <select x-model="selectedProps.fontWeight" x-on:change="updateSelectedProperty('fontWeight', $event.target.value)" class="w-full px-3 py-2 border border-gray-300 rounded text-sm">
                                        <option value="normal">Normal</option>
                                        <option value="bold">Bold</option>
                                    </select>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
