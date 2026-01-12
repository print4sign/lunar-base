<?php

namespace Lunar\Base\DataTransferObjects\Upload;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents upload requirements for a single uploader slot.
 *
 * This matches Probo's uploader structure exactly for seamless integration.
 */
class UploaderRequirement implements Arrayable, \JsonSerializable
{
    public const TYPE_SINGLE = 'single';

    public const TYPE_FRONTBACK = 'frontback';

    public const TYPE_MULTIPAGE = 'multipage';

    public const TYPE_CUSTOM = 'custom';

    public const TILING_HORIZONTAL = 'horizontal';

    public const TILING_VERTICAL = 'vertical';

    public function __construct(
        // Core settings
        public readonly string $type = self::TYPE_SINGLE,
        public readonly int $amount = 1,
        public readonly ?float $width = null,
        public readonly ?float $height = null,
        public readonly ?float $length = null,

        // DPI and file settings
        public readonly int $minimalDpi = 72,
        public readonly int $fileLimit = 500, // MB

        // Feature toggles
        public readonly bool $mirrorEnabled = false,
        public readonly bool $fillEnabled = true,
        public readonly bool $rotationEnabled = true,
        public readonly bool $requireWhiteSpot = false,

        // Cut names (for specialty products like stickers)
        public readonly array $requiredCutNames = [],
        public readonly array $optionalCutNames = [],

        // Tiling options (for large format prints)
        public readonly bool $tilingEnabled = false,
        public readonly bool $tilingMandatory = false,
        public readonly ?string $tilingDirection = null,
        public readonly ?float $maxWidthExclOverlap = null,
        public readonly ?float $maxHeightExclOverlap = null,
        public readonly ?float $maxWidthInclOverlap = null,
        public readonly ?float $maxHeightInclOverlap = null,

        // Bleed settings (in mm)
        public readonly float $bleedTop = 0,
        public readonly float $bleedRight = 0,
        public readonly float $bleedBottom = 0,
        public readonly float $bleedLeft = 0,
    ) {}

    /**
     * Create an UploaderRequirement from an array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? self::TYPE_SINGLE,
            amount: (int) ($data['amount'] ?? 1),
            width: isset($data['width']) ? (float) $data['width'] : null,
            height: isset($data['height']) ? (float) $data['height'] : null,
            length: isset($data['length']) ? (float) $data['length'] : null,

            minimalDpi: (int) ($data['minimal_dpi'] ?? $data['minimalDpi'] ?? 72),
            fileLimit: (int) ($data['file_limit'] ?? $data['fileLimit'] ?? 500),

            mirrorEnabled: (bool) ($data['mirror_enabled'] ?? $data['mirrorEnabled'] ?? false),
            fillEnabled: (bool) ($data['fill_enabled'] ?? $data['fillEnabled'] ?? true),
            rotationEnabled: (bool) ($data['rotation_enabled'] ?? $data['rotationEnabled'] ?? true),
            requireWhiteSpot: (bool) ($data['require_white_spot'] ?? $data['requireWhiteSpot'] ?? false),

            requiredCutNames: $data['required_cut_names'] ?? $data['requiredCutNames'] ?? [],
            optionalCutNames: $data['optional_cut_names'] ?? $data['optionalCutNames'] ?? [],

            tilingEnabled: (bool) ($data['tiling_enabled'] ?? $data['tilingEnabled'] ?? false),
            tilingMandatory: (bool) ($data['tiling_mandatory'] ?? $data['tilingMandatory'] ?? false),
            tilingDirection: $data['tiling_direction'] ?? $data['tilingDirection'] ?? null,
            maxWidthExclOverlap: isset($data['max_width_excl_overlap']) || isset($data['maxWidthExclOverlap'])
                ? (float) ($data['max_width_excl_overlap'] ?? $data['maxWidthExclOverlap'])
                : null,
            maxHeightExclOverlap: isset($data['max_height_excl_overlap']) || isset($data['maxHeightExclOverlap'])
                ? (float) ($data['max_height_excl_overlap'] ?? $data['maxHeightExclOverlap'])
                : null,
            maxWidthInclOverlap: isset($data['max_width_incl_overlap']) || isset($data['maxWidthInclOverlap'])
                ? (float) ($data['max_width_incl_overlap'] ?? $data['maxWidthInclOverlap'])
                : null,
            maxHeightInclOverlap: isset($data['max_height_incl_overlap']) || isset($data['maxHeightInclOverlap'])
                ? (float) ($data['max_height_incl_overlap'] ?? $data['maxHeightInclOverlap'])
                : null,

            bleedTop: (float) ($data['bleed_top'] ?? $data['bleedTop'] ?? 0),
            bleedRight: (float) ($data['bleed_right'] ?? $data['bleedRight'] ?? 0),
            bleedBottom: (float) ($data['bleed_bottom'] ?? $data['bleedBottom'] ?? 0),
            bleedLeft: (float) ($data['bleed_left'] ?? $data['bleedLeft'] ?? 0),
        );
    }

    /**
     * Create a new UploaderRequirement with dimensions overridden.
     *
     * This is used for dimensional products where the dimensions
     * come from the customer's configuration.
     */
    public function withDimensions(?float $width, ?float $height, ?float $length = null): self
    {
        return new self(
            type: $this->type,
            amount: $this->amount,
            width: $width ?? $this->width,
            height: $height ?? $this->height,
            length: $length ?? $this->length,

            minimalDpi: $this->minimalDpi,
            fileLimit: $this->fileLimit,

            mirrorEnabled: $this->mirrorEnabled,
            fillEnabled: $this->fillEnabled,
            rotationEnabled: $this->rotationEnabled,
            requireWhiteSpot: $this->requireWhiteSpot,

            requiredCutNames: $this->requiredCutNames,
            optionalCutNames: $this->optionalCutNames,

            tilingEnabled: $this->tilingEnabled,
            tilingMandatory: $this->tilingMandatory,
            tilingDirection: $this->tilingDirection,
            maxWidthExclOverlap: $this->maxWidthExclOverlap,
            maxHeightExclOverlap: $this->maxHeightExclOverlap,
            maxWidthInclOverlap: $this->maxWidthInclOverlap,
            maxHeightInclOverlap: $this->maxHeightInclOverlap,

            bleedTop: $this->bleedTop,
            bleedRight: $this->bleedRight,
            bleedBottom: $this->bleedBottom,
            bleedLeft: $this->bleedLeft,
        );
    }

