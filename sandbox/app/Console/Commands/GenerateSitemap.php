<?php

namespace App\Console\Commands;

use App\Services\SitemapGenerator;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate {--path= : Custom output path for the sitemap}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap.xml file with all localized URLs';

    /**
     * Execute the console command.
     */
    public function handle(SitemapGenerator $generator): int
    {
        $this->info('Generating sitemap...');

        $path = $this->option('path');

        if ($path) {
            $generator->writeToFile($path);
            $this->info("Sitemap generated successfully at: {$path}");
        } else {
            $generator->writeToFile();
            $this->info('Sitemap generated successfully at: public/sitemap.xml');
        }

        return Command::SUCCESS;
    }
}
