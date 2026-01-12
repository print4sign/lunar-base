<?php

namespace Lunar\Admin\Filament\Resources\SupplierProductResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\SupplierProductResource;
use Lunar\Admin\Support\Pages\BaseListRecords;

class ListSupplierProducts extends BaseListRecords
{
    protected static string $resource = SupplierProductResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\Action::make('check_updates')
                ->label(__('lunarpanel::supplierproduct.actions.check_updates.label'))
                ->icon('lucide-refresh-cw')
                ->action(function () {
                    \Illuminate\Support\Facades\Artisan::call('lunar:supplier:check-updates', ['--all' => true]);

                    $this->notify('success', __('lunarpanel::supplierproduct.actions.check_updates.success'));
                }),
        ];
    }
}
