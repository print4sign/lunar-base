<?php

namespace Lunar\Base\DataTransferObjects\Upload;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class UploadSpec implements Arrayable, \JsonSerializable
{
    public const SOURCE_STATIC = 'static';

    public const SOURCE_DYNAMIC = 'dynamic';

    public const SOURCE_SUPPLIER = 'supplier';

    public const VERSION = 1;

    /**
     * @param  Collection<int, UploaderRequirement>  $uploaders
     */
    public function __construct(
        public readonly bool $upload,
        public readonly Collection $uploaders,
        public readonly int $version = self::VERSION,
        public readonly string $source = self::SOURCE_STATIC,
        public readonly ?string $supplierDriver = null,
    ) {}

    /**
     * Create an UploadSpec from an array.
     */
    public static function fromArray(array $data): self
    {
        $uploaders = collect($data['uploaders'] ?? [])
            ->map(fn (array $uploader) => UploaderRequirement::fromArray($uploader));

        $source = $data['source'] ?? self::SOURCE_STATIC;
        $supplierDriver = null;

        // Parse supplier driver from source if present (e.g., "supplier:probo")
        if (str_starts_with($source, self::SOURCE_SUPPLIER.':')) {
            $supplierDriver = substr($source, strlen(self::SOURCE_SUPPLIER.':'));
            $source = self::SOURCE_SUPPLIER;
        }

        return new self(
            upload: (bool) ($data['upload'] ?? false),
            uploaders: $uploaders,
            version: (int) ($data['version'] ?? self::VERSION),
            source: $source,
            supplierDriver: $supplierDriver,
        );
    }

    /**
     * Create an empty UploadSpec (no upload required).
     */
    public static function empty(): self
    {
        return new self(
            upload: false,
            uploaders: collect(),
        );
    }

    /**
     * Check if this spec requires file uploads.
     */
    public function requiresUpload(): bool
    {
        return $this->upload && $this->uploaders->isNotEmpty();
    }

    /**
     * Get a specific uploader by index.
     */
    public function getUploader(int $index = 0): ?UploaderRequirement
    {
        return $this->uploaders->get($index);
    }

    /**
     * Get the total number of uploaders.
     */
    public function getTotalUploaders(): int
    {
        return $this->uploaders->count();
    }

    /**
     * Get the total number of files required across all uploaders.
     */
    public function getTotalFilesRequired(): int
    {
        return $this->uploaders->sum(function (UploaderRequirement $uploader) {
            // For frontback type, always require 2 files (front and back)
            if ($uploader->type === 'frontback') {
                return 2;
            }

            return $uploader->amount;
        });
    }

    /**
     * Create a new UploadSpec with dimensions overridden.
     *
     * This is used for dimensional products (m², m, cm²) where the
     * upload dimensions come from the customer's configuration.
     */
    public function withDimensions(?float $width, ?float $height, ?float $length = null): self
    {
        if ($width === null && $height === null && $length === null) {
            return $this;
        }

        $newUploaders = $this->uploaders->map(
            fn (UploaderRequirement $uploader) => $uploader->withDimensions($width, $height, $length)
        );

        return new self(
            upload: $this->upload,
            uploaders: $newUploaders,
            version: $this->version,
            source: $this->source,
            supplierDriver: $this->supplierDriver,
        );
    }

    /**
     * Convert the UploadSpec to an array.
     */
    public function toArray(): array
    {
        $source = $this->source;

        // Append supplier driver to source if present
        if ($this->supplierDriver && $this->source === self::SOURCE_SUPPLIER) {
            $source = self::SOURCE_SUPPLIER.':'.$this->supplierDriver;
        }

        return [
            'upload' => $this->upload,
            'uploaders' => $this->uploaders->map(fn (UploaderRequirement $u) => $u->toArray())->values()->all(),
            'version' => $this->version,
            'source' => $source,
        ];
    }

    /**
     * Serialize the UploadSpec to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
