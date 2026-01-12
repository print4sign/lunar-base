<?php

namespace Lunar\Scraper\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Lunar\Scraper\Services\ImageDownloader;

class DownloadProductImages extends Command
{
    protected $signature = 'lunar:scraper:download-images
                            {--file= : Download images for a specific JSON file}
                            {--all : Download images for all JSON files}
                            {--skip-existing : Skip images that already exist locally}
                            {--skip-mockups : Skip mockup images (default: true)}
                            {--no-skip-mockups : Include mockup images}
                            {--update-json : Update JSON files with local image paths}';

    protected $description = 'Download all product images from scraped JSON files to reusable storage';

    protected ImageDownloader $downloader;

    public function handle(): int
    {
        $scraperPath = storage_path('app/scraper');

        if (! is_dir($scraperPath)) {
            $this->error("Scraper folder not found: {$scraperPath}");

            return self::FAILURE;
        }

        // Initialize image downloader
        $this->downloader = new ImageDownloader();

        // Determine which files to process
        $files = $this->getFilesToProcess($scraperPath);

        if (empty($files)) {
            $this->error('No files to process.');

            return self::FAILURE;
        }

        $this->info('Downloading images from '.count($files).' product(s)...');
        $this->info('Storage location: storage/app/public/scraped-products/');
        $this->newLine();

        $skipMockups = ! $this->option('no-skip-mockups');
        $skipExisting = $this->option('skip-existing');
        $updateJson = $this->option('update-json');

        $totalImages = 0;
        $downloaded = 0;
        $skipped = 0;
        $failed = 0;
        $jsonUpdated = 0;

        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();

        foreach ($files as $filePath) {
            $basename = basename($filePath, '.json');

            try {
                $content = File::get($filePath);
                $productData = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->newLine();
                    $this->warn("Skipping {$basename}: Invalid JSON");
                    $progressBar->advance();
                    continue;
                }

                $images = $productData['images'] ?? [];
                if (empty($images)) {
                    $progressBar->advance();
                    continue;
                }

                $productName = $productData['name'] ?? 'product';
                $prefix = Str::slug($productName);
                $imagesUpdated = false;

                foreach ($images as $index => $imageData) {
                    $totalImages++;

                    // Handle both formats: plain URL string or {url, alt} object
                    $url = is_array($imageData) ? ($imageData['url'] ?? null) : $imageData;

                    if (empty($url)) {
                        continue;
                    }

                    // Skip mockup images if requested
                    if ($skipMockups && $this->isMockupImage($url)) {
                        $skipped++;
                        continue;
                    }

                    // Generate filename with prefix and index
                    $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = $prefix.'-'.($index + 1).'.'.$extension;

                    // Check if file already exists
                    $existsLocally = $this->downloader->exists($filename);

                    if ($skipExisting && $existsLocally) {
                        $skipped++;
                        // Still update JSON with local path if requested
                        if ($updateJson) {
                            $localUrl = $this->downloader->getLocalUrl($filename);
                            if ($localUrl) {
                                $productData['images'][$index] = is_array($imageData)
                                    ? array_merge($imageData, ['url' => $localUrl, 'local_path' => $filename])
                                    : $localUrl;
                                $imagesUpdated = true;
                            }
                        }
                        continue;
                    }

                    // Download the image
                    $localUrl = $this->downloader->download($url, $filename);

                    if ($localUrl) {
                        $downloaded++;

                        // Update JSON with local path if requested
                        if ($updateJson) {
                            $productData['images'][$index] = is_array($imageData)
                                ? array_merge($imageData, ['url' => $localUrl, 'local_path' => $filename, 'original_url' => $url])
                                : $localUrl;
                            $imagesUpdated = true;
                        }
                    } else {
                        $failed++;
                    }
                }

                // Save updated JSON file if images were updated
                if ($updateJson && $imagesUpdated) {
                    File::put($filePath, json_encode($productData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    $jsonUpdated++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error processing {$basename}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Download complete!');

        $summaryData = [
            ['Total images found', $totalImages],
            ['Downloaded', $downloaded],
            ['Skipped (existing/mockups)', $skipped],
            ['Failed', $failed],
        ];

        if ($updateJson) {
            $summaryData[] = ['JSON files updated', $jsonUpdated];
        }

        $this->table(['Metric', 'Count'], $summaryData);

        $this->newLine();
        $this->info('Images are stored in: storage/app/public/scraped-products/');

        if ($updateJson && $jsonUpdated > 0) {
            $this->info("Updated {$jsonUpdated} JSON file(s) with local image paths.");
        }

        $this->info('These images will be reused when importing products.');

        return self::SUCCESS;
    }

    /**
     * Get the list of JSON files to process.
     */
    protected function getFilesToProcess(string $scraperPath): array
    {
        $files = [];

        if ($this->option('file')) {
            $filename = $this->option('file');
            // Add .json extension if not provided
            if (! str_ends_with($filename, '.json')) {
                $filename .= '.json';
            }
            $filePath = "{$scraperPath}/{$filename}";

            if (! file_exists($filePath)) {
                $this->error("File not found: {$filePath}");

                return [];
            }

            $files[] = $filePath;
        } elseif ($this->option('all')) {
            $files = glob("{$scraperPath}/*.json");
            // Exclude cookies file
            $files = array_filter($files, fn ($f) => ! str_contains($f, 'cookies'));
        } else {
            // List available files and let user choose
            $availableFiles = glob("{$scraperPath}/*.json");
            $availableFiles = array_filter($availableFiles, fn ($f) => ! str_contains($f, 'cookies'));

            if (empty($availableFiles)) {
                $this->error('No JSON files found in scraper folder.');
                $this->info('Run `php artisan lunar:scraper:probo-all` first to scrape products.');

                return [];
            }

            $this->info('Available scraped products: '.count($availableFiles));
            $this->newLine();
            $this->info('Use --file=<name> to download images for a specific file, or --all to download all.');

            return [];
        }

        return $files;
    }

    /**
     * Check if URL is a mockup image.
     */
    protected function isMockupImage(string $url): bool
    {
        $lowerUrl = strtolower($url);

        return str_contains($lowerUrl, 'mock up')
            || str_contains($lowerUrl, 'mock-up')
            || str_contains($lowerUrl, 'mockup')
            || str_contains($lowerUrl, 'mock%20up');
    }
}
