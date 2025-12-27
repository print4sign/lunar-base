<?php

namespace Lunar\Base\DataTransferObjects\Supplier;

class ConfiguratorResponse
{
    /**
     * Input type codes that should be processed as individual inputs.
     */
    private const INPUT_TYPES = [
        'input', 'amount', 'width', 'height', 'length',
        'text', 'decimal', 'number', 'integer', 'float',
    ];

    public function __construct(
        public readonly string $productCode,
        public readonly array $availableOptions,
        public readonly array $selectedOptions,
        public readonly bool $canOrder,
        public readonly ?string $nextStep = null,
        public readonly ?array $validationErrors = null,
        public readonly ?array $productInfo = null,
        public readonly ?array $meta = null,
    ) {}

    /**
     * Create a ConfiguratorResponse from Probo API response.
     */
    public static function fromProboResponse(string $productCode, array $data): self
    {
        $availableOptions = self::normalizeProboOptions($data['available_options'] ?? []);
        $selectedOptions = self::normalizeProboSelectedOptions($data['selected_options'] ?? []);

        return new self(
            productCode: $productCode,
            availableOptions: $availableOptions,
            selectedOptions: $selectedOptions,
            canOrder: $data['can_order'] ?? false,
            nextStep: self::determineNextStep($availableOptions, $selectedOptions),
            validationErrors: $data['errors'] ?? null,
            productInfo: [
                'name' => $data['product']['name'] ?? $data['name'] ?? null,
                'description' => $data['product']['description'] ?? $data['description'] ?? null,
                'images' => $data['product']['images'] ?? $data['images'] ?? [],
            ],
            meta: ['raw_response' => $data],
        );
    }

    /**
     * Create an empty/initial response for a product.
     */
    public static function initial(string $productCode): self
    {
        return new self(
            productCode: $productCode,
            availableOptions: [],
            selectedOptions: [],
            canOrder: false,
        );
    }

