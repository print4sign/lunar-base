<?php

namespace Lunar\Scraper\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class InstallScraperScripts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lunar:scraper:install
                            {--no-browsers : Skip installing Playwright browsers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Playwright scripts and dependencies for the website scraper';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $scriptsPath = config('lunar.scraper.scripts_path', __DIR__.'/../../../scripts');

        if (! is_dir($scriptsPath)) {
            $this->components->error("Scripts directory not found: {$scriptsPath}");

            return self::FAILURE;
        }

        // Check if Node.js is installed
        $nodeCheck = new Process(['node', '--version']);
        $nodeCheck->run();

        if (! $nodeCheck->isSuccessful()) {
            $this->components->error('Node.js is required but not installed. Please install Node.js first.');

            return self::FAILURE;
        }

        $this->components->info('Node.js version: '.trim($nodeCheck->getOutput()));

        // Install npm dependencies
        $this->components->task('Installing npm dependencies', function () use ($scriptsPath) {
            $process = new Process(['npm', 'install'], $scriptsPath);
            $process->setTimeout(300);
            $process->run();

            return $process->isSuccessful();
        });

        // Install Playwright browsers
        if (! $this->option('no-browsers')) {
            $this->components->task('Installing Playwright Chromium browser', function () use ($scriptsPath) {
                $process = new Process(['npx', 'playwright', 'install', 'chromium'], $scriptsPath);
                $process->setTimeout(600);
                $process->run();

                return $process->isSuccessful();
            });
        }

        $this->newLine();
        $this->components->success('Scraper scripts installed successfully!');

        $this->newLine();
        $this->components->info('Next steps:');
        $this->line('  1. Run migrations: <fg=yellow>php artisan migrate</>');
        $this->line('  2. Create a scrape site in the admin panel');
        $this->line('  3. Start scraping: <fg=yellow>php artisan lunar:scraper:scrape</>');

        return self::SUCCESS;
    }
}
