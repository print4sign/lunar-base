<?php

namespace Lunar\Admin\Filament\Resources\InspirationResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\InspirationResource;
use Lunar\Admin\Support\Pages\BaseListRecords;

class ListInspirations extends BaseListRecords
{
    protected static string $resource = InspirationResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
