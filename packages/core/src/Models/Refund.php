<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Base\BaseModel;
use Lunar\Base\Casts\Price;
use Lunar\Base\Traits\HasMacros;
use Lunar\Base\Traits\LogsActivity;

class Refund extends BaseModel
{
    use HasMacros, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'amount' => Price::class,
        'meta' => 'array',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::modelClass());
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::modelClass());
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }
}
