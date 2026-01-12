<?php

namespace Lunar\Admin\Filament\Resources\SupplierProductResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Lunar\Admin\Filament\Resources\SupplierProductResource;

class ViewSupplierProduct extends ViewRecord
{
    protected static string $resource = SupplierProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label(__('lunarpanel::supplierproduct.actions.sync.label'))
                ->icon('lucide-refresh-cw')
                ->action(function () {
                    $record = $this->getRecord();
                    $driver = \Lunar\Facades\Suppliers::supplier($record->supplier);
                    $productData = $driver->getProduct($record->external_id);

                    if ($productData) {
                        \Lunar\Jobs\Suppliers\CheckSupplierProductUpdate::dispatchSync($record, $productData);
                        $this->refreshFormData(['active', 'active_to', 'replaced_by_external_id', 'replaced_by_id']);
                    }

                    $this->notify('success', __('lunarpanel::supplierproduct.actions.sync.success'));
                }),
            Actions\Action::make('view_replacement')
                ->label(__('lunarpanel::supplierproduct.actions.view_replacement.label'))
                ->icon('lucide-arrow-right')
                ->url(fn () => $this->getRecord()->replacedBy
                    ? SupplierProductResource::getUrl('view', ['record' => $this->getRecord()->replacedBy])
                    : null)
                ->visible(fn () => $this->getRecord()->hasReplacement()),
        ];
    }
}
