<?php

namespace Lunar\Scraper\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lunar\Models\SupplierProduct;
use Lunar\Scraper\Services\ClaudeClient;
use Lunar\Scraper\Services\VoltPageGenerator;

class RewriteProductContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public int $backoff = 30;

    public function __construct(
        public SupplierProduct $supplierProduct
    ) {}

    public function handle(ClaudeClient $claude, VoltPageGenerator $generator): void
    {
        $externalData = $this->supplierProduct->external_data ?? [];
        $htmlContent = $externalData['html_content'] ?? '';
        $productName = $this->supplierProduct->external_name;

        if (empty($htmlContent)) {
            Log::warning("No HTML content to rewrite for supplier product: {$this->supplierProduct->id}");

            return;
        }

        Log::info("Starting content rewrite for: {$productName}");

        // Get downloaded image URLs if available
        $imageUrls = $this->getLocalImageUrls($externalData);

        // Rewrite HTML content with Claude (returns raw HTML)
        $rewrittenHtml = $claude->rewriteHtml($htmlContent, $productName, $imageUrls);

        if (empty($rewrittenHtml)) {
            Log::error("Claude returned empty HTML for supplier product: {$this->supplierProduct->id}");

            return;
        }

        // Store rewritten HTML in external_data
        $externalData['rewritten_html'] = $rewrittenHtml;
        $externalData['rewritten_at'] = now()->toISOString();

        $this->supplierProduct->update([
            'external_data' => $externalData,
        ]);

        // Generate Folio page file with the product name as filename
        $pagePath = $generator->generate($this->supplierProduct, $rewrittenHtml);

        Log::info("Generated Folio page for {$productName}: {$pagePath}");
    }

    /**
     * Get local image URLs from downloaded images.
     */
    protected function getLocalImageUrls(array $externalData): array
    {
        // Check for downloaded images mapping
        $imageMap = $externalData['image_map'] ?? [];

        if (! empty($imageMap)) {
            return array_values($imageMap);
        }

        // Fallback to original image URLs if not downloaded yet
        return $externalData['images'] ?? [];
    }

    public function tags(): array
    {
        return [
            'scraper',
            'rewrite-content',
            "supplier-product:{$this->supplierProduct->id}",
        ];
    }
}
