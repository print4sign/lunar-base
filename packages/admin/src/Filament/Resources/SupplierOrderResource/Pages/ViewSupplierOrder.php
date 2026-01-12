<?php

namespace Lunar\Admin\Filament\Resources\SupplierOrderResource\Pages;

use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Lunar\Admin\Filament\Resources\SupplierOrderResource;

class ViewSupplierOrder extends ViewRecord
{
    protected static string $resource = SupplierOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('order.reference')
                            ->label('Order Reference'),
                        Infolists\Components\TextEntry::make('supplier.name')
                            ->label('Supplier'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'gray',
                                'approved' => 'info',
                                'submitted', 'processing' => 'warning',
                                'shipped' => 'primary',
                                'delivered' => 'success',
                                'cancelled', 'failed' => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('external_order_id')
                            ->label('External Order ID'),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Financial Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('estimated_cost_price')
                            ->label('Estimated Cost')
                            ->money('EUR', divideBy: 100),
                        Infolists\Components\TextEntry::make('actual_cost_price')
                            ->label('Actual Cost')
                            ->money('EUR', divideBy: 100),
                        Infolists\Components\TextEntry::make('order_line_total')
                            ->label('Revenue')
                            ->money('EUR', divideBy: 100),
                        Infolists\Components\TextEntry::make('profit_margin_cents')
                            ->label('Profit Margin')
                            ->money('EUR', divideBy: 100),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('submitted_at')
                            ->label('Submitted')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('shipped_at')
                            ->label('Shipped')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('delivered_at')
                            ->label('Delivered')
                            ->dateTime(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Selection Data')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('external_data')
                            ->label('Supplier Selection Details'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
