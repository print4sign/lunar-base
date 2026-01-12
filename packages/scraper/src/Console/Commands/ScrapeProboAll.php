<?php

namespace Lunar\Scraper\Console\Commands;

use Illuminate\Console\Command;
use Lunar\Models\Supplier;
use Lunar\Scraper\Jobs\DownloadProductImages;
use Lunar\Scraper\Jobs\RewriteProductContent;
use Lunar\Scraper\Services\PlaywrightRunner;
use Lunar\Scraper\Services\ProboProductImporter;

class ScrapeProboAll extends Command
{
    protected $signature = 'lunar:scraper:probo-all
                            {--sitemap= : Path to sitemap.xml file}
                            {--limit=0 : Limit number of URLs to process (0 = no limit)}
                            {--offset=0 : Skip first N URLs}
                            {--output= : Output file path for JSON results}
                            {--headless : Run browser in headless mode (default)}
                            {--no-headless : Run browser with visible window for debugging}
                            {--username= : Probo username/email}
                            {--password= : Probo password}
                            {--import : Import scraped products to Lunar}
                            {--supplier= : Supplier handle to link products to (default: probo)}
                            {--rewrite : Rewrite content with Claude AI}
                            {--download-images : Download original images from supplier}
                            {--update-images : Update existing images (re-download and replace)}
                            {--dry-run : Only show which URLs would be scraped}
                            {--skip-existing : Skip products that already have JSON files}';

    protected $description = 'Scrape all products from Probo.nl sitemap';

    protected string $defaultSitemapPath = 'vendor/lunarphp/scraper/sitemap.xml';

    protected string $loginUrl = 'https://www.probo.nl/customer/account/login';

