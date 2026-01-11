<?php

namespace Lunar\Scraper\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Lunar\Models\Article;
use Symfony\Component\Process\Process;

class ScrapeArticlesCommand extends Command
{
    protected $signature = 'lunar:scrape-articles
        {--source=probo-all : Source to scrape (probo-klantenservice, probo-blog, probo-all)}
        {--url=* : Specific URLs to scrape}
        {--limit=0 : Limit number of articles (0 = no limit)}
        {--dry-run : Only show which URLs would be scraped}
        {--no-headless : Show browser window}';

    protected $description = 'Scrape articles from Probo.nl';

    protected array $klantenserviceUrls = [];

    protected array $blogUrls = [];

    public function handle(): int
    {
        $source = $this->option('source');
        $urls = $this->option('url');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        // If specific URLs provided, use those
        if (! empty($urls)) {
            $urlsToScrape = $urls;
        } else {
            $urlsToScrape = $this->discoverUrls($source);
        }

        if ($limit > 0) {
            $urlsToScrape = array_slice($urlsToScrape, 0, $limit);
        }

        $this->info(sprintf('Found %d articles to scrape', count($urlsToScrape)));

        if ($dryRun) {
            foreach ($urlsToScrape as $url) {
                $this->line("  - {$url}");
            }

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($urlsToScrape));
        $bar->start();

        $imported = 0;
        $failed = 0;

        foreach ($urlsToScrape as $url) {
            try {
                $data = $this->scrapeArticle($url);
                $this->importArticle($data);
                $imported++;
            } catch (\Exception $e) {
                $this->error("\nFailed to scrape {$url}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Imported: {$imported}, Failed: {$failed}");

        return self::SUCCESS;
    }

    protected function discoverUrls(string $source): array
    {
        $urls = [];

        if (in_array($source, ['probo-klantenservice', 'probo-all'])) {
            $urls = array_merge($urls, $this->discoverKlantenserviceUrls());
        }

        if (in_array($source, ['probo-blog', 'probo-all'])) {
            $urls = array_merge($urls, $this->discoverBlogUrls());
        }

        return $urls;
    }

    protected function discoverKlantenserviceUrls(): array
    {
        // These are the main FAQ article URLs from Probo klantenservice
        return [
            'https://www.probo.nl/klantenservice/bestellen',
            'https://www.probo.nl/klantenservice/algemeen',
            'https://www.probo.nl/klantenservice/bezorgen-afhalen',
            'https://www.probo.nl/klantenservice/betalen',
        ];
    }

    protected function discoverBlogUrls(): array
    {
        // Blog URLs would be discovered by scraping /ontdek with pagination
        return [];
    }

    protected function scrapeArticle(string $url): array
    {
        $scriptsPath = config('lunar.scraper.scripts_path', __DIR__.'/../../../scripts');
        $scriptPath = $scriptsPath.'/scrape-article.js';

        $args = ['node', $scriptPath, "--url={$url}"];

        if ($this->option('no-headless')) {
            $args[] = '--no-headless';
        }

        // Add credentials if available
        $username = config('lunar.scraper.probo.username') ?: env('PROBO_USERNAME');
        $password = config('lunar.scraper.probo.password') ?: env('PROBO_PASSWORD');

        if ($username) {
            $args[] = "--username={$username}";
        }

        if ($password) {
            $args[] = "--password={$password}";
        }

        $process = new Process($args);
        $process->setTimeout(120);
        $process->setWorkingDirectory($scriptsPath);
        $process->run();

        if (! $process->isSuccessful()) {
            $errorOutput = $process->getErrorOutput();
            $errorData = json_decode($errorOutput, true);

            throw new \Exception($errorData['error'] ?? $process->getErrorOutput());
        }

        $output = trim($process->getOutput());
        $result = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: '.json_last_error_msg());
        }

        if (isset($result['error'])) {
            throw new \Exception($result['error']);
        }

        return $result;
    }

    protected function importArticle(array $data): Article
    {
        $slug = Str::slug($data['title']['nl'] ?? $data['title']['en'] ?? 'article-'.uniqid());

        // Check if article already exists
        $existing = Article::where('source_url', $data['source_url'])->first();

        if ($existing) {
            $this->line("\n  Updating existing article: {$slug}");
            $existing->update([
                'title' => $data['title'],
                'body' => $data['body'],
                'excerpt' => $data['excerpt'] ?? null,
                'featured_image' => $data['featured_image'] ?? null,
                'subcategory' => $data['subcategory'] ?? null,
                'tags' => $data['tags'] ?? null,
            ]);

            return $existing;
        }

        return Article::create([
            'title' => $data['title'],
            'slug' => $slug,
            'body' => $data['body'],
            'excerpt' => $data['excerpt'] ?? null,
            'meta_description' => null,
            'featured_image' => $data['featured_image'] ?? null,
            'category' => $data['category'],
            'subcategory' => $data['subcategory'] ?? null,
            'tags' => $data['tags'] ?? null,
            'source_url' => $data['source_url'],
            'original_content' => [
                'title' => $data['title'],
                'body' => $data['body'],
                'excerpt' => $data['excerpt'] ?? null,
            ],
            'status' => 'draft',
        ]);
    }
}
