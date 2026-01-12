<?php

namespace Lunar\Admin\Filament\Resources\CartResource\Pages;

use Lunar\Admin\Filament\Resources\CartResource;
use Lunar\Admin\Support\Pages\BaseListRecords;

class ListCarts extends BaseListRecords
{
    protected static string $resource = CartResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [];
    }
}
