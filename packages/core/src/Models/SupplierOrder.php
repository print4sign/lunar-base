<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Base\BaseModel;
use Lunar\Base\Casts\Price as PriceCast;
use Lunar\Base\Traits\HasMacros;
use Lunar\Base\Traits\LogsActivity;
use Lunar\Database\Factories\SupplierOrderFactory;

/**
 * @property int $id
 * @property int $order_id
 * @property int $order_line_id
 * @property int $supplier_id
 * @property ?string $external_order_id
 * @property string $status
 * @property ?array $external_data
 * @property ?array $tracking
 * @property ?int $cost_price
 * @property ?string $currency_code
 * @property ?\Illuminate\Support\Carbon $submitted_at
 * @property ?\Illuminate\Support\Carbon $shipped_at
 * @property ?\Illuminate\Support\Carbon $delivered_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
class SupplierOrder extends BaseModel implements Contracts\SupplierOrder
{
    use HasFactory;
    use HasMacros;
    use LogsActivity;

    /**
     * Return a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return SupplierOrderFactory::new();
    }

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * {@inheritDoc}
     */
    protected $guarded = [];

    /**
     * {@inheritDoc}
     */
    protected $casts = [
        'external_data' => 'array',
        'tracking' => 'array',
        'cost_price' => PriceCast::class,
        'submitted_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Return the order relationship.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::modelClass());
    }

    /**
     * Return the order line relationship.
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::modelClass());
    }

    /**
     * Return the supplier relationship.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::modelClass());
    }

    /**
     * Check if the order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the order has been submitted.
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
        ]);
    }

    /**
     * Check if the order has been shipped.
     */
    public function isShipped(): bool
    {
        return in_array($this->status, [
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
        ]);
    }

    /**
     * Check if the order has been delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Check if the order has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the order has been cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Get the tracking numbers.
     */
    public function getTrackingNumbers(): array
    {
        return $this->tracking['numbers'] ?? [];
    }

    /**
     * Get the tracking URL.
     */
    public function getTrackingUrl(): ?string
    {
        return $this->tracking['url'] ?? null;
    }

    /**
     * Scope to pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to submitted orders.
     */
    public function scopeSubmitted($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Scope to failed orders.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }
}
