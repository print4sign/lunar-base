<?php

namespace Lunar\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Base\DataTransferObjects\Upload\UploadValidationResult;
use Lunar\Events\Upload\PrintAssetReady;
use Lunar\Events\Upload\PrintFileUploaded;
use Lunar\Events\Upload\PrintFilesValidated;
use Lunar\Models\CartLine;
use Lunar\Models\OrderLine;
use Lunar\Models\PrintAsset;

/**
 * Service for managing print asset uploads and storage.
 */
class PrintAssetService
{
    protected string $disk = 'local';

    protected string $directory = 'print-assets';

    public function __construct(
        protected UploadSpecValidator $validator
    ) {
        $this->disk = config('lunar.cart.print_assets.disk', 'local');
        $this->directory = config('lunar.cart.print_assets.directory', 'print-assets');
    }

    /**
     * Upload a file for a cart line.
     */
    public function uploadForCartLine(
        CartLine $cartLine,
        UploadedFile $file,
        int $uploaderIndex,
        int $fileIndex
    ): PrintAsset {
        // Generate path
        $path = $this->generatePath($cartLine, $uploaderIndex, $fileIndex, $file);

        // Store the file
        $storedPath = Storage::disk($this->disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        // Extract metadata
        $metadata = $this->extractImageMetadata($file);

        // Create the print asset record
        $printAsset = PrintAsset::create([
            'cart_line_id' => $cartLine->id,
            'uploader_index' => $uploaderIndex,
            'file_index' => $fileIndex,
            'disk' => $this->disk,
            'path' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $metadata['width'] ?? null,
            'height' => $metadata['height'] ?? null,
            'dpi' => $metadata['dpi'] ?? null,
            'meta' => $metadata,
        ]);

        // Fire event
        PrintFileUploaded::dispatch($cartLine, $printAsset, $uploaderIndex, $fileIndex);

        return $printAsset;
    }

    /**
     * Validate and upload multiple files for a cart line.
     *
     * @param  array  $files  Array of files grouped by uploader index
     *                        Format: [0 => [UploadedFile, ...], 1 => [...]]
     */
    public function validateAndUpload(
        CartLine $cartLine,
        UploadSpec $spec,
        array $files
    ): UploadValidationResult {
        // Validate the files first
        $result = $this->validator->validate($spec, $files);

        // Fire validation event
        PrintFilesValidated::dispatch($cartLine, $result);

        if ($result->failed()) {
            return $result;
        }

        // Delete existing assets for this cart line
        $this->deleteForCartLine($cartLine);

        // Upload each file
        foreach ($files as $uploaderIndex => $uploaderFiles) {
            foreach ($uploaderFiles as $fileIndex => $file) {
                if ($file instanceof UploadedFile) {
                    $this->uploadForCartLine($cartLine, $file, $uploaderIndex, $fileIndex);
                }
            }
        }

        return $result;
    }

    /**
     * Copy print assets from a cart line to an order line.
     *
     * @return Collection<int, PrintAsset>
     */
    public function copyToOrderLine(CartLine $cartLine, OrderLine $orderLine): Collection
    {
        $copiedAssets = collect();

        foreach ($cartLine->printAssets as $asset) {
            $newAsset = $asset->replicate();
            $newAsset->cart_line_id = null;
            $newAsset->order_line_id = $orderLine->id;
            $newAsset->save();

            $copiedAssets->push($newAsset);
        }

        if ($copiedAssets->isNotEmpty()) {
            PrintAssetReady::dispatch($orderLine, $copiedAssets);
        }

        return $copiedAssets;
    }

    /**
     * Delete all print assets for a cart line.
     *
     * @param  bool|null  $deleteFiles  Override config setting for deleting files. Null uses config.
     */
    public function deleteForCartLine(CartLine $cartLine, ?bool $deleteFiles = null): void
    {
        $shouldDeleteFiles = $deleteFiles ?? config('lunar.cart.print_assets.delete_files_on_delete', true);

        foreach ($cartLine->printAssets as $asset) {
            if ($shouldDeleteFiles) {
                $asset->deleteFile();
            }
            $asset->delete();
        }
    }

    /**
     * Delete all print assets for an order line.
     *
     * @param  bool|null  $deleteFiles  Override config setting for deleting files. Null uses config.
     */
    public function deleteForOrderLine(OrderLine $orderLine, ?bool $deleteFiles = null): void
    {
        $shouldDeleteFiles = $deleteFiles ?? config('lunar.cart.print_assets.delete_files_on_delete', true);
        $assets = PrintAsset::where('order_line_id', $orderLine->id)->get();

        foreach ($assets as $asset) {
            if ($shouldDeleteFiles) {
                $asset->deleteFile();
            }
            $asset->delete();
        }
    }

    /**
     * Extract metadata from an image file.
     */
    protected function extractImageMetadata(UploadedFile $file): array
    {
        $metadata = [];

        // Check if it's an image
        if (! str_starts_with($file->getMimeType(), 'image/')) {
            return $metadata;
        }

        $imageInfo = @getimagesize($file->getPathname());

        if ($imageInfo !== false) {
            $metadata['width'] = $imageInfo[0];
            $metadata['height'] = $imageInfo[1];
            $metadata['type'] = $imageInfo[2];
            $metadata['bits'] = $imageInfo['bits'] ?? null;
            $metadata['channels'] = $imageInfo['channels'] ?? null;

            // Try to extract DPI from EXIF data
            if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/tiff'])) {
                try {
                    $exif = @exif_read_data($file->getPathname());
                    if ($exif) {
                        $xResolution = $exif['XResolution'] ?? null;
                        if ($xResolution) {
                            // XResolution can be a fraction like "300/1"
                            if (is_string($xResolution) && str_contains($xResolution, '/')) {
                                [$num, $denom] = explode('/', $xResolution);
                                $metadata['dpi'] = $denom > 0 ? (int) ($num / $denom) : null;
                            } else {
                                $metadata['dpi'] = (int) $xResolution;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore EXIF errors
                }
            }
        }

        return $metadata;
    }

    /**
     * Generate a path for storing the file.
     */
    protected function generatePath(
        CartLine $cartLine,
        int $uploaderIndex,
        int $fileIndex,
        UploadedFile $file
    ): string {
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $cartId = $cartLine->cart_id;
        $lineId = $cartLine->id;
        $date = now()->format('Y/m/d');

        return sprintf(
            '%s/%s/cart-%d/line-%d/uploader-%d/file-%d.%s',
            $this->directory,
            $date,
            $cartId,
            $lineId,
            $uploaderIndex,
            $fileIndex,
            $extension
        );
    }

    /**
     * Set the disk to use for storage.
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set the directory for storing files.
     */
    public function setDirectory(string $directory): self
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Generate temporary public URLs for print assets.
     *
     * This is used when submitting orders to suppliers like Probo,
     * which require publicly accessible URLs to fetch the files.
     *
     * @param  Collection<int, PrintAsset>  $printAssets
     * @param  int  $validMinutes  How long the URLs should be valid (default 120 minutes)
     * @return array Array of file data with URIs for supplier submission
     */
    public function generatePublicUrls(Collection $printAssets, int $validMinutes = 120): array
    {
        return $printAssets->map(function (PrintAsset $asset) use ($validMinutes) {
            // Try to get a temporary URL (works for S3 and other cloud storage)
            $url = $asset->getTemporaryUrl($validMinutes);

            // If temporary URL is not supported (e.g., local disk),
            // fall back to the regular URL
            if (! $url) {
                $url = $asset->getUrl();
            }

            return [
                'uri' => $url,
                'uploader_index' => $asset->uploader_index,
                'file_index' => $asset->file_index,
                'original_filename' => $asset->original_filename,
                'mime_type' => $asset->mime_type,
            ];
        })->values()->all();
    }

    /**
     * Generate public URLs formatted for Probo order submission.
     *
     * Probo expects files in a specific format within the order request.
     *
     * @param  Collection<int, PrintAsset>  $printAssets
     * @param  int  $validMinutes  How long the URLs should be valid
     * @return array Array formatted for Probo files array
     */
    public function generateProboFileUrls(Collection $printAssets, int $validMinutes = 120): array
    {
        // Group by uploader index to handle frontback uploads
        $grouped = $printAssets->groupBy('uploader_index');

        $files = [];

        foreach ($grouped as $uploaderIndex => $assets) {
            foreach ($assets as $asset) {
                $url = $asset->getTemporaryUrl($validMinutes) ?: $asset->getUrl();

                $fileData = [
                    'uri' => $url,
                ];

                // For frontback uploads, specify front/back side
                if ($assets->count() === 2) {
                    $fileData['side'] = $asset->file_index === 0 ? 'front' : 'back';
                }

                $files[] = $fileData;
            }
        }

        return $files;
    }
}
