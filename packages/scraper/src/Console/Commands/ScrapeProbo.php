<?php

namespace Lunar\Scraper\Console\Commands;

use Illuminate\Console\Command;
use Lunar\Models\Supplier;
use Lunar\Scraper\Jobs\DownloadProductImages;
use Lunar\Scraper\Jobs\RewriteProductContent;
use Lunar\Scraper\Services\PlaywrightRunner;
use Lunar\Scraper\Services\ProboProductImporter;

class ScrapeProbo extends Command
{
    protected $signature = 'lunar:scraper:probo
                            {--url=* : The Probo URL(s) to scrape (can specify multiple)}
                            {--product : Scrape a single product instead of category}
                            {--multi : Scrape multiple products for attribute discovery}
                            {--headless : Run browser in headless mode}
                            {--no-headless : Run browser with visible window for debugging}
                            {--username= : Probo username/email}
                            {--password= : Probo password}
                            {--import : Import scraped products to Lunar}
                            {--supplier= : Supplier handle to link products to (default: probo)}
                            {--rewrite : Rewrite content with Claude AI and generate Folio pages}
                            {--download-images : Download original images from supplier}
                            {--update-images : Update existing images (re-download and replace)}
                            {--generate-images : Generate copyright-free images with AI}';

    protected $description = 'Scrape products from Probo.nl';

    protected string $defaultUrl = 'https://www.probo.nl/quapro-flag';

    protected string $loginUrl = 'https://www.probo.nl/customer/account/login';

