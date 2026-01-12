<?php

namespace Lunar\DataTransferObjects;

use Illuminate\Support\Collection;

class CancellationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly Collection $cancelled,
        public readonly Collection $failed,
        public readonly int $totalRefund
    ) {}

    public function getErrorMessage(): string
    {
        return $this->failed->map(fn($f) => $f['reason'])->implode(', ');
    }
}