    public function handle(): int
    {
        // Get sitemap path
        $sitemapPath = $this->option('sitemap') ?: base_path($this->defaultSitemapPath);

        if (! file_exists($sitemapPath)) {
            $this->error("Sitemap not found: {$sitemapPath}");

            return self::FAILURE;
        }

        // Parse sitemap and extract URLs
        $this->info('Parsing sitemap...');
        $urls = $this->extractProductUrls($sitemapPath);

        $this->info('Found '.count($urls).' potential product URLs');

        // Apply offset and limit
        $offset = (int) $this->option('offset');
        $limit = (int) $this->option('limit');

        if ($offset > 0) {
            $urls = array_slice($urls, $offset);
            $this->info("Skipping first {$offset} URLs");
        }

        if ($limit > 0) {
            $urls = array_slice($urls, 0, $limit);
            $this->info("Limiting to {$limit} URLs");
        }

        // Prepare storage path
        $scraperStoragePath = storage_path('app/scraper');
        if (! is_dir($scraperStoragePath)) {
            mkdir($scraperStoragePath, 0755, true);
        }

        // Skip existing if requested
        if ($this->option('skip-existing')) {
            $originalCount = count($urls);
            $urls = array_filter($urls, function ($url) use ($scraperStoragePath) {
                $slug = $this->urlToSlug($url);

                return ! file_exists("{$scraperStoragePath}/{$slug}.json");
            });
            $urls = array_values($urls);
            $skippedExisting = $originalCount - count($urls);
            if ($skippedExisting > 0) {
                $this->info("Skipping {$skippedExisting} products that already have JSON files");
            }
        }

        $this->info('Will process '.count($urls).' URLs');

        // Dry run - just show URLs
        if ($this->option('dry-run')) {
            $this->info('Dry run - URLs that would be scraped:');
            foreach ($urls as $i => $url) {
                $this->line(sprintf('  %d. %s', $i + 1, $url));
            }

            return self::SUCCESS;
        }

        if (count($urls) === 0) {
            $this->info('No URLs to process.');

            return self::SUCCESS;
        }

        // Get credentials
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

        // Determine headless mode
        $headless = ! $this->option('no-headless');

        $this->info('Starting Probo scraper (individual product mode)...');
        $this->info('Headless: '.($headless ? 'yes' : 'no'));
        $this->info("Saving files to: {$scraperStoragePath}");

        $runner = new PlaywrightRunner;
        $supplier = null;

        // Get supplier for import
        if ($this->option('import')) {
            $supplierHandle = $this->option('supplier') ?: 'probo';
            $supplier = Supplier::where('handle', $supplierHandle)->first();

            if (! $supplier) {
                $this->error("Supplier with handle '{$supplierHandle}' not found.");

                return self::FAILURE;
            }
            $this->info("Will import to supplier: {$supplier->name}");
        }

        $importer = new ProboProductImporter;

        // Set update images flag if requested
        if ($this->option('update-images')) {
            $importer->setUpdateImages(true);
            $this->info('Image update mode enabled - existing images will be replaced');
        }

        $stats = [
            'total' => count($urls),
            'scraped' => 0,
            'imported' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        // Process each URL individually
        foreach ($urls as $i => $url) {
            $progress = sprintf('[%d/%d]', $i + 1, count($urls));
            $slug = $this->urlToSlug($url);

            $this->line("{$progress} Scraping: {$url}");

            try {
                // Scrape single product
                $config = [
                    'command' => 'probo-product',
                    'url' => $url,
                    'base_url' => 'https://www.probo.nl',
                    'headless' => $headless,
                    'delay' => 2000,
                    'timeout' => 60000,
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

                $result = $runner->run($config);

                if (! $result['success'] || empty($result['products'])) {
                    $this->warn("{$progress} FAILED: No product data returned");
                    $stats['failed']++;

                    continue;
                }

                $product = $result['products'][0];

                // Save JSON file immediately
                $productFile = "{$scraperStoragePath}/{$slug}.json";
                $productOutput = json_encode($product, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                file_put_contents($productFile, $productOutput);

                $stats['scraped']++;

                $this->info("{$progress} OK: {$product['name']} -> {$slug}.json");

                // Import to Lunar if requested
                if ($supplier) {
                    try {
                        $supplierProduct = $importer->import($product, $supplier);
                        $stats['imported']++;

                        if ($this->option('download-images')) {
                            DownloadProductImages::dispatch($supplierProduct);
                        }

                        if ($this->option('rewrite')) {
                            RewriteProductContent::dispatch($supplierProduct);
                        }
                    } catch (\Exception $e) {
                        $this->warn("  Import failed: {$e->getMessage()}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("{$progress} ERROR: {$e->getMessage()}");
                $stats['failed']++;
            }

            // Progress summary every 25 products
            if (($i + 1) % 25 === 0) {
                $this->newLine();
                $this->info("--- Progress: {$stats['scraped']} scraped, {$stats['failed']} failed ---");
                $this->newLine();
            }
        }

        // Final summary
        $this->newLine();
        $this->info('Scraping complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total URLs', $stats['total']],
                ['Products scraped', $stats['scraped']],
                ['Products imported', $stats['imported']],
                ['Failed', $stats['failed']],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Convert URL to file slug.
     */
    protected function urlToSlug(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $slug = trim($path, '/');

        return preg_replace('/[^a-z0-9\-_]/i', '-', $slug);
    }

    /**
     * Extract product URLs from sitemap.
     * Products have <image:image> tags, categories don't.
     */
    protected function extractProductUrls(string $sitemapPath): array
    {
        $content = file_get_contents($sitemapPath);
        $urls = [];

        // Parse XML
        // Disable libxml errors to handle malformed XML gracefully
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($content);

        if ($xml === false) {
            $this->warn('Failed to parse sitemap as XML, falling back to regex');

            return $this->extractProductUrlsRegex($content);
        }

        // Register namespaces
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->registerXPathNamespace('image', 'http://www.google.com/schemas/sitemap-image/1.1');

        foreach ($xml->url as $urlNode) {
            $loc = (string) $urlNode->loc;

            // Check if this URL has image tags (indicating it's a product)
            $images = $urlNode->children('http://www.google.com/schemas/sitemap-image/1.1');
            if (count($images) > 0) {
                $urls[] = $loc;
            }
        }

        return $urls;
    }

    /**
     * Fallback regex extraction for product URLs.
     */
    protected function extractProductUrlsRegex(string $content): array
    {
        $urls = [];

        // Match URLs that have image:image tags (products)
        preg_match_all('/<url>.*?<loc>([^<]+)<\/loc>.*?<image:image>/s', $content, $matches);

        if (! empty($matches[1])) {
            $urls = $matches[1];
        }

        return $urls;
    }
}
