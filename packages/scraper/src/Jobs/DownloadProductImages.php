<?php

namespace Lunar\Scraper\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lunar\Models\SupplierProduct;
use Lunar\Scraper\Services\ImageDownloader;

class DownloadProductImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes for downloading multiple images

    public int $backoff = 30;

    public function __construct(
        public SupplierProduct $supplierProduct
    ) {}

    public function handle(ImageDownloader $downloader): void
    {
        $externalData = $this->supplierProduct->external_data ?? [];
        $images = $externalData['images'] ?? [];
        $productName = $this->supplierProduct->external_name;

        if (empty($images)) {
            Log::info("No images to download for supplier product: {$this->supplierProduct->id}");

            return;
        }

        // Check if product already has images in media library
        if ($this->supplierProduct->product && $this->supplierProduct->product->getMedia('images')->count() > 0) {
            Log::info("Product already has images in media library, skipping download", [
                'product_id' => $this->supplierProduct->product->id,
                'existing_images' => $this->supplierProduct->product->getMedia('images')->count(),
            ]);

            return;
        }

        $count = count($images);
        Log::info("Processing {$count} images for: {$productName}");

        // Generate a prefix based on product name for organized storage
        $prefix = Str::slug($productName);

        // Normalize images to extract URLs (handle both plain URLs and {url, alt} objects)
        $imageUrls = array_map(fn ($img) => is_array($img) ? ($img['url'] ?? '') : $img, $images);
        $imageUrls = array_filter($imageUrls); // Remove empty URLs

        // Download all images (ImageDownloader will skip existing local files automatically)
        $results = $downloader->downloadMultiple($imageUrls, $prefix);

        // Store results in external_data
        $externalData['downloaded_images'] = $results;
        $externalData['images_downloaded_at'] = now()->toISOString();

        // Create a mapping of original to local URLs with alt text for easy lookup
        $imageMap = [];
        $imageAlts = [];
        foreach ($results as $index => $result) {
            if ($result['success'] && $result['local']) {
                $imageMap[$result['original']] = $result['local'];
                // Get alt text from original image data
                $originalImage = $images[$index] ?? null;
                $alt = is_array($originalImage) ? ($originalImage['alt'] ?? '') : '';
                $imageAlts[$result['local']] = $alt;
            }
        }
        $externalData['image_map'] = $imageMap;
        $externalData['image_alts'] = $imageAlts;

        $this->supplierProduct->update([
            'external_data' => $externalData,
        ]);

        $successCount = count(array_filter($results, fn ($r) => $r['success']));
        Log::info("Downloaded {$successCount}/{$count} images for: {$productName}", [
            'success_count' => $successCount,
            'total_count' => count($images),
        ]);

        // If there's an associated Lunar Product, attach images to media library
        if ($this->supplierProduct->product) {
            $this->attachToMediaLibrary($imageMap, $imageAlts);
        }
    }

    /**
     * Attach downloaded images to the Lunar Product's media library.
     *
     * @param  array  $imageMap  Mapping of original URLs to local URLs
     * @param  array  $imageAlts  Mapping of local URLs to alt text
     */
    protected function attachToMediaLibrary(array $imageMap, array $imageAlts = []): void
    {
        $product = $this->supplierProduct->product;

        foreach ($imageMap as $originalUrl => $localUrl) {
            try {
                // Convert URL to path
                $path = str_replace('/storage/', '', parse_url($localUrl, PHP_URL_PATH));
                $fullPath = storage_path('app/public/'.$path);

                if (file_exists($fullPath)) {
                    $alt = $imageAlts[$localUrl] ?? '';

                    $media = $product->addMedia($fullPath)
                        ->preservingOriginal()
                        ->withCustomProperties([
                            'alt' => $alt,
                            'original_url' => $originalUrl,
                        ])
                        ->toMediaCollection('images');

                    // Set the name (used as alt text in some contexts)
                    if ($alt) {
                        $media->update(['name' => $alt]);
                    }

                    Log::info("Attached image to product media library", [
                        'path' => $path,
                        'alt' => $alt,
                        'media_id' => $media->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning("Failed to attach image to media library", [
                    'url' => $localUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function tags(): array
    {
        return [
            'scraper',
            'download-images',
            "supplier-product:{$this->supplierProduct->id}",
        ];
    }
}
