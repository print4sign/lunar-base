<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasMedia;
use Lunar\Base\Traits\HasTranslations;
use Lunar\Models\Contracts\Inspiration as InspirationContract;
use Spatie\MediaLibrary\HasMedia as SpatieHasMedia;

/**
 * @property int $id
 * @property int $order_id
 * @property ?int $order_line_id
 * @property ?int $user_id
 * @property string $type
 * @property int $rating
 * @property array $text
 * @property bool $permission_granted
 * @property ?array $title
 * @property ?string $company_name
 * @property ?string $project_type
 * @property string $status
 * @property ?string $rejection_reason
 * @property ?int $moderated_by
 * @property ?\Illuminate\Support\Carbon $moderated_at
 * @property bool $featured
 * @property ?\Illuminate\Support\Carbon $published_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 */
class Inspiration extends BaseModel implements InspirationContract, SpatieHasMedia
{
    use HasFactory;
    use HasMedia;
    use HasTranslations;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'text' => 'array',
        'title' => 'array',
        'rating' => 'integer',
        'permission_granted' => 'boolean',
        'featured' => 'boolean',
        'published_at' => 'datetime',
        'moderated_at' => 'datetime',
    ];

    /**
     * Get the order this inspiration belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::modelClass());
    }

    /**
     * Get the order line this inspiration is for.
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::modelClass());
    }

    /**
     * Get the user who submitted this inspiration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model')
        );
    }

    /**
     * Get the user who moderated this inspiration.
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model'),
            'moderated_by'
        );
    }

    /**
     * Get the articles this inspiration is linked to.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            Article::modelClass(),
            config('lunar.database.table_prefix').'article_inspiration'
        )->withPivot(['position'])->orderByPivot('position')->withTimestamps();
    }

    /**
     * Get all photos for this inspiration.
     */
    public function photos(): MorphMany
    {
        return $this->media()->where('collection_name', 'inspiration_photos');
    }

    /**
     * Scope to only approved inspirations.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to only pending inspirations.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to only rejected inspirations.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to only featured inspirations.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    /**
     * Scope to published inspirations (approved and has published_at).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to inspirations for a specific product.
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->whereHas('orderLine', function (Builder $q) use ($productId) {
            $q->where('purchasable_type', ProductVariant::morphName())
                ->whereHas('purchasable', function (Builder $q2) use ($productId) {
                    $q2->where('product_id', $productId);
                });
        });
    }

    /**
     * Check if this inspiration is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if this inspiration is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if this inspiration is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if this is a case study (extended review).
     */
    public function isCaseStudy(): bool
    {
        return $this->type === 'case_study';
    }

    /**
     * Get the translated text.
     */
    public function getText(?string $locale = null): ?string
    {
        return $this->translate('text', $locale);
    }

    /**
     * Get the translated title.
     */
    public function getTitle(?string $locale = null): ?string
    {
        return $this->translate('title', $locale);
    }

    /**
     * Get the product name from the order line.
     */
    public function getProductName(): ?string
    {
        return $this->orderLine?->description;
    }

    /**
     * Get the first photo URL.
     */
    public function getFirstPhotoUrl(?string $conversion = null): ?string
    {
        $media = $this->getFirstMedia('inspiration_photos');

        if (! $media) {
            return null;
        }

        return $conversion ? $media->getUrl($conversion) : $media->getUrl();
    }

    /**
     * Approve this inspiration.
     */
    public function approve(?int $moderatorId = null): self
    {
        $this->update([
            'status' => 'approved',
            'moderated_by' => $moderatorId ?? auth()->id(),
            'moderated_at' => now(),
            'published_at' => $this->published_at ?? now(),
        ]);

        return $this;
    }

    /**
     * Reject this inspiration.
     */
    public function reject(?string $reason = null, ?int $moderatorId = null): self
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'moderated_by' => $moderatorId ?? auth()->id(),
            'moderated_at' => now(),
        ]);

        return $this;
    }
}
