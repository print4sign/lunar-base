<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasTranslations;
use Lunar\Models\Contracts\Article as ArticleContract;

/**
 * @property int $id
 * @property array $title
 * @property string $slug
 * @property ?array $excerpt
 * @property array $body
 * @property ?array $meta_description
 * @property ?string $featured_image
 * @property string $category
 * @property ?string $subcategory
 * @property ?array $tags
 * @property ?string $source_url
 * @property ?array $original_content
 * @property string $status
 * @property ?\Illuminate\Support\Carbon $published_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 */
class Article extends BaseModel implements ArticleContract
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'title' => 'array',
        'excerpt' => 'array',
        'body' => 'array',
        'meta_description' => 'array',
        'tags' => 'array',
        'original_content' => 'array',
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function getTitle(?string $locale = null): ?string
    {
        return $this->translate('title', $locale);
    }

    public function getExcerpt(?string $locale = null): ?string
    {
        return $this->translate('excerpt', $locale);
    }

    public function getBody(?string $locale = null): ?string
    {
        return $this->translate('body', $locale);
    }

    public function getMetaDescription(?string $locale = null): ?string
    {
        return $this->translate('meta_description', $locale);
    }
}
