<?php

namespace Lunar\Scraper\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PlaywrightRunner
{
    protected int $timeout = 300; // 5 minutes default

    protected bool $headless = true;

    /**
     * Run a scrape command with custom configuration.
     */
    public function run(array $config): array
    {
        $scriptsPath = config('lunar.scraper.scripts_path', __DIR__.'/../../scripts');
        $scriptPath = $scriptsPath.'/src/index.js';

        // Check if scripts are installed
        if (! file_exists($scriptsPath.'/node_modules')) {
            Log::warning('Scraper scripts not installed. Run: lunar:scraper:install');

            return [
                'success' => false,
                'error' => 'Playwright scripts not installed. Run: php artisan lunar:scraper:install',
            ];
        }

        $process = new Process(['node', $scriptPath]);
        $process->setInput(json_encode($config));
        $process->setTimeout($config['timeout'] ?? $this->timeout);
        $process->setWorkingDirectory($scriptsPath);

        try {
            // Run process with callback to avoid output buffer limits
            $stdout = '';
            $stderr = '';

            $process->run(function ($type, $buffer) use (&$stdout, &$stderr) {
                if (Process::ERR === $type) {
                    $stderr .= $buffer;
                    // Echo stderr to console for debugging
                    fwrite(STDERR, $buffer);
                } else {
                    $stdout .= $buffer;
                }
            });

            // Debug: Output process info
            if ($process->getExitCode() !== 0) {
                Log::error('Playwright process failed', [
                    'exit_code' => $process->getExitCode(),
                    'stdout_length' => strlen($stdout),
                    'stderr' => substr($stderr, 0, 500),
                ]);
            }

            // Trim whitespace
            $output = trim($stdout);

            $data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Playwright scrape returned invalid JSON', [
                    'command' => $config['command'] ?? 'unknown',
                    'output_length' => strlen($output),
                    'output_start' => substr($output, 0, 200),
                    'output_end' => substr($output, -200),
                    'json_error' => json_last_error_msg(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Invalid JSON response from Playwright: '.json_last_error_msg(),
                ];
            }

            return $data;
        } catch (ProcessFailedException $e) {
            $errorOutput = $process->getErrorOutput();
            $errorData = json_decode($errorOutput, true);

            Log::error('Playwright scrape failed', [
                'command' => $config['command'] ?? 'unknown',
                'error' => $e->getMessage(),
                'stderr' => substr($errorOutput, 0, 1000),
            ]);

            return [
                'success' => false,
                'error' => $errorData['error'] ?? $e->getMessage(),
            ];
        }
    }

    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function setHeadless(bool $headless): self
    {
        $this->headless = $headless;

        return $this;
    }
}
