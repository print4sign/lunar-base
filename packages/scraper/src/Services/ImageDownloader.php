<?php

namespace Lunar\Scraper\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageDownloader
{
    protected string $disk;

    protected string $directory;

    public function __construct()
    {
        $this->disk = config('lunar.scraper.images.disk', 'public');
        $this->directory = config('lunar.scraper.images.directory', 'scraped-products');
    }

    /**
     * Download an image from a URL and store it locally.
     * If the file already exists locally, returns the existing URL without downloading.
     *
     * @return string|null The public URL of the stored image, or null on failure
     */
    public function download(string $url, ?string $filename = null): ?string
    {
        try {
            // Generate filename if not provided
            if (! $filename) {
                $filename = $this->generateFilename($url);
            }

            // Check if file already exists locally
            $path = $this->directory.'/'.$filename;
            if (Storage::disk($this->disk)->exists($path)) {
                Log::info("Image already exists locally, skipping download: {$path}");

                return Storage::disk($this->disk)->url($path);
            }

            // Download the image
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept' => 'image/*,*/*;q=0.8',
                    'Referer' => parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).'/',
                ])
                ->get($url);

            if (! $response->successful()) {
                Log::warning("Failed to download image: {$url}", [
                    'status' => $response->status(),
                ]);

                return null;
            }

            // Get content type and validate it's an image
            $contentType = $response->header('Content-Type');
            if (! $this->isValidImageType($contentType)) {
                Log::warning("Invalid content type for image: {$url}", [
                    'content_type' => $contentType,
                ]);

                return null;
            }

            // Ensure correct extension
            $filename = $this->ensureCorrectExtension($filename, $contentType);

            // Store the image
            $path = $this->directory.'/'.$filename;
            Storage::disk($this->disk)->put($path, $response->body());

            Log::info("Downloaded image: {$url} -> {$path}");

            return Storage::disk($this->disk)->url($path);
        } catch (\Exception $e) {
            Log::error("Error downloading image: {$url}", [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if a file already exists locally for the given filename.
     */
    public function exists(string $filename): bool
    {
        $path = $this->directory.'/'.$filename;

        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Get the local URL for a file if it exists.
     */
    public function getLocalUrl(string $filename): ?string
    {
        $path = $this->directory.'/'.$filename;

        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->url($path);
        }

        return null;
    }

    /**
     * Get the full filesystem path for a file.
     */
    public function getFullPath(string $filename): string
    {
        return Storage::disk($this->disk)->path($this->directory.'/'.$filename);
    }

    /**
     * Download multiple images and return array of local URLs.
     *
     * @param  array  $urls  Array of image URLs
     * @param  string|null  $prefix  Optional prefix for filenames
     * @return array Array of ['original' => url, 'local' => url] pairs
     */
    public function downloadMultiple(array $urls, ?string $prefix = null): array
    {
        $results = [];

        foreach ($urls as $index => $url) {
            $filename = $prefix
                ? Str::slug($prefix).'-'.($index + 1).'.'.$this->getExtensionFromUrl($url)
                : null;

            $localUrl = $this->download($url, $filename);

            $results[] = [
                'original' => $url,
                'local' => $localUrl,
                'success' => $localUrl !== null,
            ];
        }

        return $results;
    }

    /**
     * Generate a filename from a URL.
     */
    protected function generateFilename(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $basename = basename($path);

        // If basename has no extension or is too generic, generate a unique name
        if (! pathinfo($basename, PATHINFO_EXTENSION) || strlen($basename) < 5) {
            return Str::uuid().'.'.$this->getExtensionFromUrl($url);
        }

        // Add unique prefix to avoid collisions
        return Str::random(8).'-'.$basename;
    }

    /**
     * Get file extension from URL.
     */
    protected function getExtensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // Default to jpg if no extension found
        return $extension ?: 'jpg';
    }

    /**
     * Check if content type is a valid image.
     */
    protected function isValidImageType(?string $contentType): bool
    {
        if (! $contentType) {
            return true; // Allow if no content type (some servers don't send it)
        }

        $validTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ];

        foreach ($validTypes as $type) {
            if (str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensure filename has correct extension based on content type.
     */
    protected function ensureCorrectExtension(string $filename, ?string $contentType): string
    {
        if (! $contentType) {
            return $filename;
        }

        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];

        foreach ($extensionMap as $type => $ext) {
            if (str_contains($contentType, $type)) {
                $currentExt = pathinfo($filename, PATHINFO_EXTENSION);
                if (strtolower($currentExt) !== $ext) {
                    return pathinfo($filename, PATHINFO_FILENAME).'.'.$ext;
                }
                break;
            }
        }

        return $filename;
    }
}
