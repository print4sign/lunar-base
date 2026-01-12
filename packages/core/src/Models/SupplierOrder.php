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

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ARTWORK_READY = 'artwork_ready';

    public const STATUS_COMPLETED = 'completed';

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
        'artwork_files' => 'array',
        'supplier_additional_costs' => 'array',
        'tracking_numbers' => 'array',
        'cost_price' => PriceCast::class,
        'submitted_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancellation_deadline' => 'datetime',
        'cancellation_requested_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'approved_at' => 'datetime',
        'artwork_approval_deadline' => 'datetime',
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'refund_issued_at' => 'datetime',
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

    /**
     * Check if this supplier order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        // Already cancelled or delivered
        if (in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED,
        ])) {
            return false;
        }

        // Check supplier's allowed statuses
        $allowedStatuses = $this->supplier->getMeta('cancellation_allowed_statuses', [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_SUBMITTED,
        ]);

        if (!in_array($this->status, $allowedStatuses)) {
            return false;
        }

        // Check cancellation deadline
        if ($this->cancellation_deadline && now()->isAfter($this->cancellation_deadline)) {
            return false;
        }

        return true;
    }

    /**
     * Get the cancellation fee based on current status.
     */
    public function getCancellationFee(): int
    {
        return match($this->status) {
            self::STATUS_PENDING,
            self::STATUS_ARTWORK_READY => 0,

            self::STATUS_APPROVED,
            self::STATUS_SUBMITTED => (int) ($this->estimated_cost_price * 0.1),

            self::STATUS_PROCESSING => (int) ($this->estimated_cost_price * 0.5),

            default => $this->estimated_cost_price,
        };
    }

    /**
     * Get human-readable time remaining until cancellation deadline.
     */
    public function getCancellationTimeRemaining(): ?string
    {
        if (!$this->canBeCancelled() || !$this->cancellation_deadline) {
            return null;
        }

        return $this->cancellation_deadline->diffForHumans();
    }
}
