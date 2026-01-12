<?php

namespace Lunar\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface PrintAsset
{
    /**
     * Get the cart line relationship.
     */
    public function cartLine(): BelongsTo;

    /**
     * Get the order line relationship.
     */
    public function orderLine(): BelongsTo;

    /**
     * Get the full URL to the file.
     */
    public function getUrl(): string;

    /**
     * Get a temporary URL for the file.
     */
    public function getTemporaryUrl(int $minutes = 60): string;

    /**
     * Check if the file exists on disk.
     */
    public function exists(): bool;
}
