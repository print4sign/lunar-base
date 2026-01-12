<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasMacros;
use Lunar\Base\Traits\LogsActivity;
use Lunar\Database\Factories\SupplierFactory;

/**
 * @property int $id
 * @property string $name
 * @property ?string $icon
 * @property string $handle
 * @property string $driver
 * @property ?array $credentials
 * @property ?array $capabilities
 * @property bool $enabled
 * @property int $priority
 * @property ?array $meta
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 */
class Supplier extends BaseModel implements Contracts\Supplier
{
    use HasFactory;
    use HasMacros;
    use LogsActivity;
    use SoftDeletes;

    /**
     * {@inheritDoc}
     */
    protected $guarded = [];

    /**
     * {@inheritDoc}
     */
    protected $casts = [
        'credentials' => 'encrypted:array',
        'capabilities' => 'array',
        'meta' => 'array',
        'enabled' => 'boolean',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return SupplierFactory::new();
    }

    /**
     * Return the supplier products relationship.
     */
    public function products(): HasMany
    {
        return $this->hasMany(SupplierProduct::modelClass());
    }

    /**
     * Return the supplier orders relationship.
     */
    public function supplierOrders(): HasMany
    {
        return $this->hasMany(SupplierOrder::modelClass());
    }

    /**
     * Return the prices relationship.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::modelClass());
    }

    /**
     * Check if the supplier is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if the supplier supports a capability.
     */
    public function supports(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? []);
    }

    /**
     * Get a credential value.
     */
    public function getCredential(string $key): ?string
    {
        return $this->credentials[$key] ?? null;
    }

    /**
     * Scope to only enabled suppliers.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Get the last product update check timestamp.
     */
    public function getLastProductUpdateCheck(): ?Carbon
    {
        $timestamp = $this->meta['product_updates_last_checked_at'] ?? null;

        return $timestamp ? Carbon::parse($timestamp) : null;
    }

    /**
     * Set the last product update check timestamp.
     */
    public function setLastProductUpdateCheck(Carbon $timestamp): void
    {
        $meta = $this->meta ?? [];
        $meta['product_updates_last_checked_at'] = $timestamp->toIso8601String();

        $this->update(['meta' => $meta]);
    }

    /**
     * Get a meta value.
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Set a meta value.
     */
    public function setMeta(string $key, mixed $value): void
    {
        $meta = $this->meta ?? [];
        $meta[$key] = $value;

        $this->update(['meta' => $meta]);
    }
}
