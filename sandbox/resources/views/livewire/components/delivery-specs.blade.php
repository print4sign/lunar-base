<div @class(['delivery-specs', 'delivery-specs--compact' => $compact])>
    @if($this->hasSpecs)
        @php $uploader = $this->primaryUploader; @endphp

        {{-- Template Download Section --}}
        @if($showTemplate)
            <div @class(['mb-8' => !$compact, 'mb-6' => $compact])>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">
                    {{ __('delivery_specs.template.title') }}
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('delivery_specs.template.description') }}
                </p>
                @if($templateUrl)
                    <a
                        href="{{ $templateUrl }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ __('delivery_specs.template.download') }}
                    </a>
                @else
                    <button
                        type="button"
                        disabled
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-300 text-gray-500 text-sm font-medium rounded-lg cursor-not-allowed"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ __('delivery_specs.template.download') }}
                    </button>
                    <p class="mt-2 text-xs text-gray-500">
                        {{ __('delivery_specs.template.not_available') }}
                    </p>
                @endif
            </div>
        @endif

        {{-- Specifications List --}}
        <div class="divide-y divide-gray-200">
            {{-- Number of designs --}}
            <x-delivery-specs-modal.spec-row
                :label="__('delivery_specs.specs.amount')"
                :value="__('delivery_specs.specs.amount_value', ['amount' => $uploader['amount'] ?? 1])"
            />

            {{-- Dimensions --}}
            @if(($uploader['width'] ?? null) || ($uploader['height'] ?? null))
                <x-delivery-specs-modal.spec-row
                    :label="__('delivery_specs.specs.dimensions')"
                    :value="$this->formatDimensions($uploader['width'] ?? null, $uploader['height'] ?? null, $uploader['length'] ?? null)"
                />
            @endif

            {{-- Optimal resolution --}}
            <x-delivery-specs-modal.spec-row
                :label="__('delivery_specs.specs.resolution')"
                :value="($uploader['minimal_dpi'] ?? 72) . ' PPI'"
                :info="__('delivery_specs.specs.resolution_info')"
            />

            {{-- Colors --}}
            <x-delivery-specs-modal.spec-row
                :label="__('delivery_specs.specs.colors')"
                :value="__('delivery_specs.specs.colors_value')"
                :info="__('delivery_specs.specs.colors_info')"
            />

            {{-- Bleed --}}
            @if($this->hasBleed($uploader))
                <x-delivery-specs-modal.spec-row
                    :label="__('delivery_specs.specs.bleed')"
                    :value="__('delivery_specs.specs.bleed_value', [
                        'top' => $uploader['bleed_top'] ?? 0,
                        'right' => $uploader['bleed_right'] ?? 0,
                        'bottom' => $uploader['bleed_bottom'] ?? 0,
                        'left' => $uploader['bleed_left'] ?? 0,
                    ])"
                    :info="__('delivery_specs.specs.bleed_info')"
                />
            @endif

            {{-- Minimum line thickness --}}
            <x-delivery-specs-modal.spec-row
                :label="__('delivery_specs.specs.line_thickness')"
                :value="'-'"
                :info="__('delivery_specs.specs.line_thickness_info')"
            />

            {{-- Minimum font size --}}
            <x-delivery-specs-modal.spec-row
                :label="__('delivery_specs.specs.font_size')"
                :value="'-'"
                :info="__('delivery_specs.specs.font_size_info')"
            />

            {{-- File type --}}
            <x-delivery-specs-modal.spec-row
                :label="__('delivery_specs.specs.file_type')"
                :value="__('delivery_specs.specs.file_type_value')"
                :info="__('delivery_specs.specs.file_type_info')"
            />

            {{-- Embed fonts --}}
            <x-delivery-specs-modal.spec-row
                :label="__('delivery_specs.specs.embed_fonts')"
                :value="__('delivery_specs.specs.embed_fonts_value')"
                :info="__('delivery_specs.specs.embed_fonts_info')"
            />

            {{-- Cut marks --}}
            @if($this->hasCutMarks($uploader))
                <x-delivery-specs-modal.spec-row
                    :label="__('delivery_specs.specs.cut_marks')"
                    :value="__('delivery_specs.specs.cut_marks_required')"
                    :info="__('delivery_specs.specs.cut_marks_info')"
                />
            @else
                <x-delivery-specs-modal.spec-row
                    :label="__('delivery_specs.specs.cut_marks')"
                    :value="__('delivery_specs.specs.cut_marks_not_allowed')"
                    :info="__('delivery_specs.specs.cut_marks_info')"
                />
            @endif

            {{-- Max file size --}}
            <x-delivery-specs-modal.spec-row
                :label="__('delivery_specs.specs.max_file_size')"
                :value="$this->formatFileLimit($uploader['file_limit'] ?? 500)"
            />

            {{-- White spot required --}}
            @if($uploader['require_white_spot'] ?? false)
                <x-delivery-specs-modal.spec-row
                    :label="__('delivery_specs.specs.white_spot')"
                    :value="__('delivery_specs.specs.white_spot_required')"
                    :info="__('delivery_specs.specs.white_spot_info')"
                />
            @endif

            {{-- Tiling --}}
            @if($uploader['tiling_enabled'] ?? false)
                <x-delivery-specs-modal.spec-row
                    :label="__('delivery_specs.specs.tiling')"
                    :value="($uploader['tiling_mandatory'] ?? false) ? __('delivery_specs.specs.tiling_mandatory') : __('delivery_specs.specs.tiling_optional')"
                    :info="__('delivery_specs.specs.tiling_info')"
                />
            @endif
        </div>

        {{-- Link to more info --}}
        @if($showMoreInfoLink)
            <div @class(['mt-8' => !$compact, 'mt-6' => $compact])>
                <a
                    href="{{ $moreInfoUrl ?? '#' }}"
                    class="block w-full text-center px-4 py-3 bg-blue-50 text-primary-600 text-sm font-medium rounded-lg hover:bg-blue-100 transition-colors"
                >
                    {{ __('delivery_specs.more_info') }}
                </a>
            </div>
        @endif
    @else
        {{-- No upload spec --}}
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">
                {{ __('delivery_specs.no_specs') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('delivery_specs.no_specs_description') }}
            </p>
        </div>
    @endif
</div>
