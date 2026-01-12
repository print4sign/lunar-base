<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'contact_person',
        'street',
        'house_number',
        'postal_code',
        'city',
        'country',
        'email',
        'selected_samples',
        'status',
        'shipped_at',
    ];

    protected $casts = [
        'selected_samples' => 'array',
        'shipped_at' => 'datetime',
    ];

    /**
     * Get the full address as a string.
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->street} {$this->house_number}, {$this->postal_code} {$this->city}, {$this->country}";
    }

    /**
     * Get the number of samples requested.
     */
    public function getSampleCountAttribute(): int
    {
        return count($this->selected_samples ?? []);
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for shipped requests.
     */
    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    /**
     * Mark the request as shipped.
     */
    public function markAsShipped(): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
    }
}
