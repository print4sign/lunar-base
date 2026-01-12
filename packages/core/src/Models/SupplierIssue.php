<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Base\BaseModel;
use Lunar\Base\Casts\Price;
use Lunar\Base\Traits\HasMacros;
use Lunar\Base\Traits\LogsActivity;

class SupplierIssue extends BaseModel
{
    use HasMacros, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'compensation_amount' => Price::class,
        'reported_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::modelClass());
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reported_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['reported', 'acknowledged']);
    }
}
