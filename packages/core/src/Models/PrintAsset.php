<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasMacros;

/**
 * @property int $id
 * @property ?int $cart_line_id
 * @property ?int $order_line_id
 * @property int $uploader_index
 * @property int $file_index
 * @property string $disk
 * @property string $path
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size
 * @property ?int $width
 * @property ?int $height
 * @property ?int $dpi
 * @property ?array $meta
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
class PrintAsset extends BaseModel implements Contracts\PrintAsset
{
    use HasMacros;

    /**
     * Define which attributes should be
     * protected from mass assignment.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'dpi' => 'integer',
        'uploader_index' => 'integer',
        'file_index' => 'integer',
    ];

    /**
     * Get the cart line relationship.
     */
    public function cartLine(): BelongsTo
    {
        return $this->belongsTo(CartLine::modelClass());
    }

    /**
     * Get the order line relationship.
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::modelClass());
    }

    /**
     * Get the full URL to the file.
     */
    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get a temporary URL for the file.
     */
    public function getTemporaryUrl(int $minutes = 60): string
    {
        $disk = Storage::disk($this->disk);

        // Check if the disk supports temporary URLs
        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($this->path, now()->addMinutes($minutes));
        }

        // Fall back to regular URL
        return $this->getUrl();
    }

    /**
     * Check if the file exists on disk.
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Get the file contents.
     */
    public function getContents(): ?string
    {
        if (! $this->exists()) {
            return null;
        }

        return Storage::disk($this->disk)->get($this->path);
    }

    /**
     * Delete the file from disk.
     */
    public function deleteFile(): bool
    {
        if (! $this->exists()) {
            return true;
        }

        return Storage::disk($this->disk)->delete($this->path);
    }

    /**
     * Get the file size in a human-readable format.
     */
    public function getHumanReadableSize(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    /**
     * Check if this is an image file.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if this is a PDF file.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Delete the file when the model is deleted (if configured)
        static::deleting(function (PrintAsset $printAsset) {
            if (config('lunar.cart.print_assets.delete_files_on_delete', true)) {
                $printAsset->deleteFile();
            }
        });
    }
}
