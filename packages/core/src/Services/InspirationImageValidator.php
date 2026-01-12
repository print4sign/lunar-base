<?php

namespace Lunar\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InspirationImageValidator
{
    protected int $minWidth;

    protected int $minHeight;

    protected array $allowedMimes;

    protected int $maxSizeKb;

    public function __construct()
    {
        $this->minWidth = config('lunar.inspirations.images.min_width', 800);
        $this->minHeight = config('lunar.inspirations.images.min_height', 600);
        $this->allowedMimes = config('lunar.inspirations.images.allowed_mimes', ['image/jpeg', 'image/png', 'image/webp']);
        $this->maxSizeKb = config('lunar.inspirations.images.max_size_kb', 10240);
    }

    /**
     * Validate an uploaded image file.
     *
     * @throws ValidationException
     */
    public function validate(UploadedFile $file): bool
    {
        $validator = Validator::make(
            ['image' => $file],
            [
                'image' => [
                    'required',
                    'image',
                    'mimes:jpeg,png,webp',
                    'max:'.$this->maxSizeKb,
                    'dimensions:min_width='.$this->minWidth.',min_height='.$this->minHeight,
                ],
            ],
            [
                'image.dimensions' => __('lunar::inspiration.validation.image_dimensions', [
                    'width' => $this->minWidth,
                    'height' => $this->minHeight,
                ]),
                'image.max' => __('lunar::inspiration.validation.image_max_size', [
                    'size' => $this->formatBytes($this->maxSizeKb * 1024),
                ]),
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * Validate multiple uploaded files.
     *
     * @param  array<UploadedFile>  $files
     *
     * @throws ValidationException
     */
    public function validateMultiple(array $files): bool
    {
        foreach ($files as $file) {
            $this->validate($file);
        }

        return true;
    }

    /**
     * Check if dimensions meet minimum requirements.
     */
    public function validateDimensions(UploadedFile $file): bool
    {
        $dimensions = getimagesize($file->getRealPath());

        if ($dimensions === false) {
            return false;
        }

        [$width, $height] = $dimensions;

        return $width >= $this->minWidth && $height >= $this->minHeight;
    }

    /**
     * Get the minimum dimension requirements.
     */
    public function getMinimumDimensions(): array
    {
        return [
            'width' => $this->minWidth,
            'height' => $this->minHeight,
        ];
    }

    /**
     * Get the maximum file size in bytes.
     */
    public function getMaxSizeBytes(): int
    {
        return $this->maxSizeKb * 1024;
    }

    /**
     * Get allowed mime types.
     */
    public function getAllowedMimes(): array
    {
        return $this->allowedMimes;
    }

    /**
     * Format bytes to human readable string.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
