<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Lunar\Models\CartLine;

class DesignerController extends Controller
{
    protected const PREVIEW_DPI = 150;
    protected const SAFE_MARGIN_MM = 3;

    public function show(string $locale, string $designerSegment, string $cartLineId, string $uploaderIndex = '0'): View
    {
        logger()->info('DesignerController::show called', ['cartLineId' => $cartLineId, 'uploaderIndex' => $uploaderIndex]);

        $cartLineId = (int) $cartLineId;
        $uploaderIndex = (int) $uploaderIndex;

        $cartLine = CartLine::with('purchasable')->findOrFail($cartLineId);

        // Resolve upload spec from cart line
        $uploadSpec = $cartLine->resolveUploadSpec();

        if (!$uploadSpec || !$uploadSpec->requiresUpload()) {
            abort(404, 'No upload specification found for this cart line.');
        }

        $uploaders = $uploadSpec->uploaders->toArray();

        if (!isset($uploaders[$uploaderIndex])) {
            abort(404, 'Uploader index not found.');
        }

        $uploader = $uploaders[$uploaderIndex];
        $canvasConfig = $this->calculateCanvasConfig($uploader);

        // Load existing designs from cart line meta
        $meta = $cartLine->meta ?? [];
        $designs = [
            'front' => $meta["design_{$uploaderIndex}_front"] ?? null,
            'back' => $meta["design_{$uploaderIndex}_back"] ?? null,
        ];

        // Get product name for display
        $productName = $cartLine->purchasable?->product?->translateAttribute('name')
            ?? $cartLine->purchasable?->getDescription()
            ?? 'Product';

        return view('designer.show', [
            'cartLineId' => $cartLineId,
            'uploaderIndex' => $uploaderIndex,
            'uploader' => $uploader,
            'canvasConfig' => $canvasConfig,
            'designs' => $designs,
            'productName' => $productName,
            'isFrontBack' => ($uploader['type'] ?? 'single') === 'frontback',
            'totalUploaders' => count($uploaders),
        ]);
    }

    public function save(Request $request, string $locale, string $designerSegment, string $cartLineId, string $uploaderIndex = '0')
    {
        $cartLine = CartLine::findOrFail($cartLineId);

        $validated = $request->validate([
            'side' => 'required|in:front,back',
            'design' => 'required|string',
        ]);

        $meta = $cartLine->meta ?? [];
        $key = "design_{$uploaderIndex}_{$validated['side']}";
        $meta[$key] = $validated['design'];

        $cartLine->update(['meta' => $meta]);

        return response()->json(['success' => true]);
    }

    public function confirm(Request $request, string $locale, string $designerSegment, string $cartLineId, string $uploaderIndex = '0')
    {
        $uploaderIndex = (int) $uploaderIndex;
        $cartLine = CartLine::findOrFail($cartLineId);

        $meta = $cartLine->meta ?? [];
        $meta["design_{$uploaderIndex}_complete"] = true;
        $meta['upload_method'] = 'online_designer';

        $cartLine->update(['meta' => $meta]);

        // Get current locale for redirect
        $locale = app()->getLocale();
        $checkoutSegment = __('routes.checkout');

        return response()->json([
            'success' => true,
            'redirect' => route('checkout.view', [
                'locale' => $locale,
                'checkoutSegment' => $checkoutSegment,
            ]),
        ]);
    }

    protected function mmToPixels(float $mm): int
    {
        return (int) round(($mm / 25.4) * self::PREVIEW_DPI);
    }

    protected function calculateCanvasConfig(array $spec): array
    {
        // Full canvas size (including bleed)
        $totalWidthMm = ($spec['width'] ?? 100)
            + ($spec['bleed_left'] ?? 0)
            + ($spec['bleed_right'] ?? 0);
        $totalHeightMm = ($spec['height'] ?? 100)
            + ($spec['bleed_top'] ?? 0)
            + ($spec['bleed_bottom'] ?? 0);

        return [
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
}
