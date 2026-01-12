<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Models\CartLine;

class OnlineDesigner extends Component
{
    public int $cartLineId;

    public array $uploaderRequirement = [];

    public int $uploaderIndex = 0;

    // For frontback type
    public string $currentSide = 'front';

    // Design JSON storage per side
    public array $designs = [
        'front' => null,
        'back' => null,
    ];

    // Canvas configuration (calculated from specs)
    public array $canvasConfig = [];

    protected const PREVIEW_DPI = 150;

    protected const SAFE_MARGIN_MM = 3;

    public function mount(int $cartLineId, array $uploaderRequirement, int $uploaderIndex = 0): void
    {
        $this->cartLineId = $cartLineId;
        $this->uploaderRequirement = $uploaderRequirement;
        $this->uploaderIndex = $uploaderIndex;

        $this->calculateCanvasConfig();
        $this->loadExistingDesigns();
    }

    protected function mmToPixels(float $mm): int
    {
        return (int) round(($mm / 25.4) * self::PREVIEW_DPI);
    }

    protected function calculateCanvasConfig(): void
    {
        $spec = $this->uploaderRequirement;

        // Full canvas size (including bleed)
        $totalWidthMm = ($spec['width'] ?? 100)
            + ($spec['bleed_left'] ?? 0)
            + ($spec['bleed_right'] ?? 0);
        $totalHeightMm = ($spec['height'] ?? 100)
            + ($spec['bleed_top'] ?? 0)
            + ($spec['bleed_bottom'] ?? 0);

        $this->canvasConfig = [
            // Canvas dimensions in pixels
            'width' => $this->mmToPixels($totalWidthMm),
            'height' => $this->mmToPixels($totalHeightMm),

            // Trim line position (inside bleed)
            'trimX' => $this->mmToPixels($spec['bleed_left'] ?? 0),
            'trimY' => $this->mmToPixels($spec['bleed_top'] ?? 0),
            'trimWidth' => $this->mmToPixels($spec['width'] ?? 100),
            'trimHeight' => $this->mmToPixels($spec['height'] ?? 100),

            // Safe zone (3mm inside trim)
            'safeMargin' => $this->mmToPixels(self::SAFE_MARGIN_MM),

            // Original mm values for display
            'widthMm' => $spec['width'] ?? 100,
            'heightMm' => $spec['height'] ?? 100,
            'bleedTop' => $spec['bleed_top'] ?? 0,
            'bleedRight' => $spec['bleed_right'] ?? 0,
            'bleedBottom' => $spec['bleed_bottom'] ?? 0,
            'bleedLeft' => $spec['bleed_left'] ?? 0,

            // DPI settings
            'previewDpi' => self::PREVIEW_DPI,
            'outputDpi' => $spec['minimal_dpi'] ?? 150,

            // Uploader type
            'type' => $spec['type'] ?? 'single',
        ];
    }

    protected function loadExistingDesigns(): void
    {
        $cartLine = CartLine::find($this->cartLineId);
        if (! $cartLine) {
            return;
        }

        $meta = $cartLine->meta ?? [];

        $frontKey = "design_{$this->uploaderIndex}_front";
        if (isset($meta[$frontKey])) {
            $this->designs['front'] = $meta[$frontKey];
        }

        $backKey = "design_{$this->uploaderIndex}_back";
        if (isset($meta[$backKey])) {
            $this->designs['back'] = $meta[$backKey];
        }
    }

    public function switchSide(string $side): void
    {
        if (! in_array($side, ['front', 'back'])) {
            return;
        }

        // Save current design before switching
        $this->dispatch('save-current-design');

        $this->currentSide = $side;

        // Load design for new side
        $this->dispatch('load-design', design: $this->designs[$side]);
    }

    #[On('design-updated')]
    public function saveDesign(string $designJson, string $side): void
    {
        $this->designs[$side] = $designJson;

        $cartLine = CartLine::find($this->cartLineId);
        if (! $cartLine) {
            return;
        }

        $meta = $cartLine->meta ?? [];
        $key = "design_{$this->uploaderIndex}_{$side}";
        $meta[$key] = $designJson;

        $cartLine->update(['meta' => $meta]);
    }

    public function confirmDesign(): void
    {
        $this->dispatch('save-current-design');

        $cartLine = CartLine::find($this->cartLineId);
        if ($cartLine) {
            $meta = $cartLine->meta ?? [];
            $meta["design_{$this->uploaderIndex}_complete"] = true;
            $meta['upload_method'] = 'online_designer';
            $cartLine->update(['meta' => $meta]);
        }

        $this->dispatch('design-confirmed', uploaderIndex: $this->uploaderIndex);
    }

    #[Computed]
    public function isFrontBack(): bool
    {
        return ($this->uploaderRequirement['type'] ?? 'single') === 'frontback';
    }

    #[Computed]
    public function hasDesign(): bool
    {
        if ($this->isFrontBack) {
            return ! empty($this->designs['front']) && ! empty($this->designs['back']);
        }

        return ! empty($this->designs['front']);
    }

    #[Computed]
    public function hasBleed(): bool
    {
        return ($this->canvasConfig['bleedTop'] ?? 0) > 0
            || ($this->canvasConfig['bleedRight'] ?? 0) > 0
            || ($this->canvasConfig['bleedBottom'] ?? 0) > 0
            || ($this->canvasConfig['bleedLeft'] ?? 0) > 0;
    }

    public function render()
    {
        return view('livewire.components.online-designer');
    }
}
