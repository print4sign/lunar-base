<?php

namespace Lunar\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Lunar\Base\DataTransferObjects\Upload\UploaderRequirement;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Base\DataTransferObjects\Upload\UploadValidationResult;

/**
 * Service for validating files against upload specifications.
 */
class UploadSpecValidator
{
    /**
     * Validate files against an upload specification.
     *
     * @param  UploadSpec  $spec  The upload specification
     * @param  array  $files  Array of files grouped by uploader index
     *                        Format: [0 => [UploadedFile, ...], 1 => [...]]
     */
    public function validate(UploadSpec $spec, array $files): UploadValidationResult
    {
        if (! $spec->requiresUpload()) {
            return UploadValidationResult::success();
        }

        $uploaderResults = [];

        foreach ($spec->uploaders as $index => $uploader) {
            $uploaderFiles = $files[$index] ?? [];
            $uploaderResults[$index] = $this->validateUploader($uploader, $uploaderFiles);
        }

        return UploadValidationResult::fromUploaderResults($uploaderResults);
    }

    /**
     * Validate files for a single uploader.
     *
     * @param  UploaderRequirement  $uploader  The uploader requirement
     * @param  array  $files  Array of UploadedFile instances
     */
    protected function validateUploader(UploaderRequirement $uploader, array $files): array
    {
        $errors = [];
        $warnings = [];

        // Check file count
        $requiredCount = $uploader->getRequiredFileCount();
        $actualCount = count($files);

        if ($actualCount < $requiredCount) {
            $errors[] = sprintf(
                'Expected %d file(s), but got %d.',
                $requiredCount,
                $actualCount
            );
        }

        // Validate each file
        foreach ($files as $fileIndex => $file) {
            if (! $file instanceof UploadedFile) {
                $errors[] = sprintf('File %d is not a valid uploaded file.', $fileIndex + 1);

                continue;
            }

            $fileErrors = $this->validateFile($uploader, $file, $fileIndex);
            $errors = array_merge($errors, $fileErrors['errors']);
            $warnings = array_merge($warnings, $fileErrors['warnings']);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'file_count' => $actualCount,
            'required_count' => $requiredCount,
        ];
    }

    /**
     * Validate a single file against uploader requirements.
     */
    protected function validateFile(UploaderRequirement $uploader, UploadedFile $file, int $fileIndex): array
    {
        $errors = [];
        $warnings = [];
        $fileNumber = $fileIndex + 1;

        // Check file size (convert MB to bytes)
        $maxSizeBytes = $uploader->fileLimit * 1024 * 1024;
        if ($file->getSize() > $maxSizeBytes) {
            $errors[] = sprintf(
                'File %d exceeds maximum size of %d MB.',
                $fileNumber,
                $uploader->fileLimit
            );
        }

        // Check if it's an image for dimension/DPI validation
        if ($this->isImageFile($file)) {
            $imageValidation = $this->validateImageFile($uploader, $file, $fileNumber);
            $errors = array_merge($errors, $imageValidation['errors']);
            $warnings = array_merge($warnings, $imageValidation['warnings']);
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate image-specific requirements (dimensions, DPI).
     */
    protected function validateImageFile(UploaderRequirement $uploader, UploadedFile $file, int $fileNumber): array
    {
        $errors = [];
        $warnings = [];

        $imageInfo = @getimagesize($file->getPathname());

        if ($imageInfo === false) {
            $warnings[] = sprintf(
                'File %d: Could not read image dimensions for validation.',
                $fileNumber
            );

            return ['errors' => $errors, 'warnings' => $warnings];
        }

        [$imageWidth, $imageHeight] = $imageInfo;

        // Calculate estimated DPI if we have target dimensions
        if ($uploader->width && $uploader->height) {
            // Convert mm to inches (1 inch = 25.4mm)
            $targetWidthInches = $uploader->width / 25.4;
            $targetHeightInches = $uploader->height / 25.4;

            // Calculate DPI based on image pixels / target inches
            $estimatedDpiWidth = $imageWidth / $targetWidthInches;
            $estimatedDpiHeight = $imageHeight / $targetHeightInches;
            $estimatedDpi = min($estimatedDpiWidth, $estimatedDpiHeight);

            if ($estimatedDpi < $uploader->minimalDpi) {
                $warnings[] = sprintf(
                    'File %d: Estimated DPI (%.0f) is below the recommended minimum of %d DPI. Print quality may be affected.',
                    $fileNumber,
                    $estimatedDpi,
                    $uploader->minimalDpi
                );
            }

            // Check aspect ratio
            $targetRatio = $uploader->width / $uploader->height;
            $imageRatio = $imageWidth / $imageHeight;
            $ratioDifference = abs($targetRatio - $imageRatio) / $targetRatio;

            // Allow 5% difference in aspect ratio
            if ($ratioDifference > 0.05) {
                $warnings[] = sprintf(
                    'File %d: Aspect ratio (%.2f:1) differs from target (%.2f:1). Image may be cropped or stretched.',
                    $fileNumber,
                    $imageRatio,
                    $targetRatio
                );
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check if a file is an image.
     */
    protected function isImageFile(UploadedFile $file): bool
    {
        $imageMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/tiff',
            'image/bmp',
        ];

        return in_array($file->getMimeType(), $imageMimeTypes);
    }

    /**
     * Validate that all required files are present for a cart line.
     *
     * @param  UploadSpec  $spec  The upload specification
     * @param  Collection  $existingAssets  Collection of existing PrintAsset models
     */
    public function validateCompleteness(UploadSpec $spec, Collection $existingAssets): UploadValidationResult
    {
        if (! $spec->requiresUpload()) {
            return UploadValidationResult::success();
        }

        $uploaderResults = [];

        foreach ($spec->uploaders as $index => $uploader) {
            $uploaderAssets = $existingAssets->where('uploader_index', $index);
            $requiredCount = $uploader->getRequiredFileCount();
            $actualCount = $uploaderAssets->count();

            $errors = [];
            if ($actualCount < $requiredCount) {
                $errors[] = sprintf(
                    'Uploader %d requires %d file(s), but only %d uploaded.',
                    $index + 1,
                    $requiredCount,
                    $actualCount
                );
            }

            $uploaderResults[$index] = [
                'valid' => empty($errors),
                'errors' => $errors,
                'warnings' => [],
                'file_count' => $actualCount,
                'required_count' => $requiredCount,
            ];
        }

        return UploadValidationResult::fromUploaderResults($uploaderResults);
    }
}
