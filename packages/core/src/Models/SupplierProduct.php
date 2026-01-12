<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasMacros;
use Lunar\Base\Traits\LogsActivity;
use Lunar\Database\Factories\SupplierProductFactory;

/**
 * @property int $id
 * @property int $supplier_id
 * @property ?int $product_id
 * @property ?int $product_variant_id
 * @property string $external_id
 * @property ?string $external_name
 * @property ?array $external_data
 * @property ?array $configurator_schema
 * @property bool $active
 * @property ?\Illuminate\Support\Carbon $active_to
 * @property ?string $replaced_by_external_id
 * @property ?int $replaced_by_id
 * @property bool $synced
 * @property ?\Illuminate\Support\Carbon $last_synced_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
class SupplierProduct extends BaseModel implements Contracts\SupplierProduct
{
    use HasFactory;
    use HasMacros;
    use LogsActivity;

    /**
     * {@inheritDoc}
     */
    protected $guarded = [];

    /**
     * {@inheritDoc}
     */
    protected $casts = [
        'external_data' => 'array',
        'configurator_schema' => 'array',
        'active' => 'boolean',
        'active_to' => 'datetime',
        'synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return SupplierProductFactory::new();
    }

    /**
     * Return the supplier relationship.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::modelClass());
    }

    /**
     * Return the product relationship.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::modelClass());
    }

    /**
     * Return the product variant relationship.
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::modelClass());
    }

    /**
     * Return the variants relationship.
     * Variants that were generated from this supplier product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::modelClass());
    }

    /**
     * Return the product mappings relationship.
     */
    public function mappings(): BelongsToMany
    {
        $prefix = config('lunar.database.table_prefix');

        return $this->belongsToMany(
            ProductMapping::modelClass(),
            "{$prefix}product_mapping_suppliers"
        )->withPivot([
            'confidence_score',
            'status',
            'confirmed_by',
            'confirmed_at',
        ])->withTimestamps();
    }

    /**
     * Check if the supplier product has a configurator schema.
     */
    public function hasConfiguratorSchema(): bool
    {
        return ! empty($this->configurator_schema);
    }

    /**
     * Scope to only synced products.
     */
    public function scopeSynced($query)
    {
        return $query->where('synced', true);
    }

    /**
     * Scope to only unsynced products.
     */
    public function scopeUnsynced($query)
    {
        return $query->where('synced', false);
    }

    /**
     * Mark the product as synced.
     */
    public function markAsSynced(): bool
    {
        return $this->update([
            'synced' => true,
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Return the replacement supplier product relationship.
     */
    public function replacedBy(): BelongsTo
    {
        return $this->belongsTo(self::modelClass(), 'replaced_by_id');
    }

    /**
     * Return the products that this product replaces.
     */
    public function replaces(): HasMany
    {
        return $this->hasMany(self::modelClass(), 'replaced_by_id');
    }

    /**
     * Scope to only active products.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to only discontinued products.
     */
    public function scopeDiscontinued($query)
    {
        return $query->where('active', false);
    }

    /**
     * Scope to products with a replacement.
     */
    public function scopeHasReplacement($query)
    {
        return $query->whereNotNull('replaced_by_id');
    }

    /**
     * Scope to products approaching discontinuation.
     */
    public function scopeApproachingDiscontinuation($query, int $days = 30)
    {
        return $query->where('active', true)
            ->whereNotNull('active_to')
            ->where('active_to', '<=', now()->addDays($days));
    }

    /**
     * Check if the product is discontinued.
     */
    public function isDiscontinued(): bool
    {
        return ! $this->active;
    }

    /**
     * Check if the product has a replacement.
     */
    public function hasReplacement(): bool
    {
        return ! is_null($this->replaced_by_id);
    }

    /**
     * Check if the product is approaching discontinuation.
     */
    public function isApproachingDiscontinuation(int $days = 30): bool
    {
        if (! $this->active_to) {
            return false;
        }

        return $this->active && $this->active_to->lte(now()->addDays($days));
    }

    /**
     * Mark the product as discontinued.
     */
    public function markAsDiscontinued(): bool
    {
        return $this->update([
            'active' => false,
        ]);
    }
}
