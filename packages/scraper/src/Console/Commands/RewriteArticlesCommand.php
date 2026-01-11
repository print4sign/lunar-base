<?php

namespace Lunar\Scraper\Console\Commands;

use Illuminate\Console\Command;
use Lunar\Models\Article;
use Lunar\Scraper\Services\ArticleRewriterService;

class RewriteArticlesCommand extends Command
{
    protected $signature = 'lunar:rewrite-articles
        {--category= : Only rewrite articles in this category}
        {--limit=0 : Limit number of articles (0 = no limit)}
        {--dry-run : Only show which articles would be rewritten}
        {--locales=nl,en : Comma-separated list of locales to rewrite}';

    protected $description = 'Rewrite draft articles using Claude AI';

    public function handle(ArticleRewriterService $rewriter): int
    {
        $category = $this->option('category');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $locales = explode(',', $this->option('locales'));

        $query = Article::where('status', 'draft');

        if ($category) {
            $query->where('category', $category);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $articles = $query->get();

        $this->info(sprintf('Found %d draft articles to rewrite', $articles->count()));

        if ($dryRun) {
            foreach ($articles as $article) {
                $title = $article->title['nl'] ?? $article->title['en'] ?? 'Untitled';
                $this->line("  - [{$article->category}] {$title}");
            }

            return self::SUCCESS;
        }

        if ($articles->isEmpty()) {
            $this->info('No articles to rewrite.');

            return self::SUCCESS;
        }

        if (! $this->confirm("This will rewrite {$articles->count()} articles using Claude AI. Continue?")) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($articles as $article) {
            try {
                $rewriter->rewrite($article, $locales);
                $article->update(['status' => 'rewritten']);
                $success++;
            } catch (\Exception $e) {
                $title = $article->title['nl'] ?? 'Untitled';
                $this->error("\nFailed to rewrite '{$title}': {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Success: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
