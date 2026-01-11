<?php

namespace Lunar\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Article
{
    public function scopePublished(Builder $query): Builder;

    public function scopeByCategory(Builder $query, string $category): Builder;

    public function scopeDraft(Builder $query): Builder;

    public function isPublished(): bool;
}