    public function handle(): int
    {
        $urls = $this->option('url');
        $url = ! empty($urls) ? $urls[0] : $this->defaultUrl;
        $isProduct = $this->option('product');
        $isMulti = $this->option('multi');

        // Determine headless mode - default to headless unless --no-headless is passed
        $headless = ! $this->option('no-headless');
        if ($this->option('headless')) {
            $headless = true;
        }

        // Get credentials from options, config, or environment
        $username = $this->option('username')
            ?: config('lunar.scraper.probo.username')
            ?: env('PROBO_USERNAME');
        $password = $this->option('password')
            ?: config('lunar.scraper.probo.password')
            ?: env('PROBO_PASSWORD');

        if (! $username || ! $password) {
            $this->error('Probo credentials not configured.');
            $this->error('Set PROBO_USERNAME and PROBO_PASSWORD in .env, or pass --username and --password options.');

            return self::FAILURE;
        }

        $this->info('Starting Probo scraper...');

        // Determine command and mode
        if ($isMulti && ! empty($urls)) {
            $command = 'probo-multi';
            $this->info('Mode: Multi-product attribute discovery');
            $this->info('URLs to scrape: '.count($urls));
            foreach ($urls as $u) {
                $this->line("  - {$u}");
            }
        } elseif ($isProduct) {
            $command = 'probo-product';
            $this->info("URL: {$url}");
            $this->info('Mode: Single Product');
        } else {
            $command = 'probo-scrape';
            $this->info("URL: {$url}");
            $this->info('Mode: Category');
        }

        $runner = new PlaywrightRunner;

        // Cookie file path - we always start fresh to avoid stale session issues
        $cookiesFile = storage_path('app/scraper/probo-cookies.json');

        // Delete any existing cookies file to force fresh login
        if (file_exists($cookiesFile)) {
            unlink($cookiesFile);
            $this->info('Cleared previous session cookies');
        }

        $config = [
            'command' => $command,
            'url' => $url,
            'urls' => $urls ?: [$this->defaultUrl],
            'start_url' => $url,
            'base_url' => 'https://www.probo.nl',
            'headless' => $headless,
            'delay' => 2000,
            'timeout' => 60000,
            'cookies_file' => $cookiesFile,
            'auth' => [
                'type' => 'form',
                'login_url' => $this->loginUrl,
                'credentials' => [
                    'username' => $username,
                    'password' => $password,
                ],
                'selectors' => [
                    'username' => '#email',
                    'password' => '#pass',
                    'submit' => '#send2',
                ],
                'success_indicator' => '.customer-welcome',
            ],
        ];

        $this->info('Launching Playwright...');

        try {
            $result = $runner->run($config);

            if (! $result['success']) {
                $this->error('Scraping failed: '.($result['error'] ?? 'Unknown error'));

                return self::FAILURE;
            }

            $productCount = count($result['products'] ?? []);
            $this->info("Successfully scraped {$productCount} products");

            if (isset($result['category'])) {
                $this->info("Category: {$result['category']['name']}");
            }

            // Import products to Lunar if requested
            if ($this->option('import') && $productCount > 0) {
                $this->importProducts($result['products']);
            }

            // Always save to storage/app/scraper folder
            $scraperStoragePath = storage_path('app/scraper');
            if (! is_dir($scraperStoragePath)) {
                mkdir($scraperStoragePath, 0755, true);
            }

            // Save each product as a separate JSON file (named by URL slug)
            foreach ($result['products'] as $product) {
                // Extract slug from URL path (e.g., https://www.probo.nl/led-frame -> led-frame)
                $productSlug = 'unknown';
                if (! empty($product['url'])) {
                    $path = parse_url($product['url'], PHP_URL_PATH);
                    $productSlug = trim($path, '/');
                }
                $productSlug = preg_replace('/[^a-z0-9\-_]/i', '-', $productSlug);
                $productFile = "{$scraperStoragePath}/{$productSlug}.json";

                $productOutput = json_encode($product, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                file_put_contents($productFile, $productOutput);
                $this->info("Saved: {$productFile}");
            }

            // Display summary
            $this->newLine();
            $this->table(
                ['Field', 'Value'],
                [
                    ['Products Found', $productCount],
                    ['Category', $result['category']['name'] ?? 'N/A'],
                    ['Scraped At', $result['stats']['completed_at'] ?? 'N/A'],
                ]
            );

            if ($productCount > 0 && $productCount <= 10) {
                $this->newLine();
                $this->info('Products:');
                foreach ($result['products'] as $product) {
                    $this->line("  - {$product['name']} (SKU: {$product['sku']})");
                    if (! empty($product['price'])) {
                        $this->line("    Price: €{$product['price']}");
                    }
                }
            } elseif ($productCount > 10) {
                $this->newLine();
                $this->info('First 10 products:');
                foreach (array_slice($result['products'], 0, 10) as $product) {
                    $this->line("  - {$product['name']}");
                }
                $this->line("  ... and ".($productCount - 10).' more');
            }

            // Display discovered attributes for multi-product scrape
            if (! empty($result['discovered_attributes'])) {
                $this->newLine();
                $this->info('Discovered Attributes ('.count($result['discovered_attributes']).' unique):');
                foreach ($result['discovered_attributes'] as $attr) {
                    $this->line("  - {$attr['name']}: \"{$attr['sample_value']}\"");
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Import scraped products to Lunar.
     */
    protected function importProducts(array $products): void
    {
        $supplierHandle = $this->option('supplier') ?: 'probo';
        $supplier = Supplier::where('handle', $supplierHandle)->first();

        if (! $supplier) {
            $this->error("Supplier with handle '{$supplierHandle}' not found.");
            $this->error('Create the supplier first or specify a valid --supplier handle.');

            return;
        }

        $this->info("Importing products to Lunar (Supplier: {$supplier->name})...");

        $importer = new ProboProductImporter;

        // Set update images flag if requested
        if ($this->option('update-images')) {
            $importer->setUpdateImages(true);
            $this->info('Image update mode enabled - existing images will be replaced');
        }

        $imported = 0;
        $failed = 0;

        foreach ($products as $productData) {
            try {
                $supplierProduct = $importer->import($productData, $supplier);
                $imported++;

                $this->line("  Imported: {$supplierProduct->external_name}");

                // Dispatch image download job if requested
                if ($this->option('download-images')) {
                    DownloadProductImages::dispatch($supplierProduct);
                    $this->line("    -> Dispatched image download job");
                }

                // Dispatch content rewriting job if requested
                if ($this->option('rewrite')) {
                    RewriteProductContent::dispatch($supplierProduct);
                    $this->line("    -> Dispatched content rewriting job");
                }

                // AI image generation (not yet implemented)
                if ($this->option('generate-images')) {
                    $this->warn("    -> AI image generation not yet implemented");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  Failed to import: {$productData['name']} - {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Import complete: {$imported} imported, {$failed} failed");
    }
}
