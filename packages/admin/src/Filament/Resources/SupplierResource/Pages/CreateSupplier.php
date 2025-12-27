<?php

namespace Lunar\Admin\Filament\Resources\SupplierResource\Pages;

use Lunar\Admin\Filament\Resources\SupplierResource;
use Lunar\Admin\Support\Pages\BaseCreateRecord;

class CreateSupplier extends BaseCreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
