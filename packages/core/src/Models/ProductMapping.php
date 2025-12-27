<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasMacros;
use Lunar\Base\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $canonical_name
 * @property string $canonical_handle
 * @property ?array $attributes
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
class ProductMapping extends BaseModel implements Contracts\ProductMapping
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
        'attributes' => 'array',
    ];

    /**
     * Return the supplier products relationship.
     */
    public function supplierProducts(): BelongsToMany
    {
        $prefix = config('lunar.database.table_prefix');

        return $this->belongsToMany(
            SupplierProduct::modelClass(),
            "{$prefix}product_mapping_suppliers"
        )->withPivot([
            'confidence_score',
            'status',
            'confirmed_by',
            'confirmed_at',
        ])->withTimestamps();
    }

    /**
     * Return the confirmed supplier products relationship.
     */
    public function confirmedSupplierProducts(): BelongsToMany
    {
        return $this->supplierProducts()->wherePivot('status', 'confirmed');
    }

    /**
     * Return the pending supplier products relationship.
     */
    public function pendingSupplierProducts(): BelongsToMany
    {
        return $this->supplierProducts()->wherePivot('status', 'pending');
    }

    /**
     * Scope to mappings with pending reviews.
     */
    public function scopeWithPendingReviews($query)
    {
        return $query->whereHas('supplierProducts', function ($q) {
            $q->wherePivot('status', 'pending');
        });
    }
}
