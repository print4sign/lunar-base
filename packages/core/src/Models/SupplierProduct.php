<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasMacros;
use Lunar\Base\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $supplier_id
 * @property ?int $product_id
 * @property ?int $product_variant_id
 * @property string $external_id
 * @property ?string $external_name
 * @property ?array $external_data
 * @property ?array $configurator_schema
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
        'synced' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

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
}