    public function isComplete(): bool
    {
        return $this->canOrder;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->validationErrors);
    }

    public function getOption(string $code): ?array
    {
        foreach ($this->availableOptions as $option) {
            if (($option['code'] ?? null) === $code) {
                return $option;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'product_code' => $this->productCode,
            'available_options' => $this->availableOptions,
            'selected_options' => $this->selectedOptions,
            'can_order' => $this->canOrder,
            'next_step' => $this->nextStep,
            'validation_errors' => $this->validationErrors,
            'product_info' => $this->productInfo,
        ];
    }

    // ========================================
    // Main Normalization Entry Point
    // ========================================

    /**
     * Normalize Probo's available options format into our structure.
     *
     * @see https://apidocs.proboprints.com/examples/configure-examples
     */
    protected static function normalizeProboOptions(array $proboOptions): array
    {
        self::logRawOptions($proboOptions);

        $lang = app()->getLocale() === 'nl' ? 'nl' : 'en';
        $normalized = [];

        // Classify top-level options
        $classified = self::classifyTopLevelOptions($proboOptions);

        // Process radio-like top-level options as grouped select
        $normalized = array_merge(
            $normalized,
            self::processTopLevelRadioOptions($classified['radioLike'], $lang)
        );

        // Process each option group (ones with children or input types)
        foreach ($classified['input'] as $optionGroup) {
            $normalized = array_merge(
                $normalized,
                self::processOptionGroup($optionGroup, $lang)
            );
        }

        return $normalized;
    }

    // ========================================
    // Classification Methods
    // ========================================

    /**
     * Classify top-level options into radio-like and input types.
     */
    protected static function classifyTopLevelOptions(array $proboOptions): array
    {
        $radioLike = [];
        $input = [];

        foreach ($proboOptions as $option) {
            if (self::isTopLevelRadioLike($option)) {
                $radioLike[] = $option;
            } else {
                $input[] = $option;
            }
        }

        self::logTopLevelClassification($proboOptions, $radioLike, $input);

        return ['radioLike' => $radioLike, 'input' => $input];
    }

    /**
     * Classify children of an option group into radio-like and input types.
     */
    protected static function classifyChildren(array $children): array
    {
        $radioLike = [];
        $input = [];

        foreach ($children as $child) {
            $typeCode = strtolower($child['type_code'] ?? '');
            $hasGrandchildren = ! empty($child['children']);

            if (self::isCrossSellType($typeCode) || self::isInputType($typeCode)) {
                $input[] = $child;
            } elseif (! $hasGrandchildren) {
                $radioLike[] = $child;
            } else {
                $input[] = $child;
            }
        }

        return ['radioLike' => $radioLike, 'input' => $input];
    }

    /**
     * Check if a top-level option should be treated as a radio choice.
     */
    protected static function isTopLevelRadioLike(array $option): bool
    {
        $typeCode = strtolower($option['type_code'] ?? '');
        $children = $option['children'] ?? [];

        // Direct radio/select with no children
        if (empty($children) && in_array($typeCode, ['radio', 'select'])) {
            return true;
        }

        // No children and not an input type
        if (empty($children) && ! self::isInputType($typeCode) && ! self::isCrossSellType($typeCode)) {
            return true;
        }

        // Single radio child
        if (count($children) === 1) {
            $child = $children[0];
            $childType = strtolower($child['type_code'] ?? '');

            return $childType === 'radio' && empty($child['children']);
        }

        return false;
    }

    protected static function isInputType(string $typeCode): bool
    {
        return in_array($typeCode, self::INPUT_TYPES);
    }

    protected static function isCrossSellType(string $typeCode): bool
    {
        return str_contains($typeCode, 'cross_sell');
    }

    // ========================================
    // Processing Methods
    // ========================================

    /**
     * Process top-level radio-like options into a grouped select.
     */
    protected static function processTopLevelRadioOptions(array $radioOptions, string $lang): array
    {
        if (count($radioOptions) <= 1) {
            if (count($radioOptions) === 1) {
                return [self::normalizeDirectOption($radioOptions[0], $lang)];
            }

            return [];
        }

        $choices = array_map([self::class, 'buildChoiceFromTopLevelOption'], $radioOptions);
        $firstCode = $radioOptions[0]['code'] ?? 'options';
        $groupCode = preg_replace('/-[^-]+$/', '', $firstCode) ?: $firstCode;

        logger()->debug('ConfiguratorResponse: Grouped radio-like options', [
            'choices_count' => count($choices),
            'choices' => $choices,
        ]);

        return [[
            'code' => $groupCode,
            'label' => $radioOptions[0]['name'] ?? ucfirst(str_replace('-', ' ', $groupCode)),
            'description' => null,
            'type' => 'select',
            'required' => true,
            'default' => null,
            'image' => null,
            'choices' => $choices,
        ]];
    }

    /**
     * Process a single option group (may have children).
     */
    protected static function processOptionGroup(array $optionGroup, string $lang): array
    {
        $children = $optionGroup['children'] ?? [];

        if (empty($children)) {
            return [self::normalizeDirectOption($optionGroup, $lang)];
        }

        $classified = self::classifyChildren($children);
        $normalized = [];

        self::logChildrenClassification($optionGroup, $classified);

        // Multiple radio-like children -> grouped select with sibling inputs
        if (count($classified['radioLike']) > 1) {
            $normalized[] = self::buildGroupedSelect($optionGroup, $classified['radioLike'], $classified['input'], $lang);

            // Only cross-sells remain for individual processing
            $classified['input'] = array_filter(
                $classified['input'],
                fn ($c) => self::isCrossSellType($c['type_code'] ?? '')
            );
        } elseif (count($classified['radioLike']) === 1) {
            $normalized[] = self::normalizeDirectOption($classified['radioLike'][0], $lang);
        }

        // Process remaining input children individually
        foreach ($classified['input'] as $child) {
            $normalized[] = self::normalizeChildOption($child, $optionGroup, $lang);
        }

        return $normalized;
    }

    // ========================================
    // Building Normalized Options
    // ========================================

    /**
     * Build a grouped select option with sibling inputs embedded.
     */
    protected static function buildGroupedSelect(array $optionGroup, array $radioChildren, array $inputChildren, string $lang): array
    {
        $choices = self::normalizeChoices($radioChildren, $lang);
        $siblingInputs = self::buildSiblingInputs($inputChildren);

        logger()->debug('ConfiguratorResponse: Radio group normalized', [
            'group_code' => $optionGroup['code'] ?? 'unknown',
            'radioLikeChildren_count' => count($radioChildren),
            'choices_count' => count($choices),
            'sibling_inputs_count' => count($siblingInputs),
            'choices' => $choices,
        ]);

        return [
            'code' => $optionGroup['code'] ?? '',
            'label' => $optionGroup['name'] ?? $optionGroup['label'] ?? $optionGroup['code'] ?? '',
            'description' => $optionGroup['description'] ?? null,
            'type' => 'select',
            'required' => true,
            'default' => null,
            'image' => self::extractImage($optionGroup),
            'choices' => $choices,
            'sibling_inputs' => $siblingInputs,
        ];
    }

    /**
     * Build sibling input options to embed in a grouped select.
     */
    protected static function buildSiblingInputs(array $inputChildren): array
    {
        $siblings = [];

        foreach ($inputChildren as $child) {
            $type = self::determineChildType($child);

            if ($type === 'cross_sell') {
                continue;
            }

            $sibling = [
                'code' => $child['code'] ?? '',
                'label' => $child['name'] ?? $child['label'] ?? $child['code'] ?? '',
                'description' => $child['description'] ?? null,
                'type' => $type,
                'required' => false,
                'default' => $child['default_value'] ?? null,
                'image' => self::extractImage($child),
            ];

            if ($type === 'number') {
                self::addNumberConstraints($sibling, $child);
            }

            $siblings[] = $sibling;
        }

        return $siblings;
    }

    /**
     * Normalize a child option within an option group.
     */
    protected static function normalizeChildOption(array $child, array $parentGroup, string $lang): array
    {
        $code = $child['code'] ?? $parentGroup['code'] ?? '';
        $type = self::determineChildType($child);

        logger()->debug('ConfiguratorResponse: Processing child option', [
            'group_code' => $parentGroup['code'] ?? 'unknown',
            'child_code' => $code,
            'child_type_code' => $child['type_code'] ?? 'unknown',
            'determined_type' => $type,
            'has_unit' => isset($child['unit_code']),
            'unit' => $child['unit_code'] ?? null,
        ]);

        $option = [
            'code' => $code,
            'label' => $child['name'] ?? $child['label'] ?? $parentGroup['name'] ?? $parentGroup['label'] ?? $code,
            'description' => $child['description'] ?? null,
            'type' => $type,
            'required' => $type !== 'cross_sell',
            'default' => $child['default_value'] ?? null,
            'image' => self::extractImage($child),
        ];

        if ($type === 'cross_sell') {
            $option['price'] = $child['price'] ?? $child['price_in_cents'] ?? null;
            $option['currency'] = $child['currency'] ?? 'EUR';
            $option['unit'] = $child['unit_code'] ?? 'pc';
            $option['group_code'] = $parentGroup['code'] ?? 'cross-sells';
            $option['group_label'] = $parentGroup['name'] ?? 'Cross-sells';
        }

        if ($type === 'number') {
            self::addNumberConstraints($option, $child);
            $option['reversible'] = $child['reversible'] ?? false;
        }

        if ($type === 'select' && ! empty($child['children'])) {
            $option['choices'] = self::normalizeChoices($child['children'], $lang);
        }

        return $option;
    }

    /**
     * Normalize a direct option (legacy format without nested children).
     */
    protected static function normalizeDirectOption(array $option, string $lang): array
    {
        $type = self::determineOptionType($option);

        $normalized = [
            'code' => $option['code'] ?? $option['handle'] ?? $option['id'] ?? '',
            'label' => self::extractLabel($option, $lang),
            'description' => self::extractDescription($option, $lang),
            'type' => $type,
            'required' => $option['required'] ?? true,
            'default' => $option['default'] ?? null,
            'image' => $option['image'] ?? null,
        ];

        if ($type === 'number') {
            $normalized['min'] = $option['min_value'] ?? $option['min'] ?? null;
            $normalized['max'] = $option['max_value'] ?? $option['max'] ?? null;
            $scale = $option['scale'] ?? null;
            $normalized['step'] = $scale !== null ? pow(0.1, (int) $scale) : 1;
            $normalized['unit'] = $option['unit_code'] ?? $option['unit'] ?? null;
            $normalized['reversible'] = $option['reversible'] ?? false;
        }

        if ($type === 'text') {
            $normalized['maxLength'] = $option['max_length'] ?? null;
            $normalized['placeholder'] = $option['placeholder'] ?? null;
        }

        return $normalized;
    }

    /**
     * Normalize choices from children array.
     */
    protected static function normalizeChoices(array $children, string $lang): array
    {
        return array_map(fn ($child) => [
            'value' => $child['code'] ?? $child['value'] ?? $child['id'] ?? '',
            'label' => self::extractLabel($child, $lang),
            'description' => self::extractDescription($child, $lang),
            'image' => self::extractImage($child),
        ], $children);
    }

    /**
     * Build a choice from a top-level option.
     */
    protected static function buildChoiceFromTopLevelOption(array $option): array
    {
        $children = $option['children'] ?? [];

        if (count($children) === 1) {
            $child = $children[0];

            return [
                'value' => $child['code'] ?? $option['code'] ?? '',
                'label' => $child['name'] ?? $option['name'] ?? $child['code'] ?? '',
                'description' => $child['description'] ?? $option['description'] ?? null,
                'image' => self::extractImage($child) ?? self::extractImage($option),
            ];
        }

        return [
            'value' => $option['code'] ?? '',
            'label' => $option['name'] ?? $option['code'] ?? '',
            'description' => $option['description'] ?? null,
            'image' => self::extractImage($option),
        ];
    }

    // ========================================
    // Type Determination
    // ========================================

    /**
     * Determine the type of a child option based on type_code.
     */
    protected static function determineChildType(array $child): string
    {
        $typeCode = strtolower($child['type_code'] ?? '');

        if (self::isCrossSellType($typeCode)) {
            return 'cross_sell';
        }

        if (in_array($typeCode, ['decimal', 'number', 'integer', 'float'])) {
            return 'number';
        }

        if (in_array($typeCode, ['amount', 'width', 'height', 'length', 'input'])) {
            $hasNumericConstraints = isset($child['min_value']) || isset($child['max_value']) || isset($child['unit_code']);

            if ($hasNumericConstraints || in_array($typeCode, ['amount', 'width', 'height', 'length'])) {
                return 'number';
            }

            return 'text';
        }

        if (in_array($typeCode, ['radio', 'select', 'dropdown']) || ! empty($child['children'])) {
            return 'select';
        }

        if ($typeCode === 'text') {
            return 'text';
        }

        return ! empty($child['children']) ? 'select' : 'text';
    }

    /**
     * Determine the option type from Probo's structure.
     */
    protected static function determineOptionType(array $option): string
    {
        $typeCode = $option['type_code'] ?? $option['input_type'] ?? null;

        if ($typeCode === 'radio' || ! empty($option['children'])) {
            return 'select';
        }

        if ($typeCode === 'input') {
            return self::determineInputType($option);
        }

        if (isset($option['input_type'])) {
            return match ($option['input_type']) {
                'number', 'integer', 'float', 'decimal' => 'number',
                'text', 'string' => 'text',
                'select', 'radio', 'dropdown' => 'select',
                default => 'text',
            };
        }

        if (self::hasNumericConstraints($option)) {
            return 'number';
        }

        return ! empty($option['children']) ? 'select' : 'text';
    }

    /**
     * Determine if an input type option is number or text.
     */
    protected static function determineInputType(array $option): string
    {
        $code = strtolower($option['code'] ?? '');
        $dimensionCodes = ['width', 'height', 'amount', 'quantity', 'length'];

        if (self::hasNumericConstraints($option) || in_array($code, $dimensionCodes)) {
            return 'number';
        }

        return 'text';
    }

    /**
     * Check if option has numeric constraints.
     */
    protected static function hasNumericConstraints(array $option): bool
    {
        return isset($option['min_value'])
            || isset($option['max_value'])
            || isset($option['min'])
            || isset($option['max'])
            || isset($option['unit_code'])
            || isset($option['unit'])
            || isset($option['scale']);
    }

    // ========================================
    // Utility Methods
    // ========================================

    /**
     * Add number constraints to an option array.
     */
    protected static function addNumberConstraints(array &$option, array $source): void
    {
        $option['min'] = $source['min_value'] ?? null;
        $option['max'] = $source['max_value'] ?? null;
        $scale = $source['scale'] ?? null;
        $option['step'] = $source['step_size'] ?? ($scale !== null ? pow(0.1, (int) $scale) : 1);
        $option['unit'] = $source['unit_code'] ?? null;
    }

    protected static function extractImage(array $data): ?string
    {
        if (! empty($data['images'])) {
            return $data['images'][0]['url'] ?? $data['images'][0] ?? null;
        }

        return $data['image'] ?? null;
    }

    protected static function extractLabel(array $data, string $lang): string
    {
        return $data['translations'][$lang]['title']
            ?? $data['translations']['en']['title']
            ?? $data['name']
            ?? $data['label']
            ?? $data['code']
            ?? '';
    }

    protected static function extractDescription(array $data, string $lang): ?string
    {
        return $data['translations'][$lang]['description']
            ?? $data['translations']['en']['description']
            ?? $data['description']
            ?? null;
    }

    /**
     * Normalize Probo's selected options into our format.
     */
    protected static function normalizeProboSelectedOptions(array $proboSelected): array
    {
        $normalized = [];

        foreach ($proboSelected as $key => $value) {
            if (is_array($value) && isset($value['code'])) {
                $normalized[$key] = $value['code'];
            } elseif (is_array($value) && isset($value['value'])) {
                $normalized[$key] = $value['value'];
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Determine the next step (option to configure).
     */
    protected static function determineNextStep(array $availableOptions, array $selectedOptions): ?string
    {
        foreach ($availableOptions as $option) {
            $code = $option['code'] ?? null;
            if ($code && ! isset($selectedOptions[$code])) {
                return $option['label'] ?? $code;
            }
        }

        return null;
    }

    // ========================================
    // Logging Methods
    // ========================================

    protected static function logRawOptions(array $proboOptions): void
    {
        logger()->debug('ConfiguratorResponse: Raw Probo options', [
            'count' => count($proboOptions),
            'options' => collect($proboOptions)->map(fn ($o) => [
                'code' => $o['code'] ?? 'unknown',
                'name' => $o['name'] ?? 'unknown',
                'has_children' => ! empty($o['children']),
                'children_count' => count($o['children'] ?? []),
                'children_types' => collect($o['children'] ?? [])->pluck('type_code')->toArray(),
            ])->toArray(),
        ]);
    }

    protected static function logTopLevelClassification(array $options, array $radioLike, array $input): void
    {
        logger()->debug('ConfiguratorResponse: Top-level structure analysis', [
            'option_count' => count($options),
            'radioLikeCount' => count($radioLike),
            'inputCount' => count($input),
            'option_codes' => collect($options)->pluck('code')->toArray(),
        ]);
    }

    protected static function logChildrenClassification(array $optionGroup, array $classified): void
    {
        logger()->debug('ConfiguratorResponse: Separated children', [
            'group_code' => $optionGroup['code'] ?? 'unknown',
            'total_children' => count($optionGroup['children'] ?? []),
            'radioLikeChildren' => count($classified['radioLike']),
            'inputTypeChildren' => count($classified['input']),
            'radioLikeCodes' => collect($classified['radioLike'])->pluck('code')->toArray(),
            'inputTypeCodes' => collect($classified['input'])->pluck('code')->toArray(),
        ]);
    }
}
