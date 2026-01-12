<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Models\CartLine;

/**
 * Reusable component to display upload/delivery specifications.
 *
 * Can be used:
 * - With a cart line ID (fetches spec from cart line)
 * - With a raw upload spec array (passed directly)
 * - Inside a modal or standalone on any page
 */
class DeliverySpecs extends Component
{
    /**
     * Cart line ID to fetch spec from.
     */
    public ?int $cartLineId = null;

    /**
     * Upload spec array (can be passed directly instead of cart line).
     */
    public ?array $uploadSpec = null;

    /**
     * Template download URL.
     */
    public ?string $templateUrl = null;

    /**
     * Whether to show the template download section.
     */
    public bool $showTemplate = true;

    /**
     * Whether to show the "more info" link at the bottom.
     */
    public bool $showMoreInfoLink = true;

    /**
     * URL for the "more info" link.
     */
    public ?string $moreInfoUrl = null;

    /**
     * Whether specs are in a compact view (less padding).
     */
    public bool $compact = false;

    public function mount(
        ?int $cartLineId = null,
        ?array $uploadSpec = null,
        ?string $templateUrl = null,
        bool $showTemplate = true,
        bool $showMoreInfoLink = true,
        ?string $moreInfoUrl = null,
        bool $compact = false
    ): void {
        $this->cartLineId = $cartLineId;
        $this->templateUrl = $templateUrl;
        $this->showTemplate = $showTemplate;
        $this->showMoreInfoLink = $showMoreInfoLink;
        $this->moreInfoUrl = $moreInfoUrl;
        $this->compact = $compact;

        // If upload spec is passed directly, use it
        if ($uploadSpec !== null) {
            $this->uploadSpec = $uploadSpec;
        } elseif ($cartLineId !== null) {
            // Otherwise, fetch from cart line
            $this->loadFromCartLine();
        }
    }

    /**
     * Load upload spec from cart line.
     */
    public function loadFromCartLine(): void
    {
        if (! $this->cartLineId) {
            return;
        }

        $cartLine = CartLine::find($this->cartLineId);

        if (! $cartLine) {
            return;
        }

        // Get upload spec
        $spec = $cartLine->resolveUploadSpec();

        if ($spec instanceof UploadSpec) {
            $this->uploadSpec = $spec->toArray();
        }

        // Get template URL from cart line meta if not already set
        if ($this->templateUrl === null) {
            $this->templateUrl = $cartLine->meta['template_url'] ?? null;
        }
    }

    /**
     * Set the upload spec programmatically.
     */
    public function setUploadSpec(array $spec): void
    {
        $this->uploadSpec = $spec;
    }

    /**
     * Get the first uploader spec (most products have one).
     */
    #[Computed]
    public function primaryUploader(): ?array
    {
        $uploaders = $this->uploadSpec['uploaders'] ?? [];

        return $uploaders[0] ?? null;
    }

    /**
     * Get all uploaders.
     */
    #[Computed]
    public function uploaders(): array
    {
        return $this->uploadSpec['uploaders'] ?? [];
    }

    /**
     * Check if there are any specs to display.
     */
    #[Computed]
    public function hasSpecs(): bool
    {
        return ! empty($this->uploadSpec['uploaders']);
    }

    /**
     * Get the uploader type label.
     */
    public function getUploaderTypeLabel(string $type): string
    {
        return match ($type) {
            'single' => __('delivery_specs.types.single'),
            'frontback' => __('delivery_specs.types.frontback'),
            'multipage' => __('delivery_specs.types.multipage'),
            'custom' => __('delivery_specs.types.custom'),
            default => $type,
        };
    }

    /**
     * Format dimensions for display.
     */
    public function formatDimensions(?float $width, ?float $height, ?float $length = null): string
    {
        if ($width === null && $height === null) {
            return '-';
        }

        $parts = [];

        if ($width !== null) {
            $parts[] = number_format($width, 0, ',', '.').' mm';
        }

        if ($height !== null) {
            if (count($parts) > 0) {
                $parts[] = 'x '.number_format($height, 0, ',', '.').' mm';
            } else {
                $parts[] = number_format($height, 0, ',', '.').' mm';
            }
        }

        if ($length !== null && $length > 0) {
            $parts[] = 'x '.number_format($length, 0, ',', '.').' mm';
        }

        return implode(' ', $parts);
    }

    /**
     * Format file size limit.
     */
    public function formatFileLimit(int $megabytes): string
    {
        if ($megabytes >= 1000) {
            return number_format($megabytes / 1000, 0, ',', '.').' GB';
        }

        return $megabytes.' MB';
    }

    /**
     * Check if bleed is set (any side > 0).
     */
    public function hasBleed(array $uploader): bool
    {
        return ($uploader['bleed_top'] ?? 0) > 0
            || ($uploader['bleed_right'] ?? 0) > 0
            || ($uploader['bleed_bottom'] ?? 0) > 0
            || ($uploader['bleed_left'] ?? 0) > 0;
    }

    /**
     * Check if cut marks are required.
     */
    public function hasCutMarks(array $uploader): bool
    {
        return ! empty($uploader['required_cut_names'] ?? [])
            || ! empty($uploader['optional_cut_names'] ?? []);
    }

    public function render()
    {
        return view('livewire.components.delivery-specs');
    }
}
