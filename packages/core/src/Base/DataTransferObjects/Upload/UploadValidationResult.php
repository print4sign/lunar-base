<?php

namespace Lunar\Base\DataTransferObjects\Upload;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

/**
 * Result of validating uploaded files against an UploadSpec.
 */
class UploadValidationResult implements Arrayable, \JsonSerializable
{
    public function __construct(
        public readonly bool $valid,
        public readonly Collection $errors,
        public readonly Collection $warnings,
        public readonly Collection $uploaderResults,
    ) {}

    /**
     * Create a successful validation result.
     */
    public static function success(): self
    {
        return new self(
            valid: true,
            errors: collect(),
            warnings: collect(),
            uploaderResults: collect(),
        );
    }

    /**
     * Create a failed validation result with errors.
     */
    public static function withErrors(array $errors = [], array $warnings = []): self
    {
        return new self(
            valid: false,
            errors: collect($errors),
            warnings: collect($warnings),
            uploaderResults: collect(),
        );
    }

    /**
     * Create a validation result from uploader results.
     */
    public static function fromUploaderResults(array $uploaderResults): self
    {
        $errors = collect();
        $warnings = collect();

        foreach ($uploaderResults as $index => $result) {
            if (! empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $errors->push([
                        'uploader_index' => $index,
                        'message' => $error,
                    ]);
                }
            }

            if (! empty($result['warnings'])) {
                foreach ($result['warnings'] as $warning) {
                    $warnings->push([
                        'uploader_index' => $index,
                        'message' => $warning,
                    ]);
                }
            }
        }

        return new self(
            valid: $errors->isEmpty(),
            errors: $errors,
            warnings: $warnings,
            uploaderResults: collect($uploaderResults),
        );
    }

    /**
     * Check if the validation passed.
     */
    public function passed(): bool
    {
        return $this->valid;
    }

    /**
     * Check if the validation failed.
     */
    public function failed(): bool
    {
        return ! $this->valid;
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return $this->warnings->isNotEmpty();
    }

    /**
     * Get errors for a specific uploader.
     */
    public function getErrorsForUploader(int $index): Collection
    {
        return $this->errors->filter(fn ($error) => $error['uploader_index'] === $index);
    }

    /**
     * Get warnings for a specific uploader.
     */
    public function getWarningsForUploader(int $index): Collection
    {
        return $this->warnings->filter(fn ($warning) => $warning['uploader_index'] === $index);
    }

    /**
     * Get all error messages as a flat array.
     */
    public function getErrorMessages(): array
    {
        return $this->errors->pluck('message')->all();
    }

    /**
     * Get all warning messages as a flat array.
     */
    public function getWarningMessages(): array
    {
        return $this->warnings->pluck('message')->all();
    }

    /**
     * Convert the validation result to an array.
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors->all(),
            'warnings' => $this->warnings->all(),
            'uploader_results' => $this->uploaderResults->all(),
        ];
    }

    /**
     * Serialize to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
