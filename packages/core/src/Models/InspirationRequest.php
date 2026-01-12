<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Lunar\Base\BaseModel;
use Lunar\Models\Contracts\InspirationRequest as InspirationRequestContract;

/**
 * @property int $id
 * @property int $order_id
 * @property ?int $order_line_id
 * @property string $token
 * @property ?\Illuminate\Support\Carbon $sent_at
 * @property ?\Illuminate\Support\Carbon $opened_at
 * @property ?\Illuminate\Support\Carbon $completed_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class InspirationRequest extends BaseModel implements InspirationRequestContract
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the order this request belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::modelClass());
    }

    /**
     * Get the order line this request is for.
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::modelClass());
    }

    /**
     * Check if this request has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if this request has been completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if this request is still valid (not expired and not completed).
     */
    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isCompleted();
    }

    /**
     * Mark this request as opened.
     */
    public function markAsOpened(): void
    {
        if ($this->opened_at === null) {
            $this->update(['opened_at' => now()]);
        }
    }

    /**
     * Mark this request as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update(['completed_at' => now()]);
    }

    /**
     * Mark this request as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['sent_at' => now()]);
    }

    /**
     * Generate a secure random token.
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Find a request by its token.
     */
    public static function findByToken(string $token): ?self
    {
        return static::where('token', $token)->first();
    }
}