    /**
     * Get the number of files required for this uploader.
     */
    public function getRequiredFileCount(): int
    {
        if ($this->type === self::TYPE_FRONTBACK) {
            return 2;
        }

        return $this->amount;
    }

    /**
     * Check if this uploader requires a cut file (for stickers, etc).
     */
    public function requiresCutFile(): bool
    {
        return ! empty($this->requiredCutNames);
    }

    /**
     * Get the effective dimensions including bleed.
     */
    public function getDimensionsWithBleed(): array
    {
        return [
            'width' => $this->width !== null
                ? $this->width + $this->bleedLeft + $this->bleedRight
                : null,
            'height' => $this->height !== null
                ? $this->height + $this->bleedTop + $this->bleedBottom
                : null,
            'length' => $this->length,
        ];
    }

    /**
     * Convert the UploaderRequirement to an array.
     *
     * Uses snake_case keys to match Probo's API format.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'amount' => $this->amount,
            'width' => $this->width,
            'height' => $this->height,
            'length' => $this->length,

            'minimal_dpi' => $this->minimalDpi,
            'file_limit' => $this->fileLimit,

            'mirror_enabled' => $this->mirrorEnabled,
            'fill_enabled' => $this->fillEnabled,
            'rotation_enabled' => $this->rotationEnabled,
            'require_white_spot' => $this->requireWhiteSpot,

            'required_cut_names' => $this->requiredCutNames,
            'optional_cut_names' => $this->optionalCutNames,

            'tiling_enabled' => $this->tilingEnabled,
            'tiling_mandatory' => $this->tilingMandatory,
            'tiling_direction' => $this->tilingDirection,
            'max_width_excl_overlap' => $this->maxWidthExclOverlap,
            'max_height_excl_overlap' => $this->maxHeightExclOverlap,
            'max_width_incl_overlap' => $this->maxWidthInclOverlap,
            'max_height_incl_overlap' => $this->maxHeightInclOverlap,

            'bleed_top' => $this->bleedTop,
            'bleed_right' => $this->bleedRight,
            'bleed_bottom' => $this->bleedBottom,
            'bleed_left' => $this->bleedLeft,
        ];
    }

    /**
     * Serialize the UploaderRequirement to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
