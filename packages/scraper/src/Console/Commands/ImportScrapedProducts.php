<?php

namespace Lunar\Scraper\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;
use Lunar\Scraper\Jobs\DownloadProductImages;
use Lunar\Scraper\Jobs\RewriteProductContent;
use Lunar\Scraper\Services\ClaudeClient;
use Lunar\Scraper\Services\ProboProductImporter;

class ImportScrapedProducts extends Command
{
    protected $signature = 'lunar:scraper:import
                            {--file= : Import a specific JSON file (filename without path)}
                            {--all : Import all JSON files in the scraper folder}
                            {--supplier=probo : Supplier handle to link products to}
                            {--rewrite : Rewrite content with Claude AI and generate Folio pages}
                            {--download-images : Download original images from supplier}
                            {--skip-imported : Skip products that are already imported (have SupplierProduct)}';

    protected $description = 'Import products from scraped JSON files in storage/app/scraper';

    public function handle(): int
    {
        $scraperPath = storage_path('app/scraper');

        if (! is_dir($scraperPath)) {
            $this->error("Scraper folder not found: {$scraperPath}");

            return self::FAILURE;
        }

        // Get supplier
        $supplierHandle = $this->option('supplier');
        $supplier = Supplier::where('handle', $supplierHandle)->first();

        if (! $supplier) {
            $this->error("Supplier with handle '{$supplierHandle}' not found.");

            return self::FAILURE;
        }

        // Determine which files to import
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

                return self::FAILURE;
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
                $this->info('Run `php artisan lunar:scraper:probo --url=... --product` first to scrape products.');

                return self::FAILURE;
            }

            $this->info('Available scraped products:');
            foreach ($availableFiles as $index => $file) {
                $basename = basename($file, '.json');
                $this->line("  [{$index}] {$basename}");
            }

            $this->newLine();
            $this->info('Use --file=<name> to import a specific file, or --all to import all files.');

            return self::SUCCESS;
        }

        if (empty($files)) {
            $this->error('No files to import.');

            return self::FAILURE;
        }

        $this->info("Importing ".count($files)." product(s) to Lunar (Supplier: {$supplier->name})...");
        $this->newLine();

        // Initialize importer with ClaudeClient for translations (specs stored raw, format later)
        $claudeClient = new ClaudeClient();
        $importer = new ProboProductImporter($claudeClient);
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($files as $filePath) {
            $basename = basename($filePath, '.json');

            try {
                $content = File::get($filePath);
                $productData = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON: '.json_last_error_msg());
                }

                // Check if product already exists if --skip-imported option is set
                if ($this->option('skip-imported')) {
                    $externalId = $this->generateExternalId($productData);
                    $existingSupplierProduct = SupplierProduct::where('supplier_id', $supplier->id)
                        ->where('external_id', $externalId)
                        ->first();

                    if ($existingSupplierProduct) {
                        $skipped++;
                        $this->line("<fg=yellow>Skipped (already imported): {$basename}</>");
                        $this->line("  -> SupplierProduct ID: {$existingSupplierProduct->id}");
                        $this->line("  -> Product ID: {$existingSupplierProduct->product_id}");
                        continue;
                    }
                }

                $supplierProduct = $importer->import($productData, $supplier);

                // Check if this was a new import or an update
                $wasNew = $supplierProduct->wasRecentlyCreated;
                if ($wasNew) {
                    $imported++;
                } else {
                    $updated++;
                }

                $status = $wasNew ? 'Imported' : 'Updated';
                $this->info("{$status}: {$basename}");
                $this->line("  -> SupplierProduct ID: {$supplierProduct->id}");
                $this->line("  -> Product ID: {$supplierProduct->product_id}");

                // Dispatch image download job if requested
                if ($this->option('download-images')) {
                    DownloadProductImages::dispatch($supplierProduct);
                    $this->line('  -> Dispatched image download job');
                }

                // Dispatch content rewriting job if requested
                if ($this->option('rewrite')) {
                    RewriteProductContent::dispatch($supplierProduct);
                    $this->line('  -> Dispatched content rewriting job');
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed: {$basename} - {$e->getMessage()}");
            }
        }

        $this->newLine();

        // Build summary message
        $summary = "Import complete: {$imported} new";
        if ($updated > 0) {
            $summary .= ", {$updated} updated";
        }
        if ($skipped > 0) {
            $summary .= ", {$skipped} skipped";
        }
        if ($failed > 0) {
            $summary .= ", {$failed} failed";
        }

        $this->info($summary);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Generate external ID from scraped product data.
     * This mirrors the logic in ProboProductImporter::generateExternalId()
     */
    protected function generateExternalId(array $scrapedProduct): ?string
    {
        // First, try to use the URL path as it's the most stable
        if (! empty($scrapedProduct['url'])) {
            $path = parse_url($scrapedProduct['url'], PHP_URL_PATH);
            if ($path) {
                // Remove leading/trailing slashes and convert to slug
                return trim($path, '/');
            }
        }

        // Fall back to scraped external_id (usually derived from URL)
        if (! empty($scrapedProduct['external_id'])) {
            return $scrapedProduct['external_id'];
        }

        // Fall back to product_id from the page
        if (! empty($scrapedProduct['product_id']) || ! empty($scrapedProduct['meta']['product_id'])) {
            return $scrapedProduct['product_id'] ?? $scrapedProduct['meta']['product_id'];
        }

        // Last resort: use SKU (but this can change with login state)
        return $scrapedProduct['sku'] ?? null;
    }
}
