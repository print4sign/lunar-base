<?php

namespace Lunar\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Inspiration
{
    public function scopeApproved(Builder $query): Builder;

    public function scopePending(Builder $query): Builder;

    public function scopeRejected(Builder $query): Builder;

    public function scopePublished(Builder $query): Builder;

    public function scopeFeatured(Builder $query): Builder;

    public function isApproved(): bool;

    public function isPending(): bool;

    public function isRejected(): bool;

    public function isCaseStudy(): bool;
}
