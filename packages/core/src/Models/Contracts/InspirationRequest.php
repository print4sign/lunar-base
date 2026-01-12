<?php

namespace Lunar\Models\Contracts;

interface InspirationRequest
{
    public function isExpired(): bool;

    public function isCompleted(): bool;

    public function markAsOpened(): void;

    public function markAsCompleted(): void;

    public static function generateToken(): string;
}
