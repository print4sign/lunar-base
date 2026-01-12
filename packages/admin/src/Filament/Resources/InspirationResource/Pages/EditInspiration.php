<?php

namespace Lunar\Admin\Filament\Resources\InspirationResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\InspirationResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;

class EditInspiration extends BaseEditRecord
{
    protected static string $resource = InspirationResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
