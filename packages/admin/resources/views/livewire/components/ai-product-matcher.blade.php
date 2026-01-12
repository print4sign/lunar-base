<div>
    <x-filament::modal
        id="ai-product-matcher-modal"
        :wire:key="'ai-matcher-' . $variantId"
        :visible="$isOpen"
        width="5xl"
        :close-by-clicking-away="!$isLoading"
    >
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-sparkles"
                    class="w-6 h-6 text-info-500"
                />
                <span>{{ __('lunarpanel::productvariant.fulfillment.ai_match.title') }}</span>
            </div>
        </x-slot>

        <x-slot name="description">
            {{ __('lunarpanel::productvariant.fulfillment.ai_match.description') }}
        </x-slot>

        <div class="space-y-4">
            {{-- Supplier Filter --}}
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                <label class="block text-sm font-medium mb-2">
                    {{ __('lunarpanel::productvariant.fulfillment.ai_match.filter_suppliers') }}
                </label>
                <select
                    wire:model.live="selectedSuppliers"
                    multiple
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700"
                >
                    @foreach($availableSuppliers as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('lunarpanel::productvariant.fulfillment.ai_match.filter_help') }}
                </p>
            </div>

            {{-- Loading State --}}
            @if($isLoading)
                <div class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <x-filament::loading-indicator class="w-8 h-8 mx-auto mb-4" />
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('lunarpanel::productvariant.fulfillment.ai_match.analyzing') }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- Error State --}}
            @if($error && !$isLoading)
                <div class="bg-danger-50 dark:bg-danger-950 border border-danger-200 dark:border-danger-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            class="w-5 h-5 text-danger-600 dark:text-danger-400 flex-shrink-0 mt-0.5"
                        />
                        <div>
                            <p class="font-medium text-danger-900 dark:text-danger-100">
                                {{ __('lunarpanel::productvariant.fulfillment.ai_match.error_title') }}
                            </p>
                            <p class="text-sm text-danger-700 dark:text-danger-300 mt-1">
                                {{ $error }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Matches List --}}
            @if(!empty($matches) && !$isLoading)
                <div class="space-y-3">
                    @foreach($matches as $match)
                        <div
                            class="border rounded-lg p-4 hover:border-primary-500 transition cursor-pointer {{ $match['is_recommended'] ? 'bg-success-50 dark:bg-success-950 border-success-200 dark:border-success-800' : 'bg-white dark:bg-gray-900' }}"
                            wire:click="selectMatch({{ $match['supplier_product_id'] }})"
                        >
                            <div class="flex items-start justify-between gap-4">
                                {{-- Product Info --}}
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $match['product_name'] }}
                                        </h4>
                                        <x-filament::badge :color="$match['score_color']">
                                            {{ $match['score'] }}% - {{ $match['score_label'] }}
                                        </x-filament::badge>
                                        @if($match['is_recommended'])
                                            <x-filament::badge color="success" icon="heroicon-o-star">
                                                {{ __('lunarpanel::productvariant.fulfillment.ai_match.recommended') }}
                                            </x-filament::badge>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        <span class="flex items-center gap-1">
                                            <x-filament::icon icon="heroicon-o-building-storefront" class="w-4 h-4" />
                                            {{ $match['supplier_name'] }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <x-filament::icon icon="heroicon-o-hashtag" class="w-4 h-4" />
                                            {{ $match['external_id'] }}
                                        </span>
                                    </div>

                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                        {{ $match['reasoning'] }}
                                    </p>

                                    {{-- Matched Fields --}}
                                    @if(!empty($match['matched_fields']))
                                        <div class="flex flex-wrap gap-2 mb-2">
                                            @foreach($match['matched_fields'] as $field)
                                                <x-filament::badge color="success" icon="heroicon-o-check-circle">
                                                    {{ $field }}
                                                </x-filament::badge>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Concerns --}}
                                    @if(!empty($match['concerns']))
                                        <div class="bg-warning-50 dark:bg-warning-950 border border-warning-200 dark:border-warning-800 rounded p-2 mt-2">
                                            <div class="flex items-start gap-2">
                                                <x-filament::icon
                                                    icon="heroicon-o-exclamation-triangle"
                                                    class="w-4 h-4 text-warning-600 dark:text-warning-400 flex-shrink-0 mt-0.5"
                                                />
                                                <div class="text-xs">
                                                    <p class="font-medium text-warning-900 dark:text-warning-100 mb-1">
                                                        {{ __('lunarpanel::productvariant.fulfillment.ai_match.concerns') }}:
                                                    </p>
                                                    <ul class="list-disc list-inside text-warning-700 dark:text-warning-300 space-y-0.5">
                                                        @foreach($match['concerns'] as $concern)
                                                            <li>{{ $concern }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Select Button --}}
                                <div class="flex-shrink-0">
                                    <x-filament::button
                                        color="primary"
                                        size="sm"
                                    >
                                        {{ __('lunarpanel::productvariant.fulfillment.ai_match.select') }}
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <x-slot name="footerActions">
            <x-filament::button
                color="gray"
                wire:click="closeModal"
            >
                {{ __('filament::pages/auth/register.actions.login.label') }}
            </x-filament::button>

            @if(!empty($matches))
                <x-filament::button
                    color="primary"
                    wire:click="findMatches"
                    :disabled="$isLoading"
                >
                    <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4 mr-1" />
                    {{ __('lunarpanel::productvariant.fulfillment.ai_match.refresh') }}
                </x-filament::button>
            @endif
        </x-slot>
    </x-filament::modal>
</div>
