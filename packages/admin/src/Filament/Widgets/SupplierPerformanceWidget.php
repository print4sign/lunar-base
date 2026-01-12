<?php

namespace Lunar\Admin\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Lunar\Services\SupplierMetricsService;

class SupplierPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $metricsService = app(SupplierMetricsService::class);
        $performance = $metricsService->getSupplierPerformance(30);

        return $table
            ->query(
                // We use a dummy query since we're providing data directly
                \Lunar\Models\Supplier::query()->whereIn('id', $performance->pluck('supplier_id'))
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Orders (30d)')
                    ->getStateUsing(fn ($record) => $performance->firstWhere('supplier_id', $record->id)['total_orders'] ?? 0)
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_rate')
                    ->label('Delivery Rate')
                    ->getStateUsing(fn ($record) => $performance->firstWhere('supplier_id', $record->id)['delivery_rate'] ?? 0)
                    ->suffix('%')
                    ->color(fn ($state) => $state >= 95 ? 'success' : ($state >= 85 ? 'warning' : 'danger'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('avg_delivery_time')
                    ->label('Avg Delivery')
                    ->getStateUsing(fn ($record) => $performance->firstWhere('supplier_id', $record->id)['avg_delivery_time_hours'] ?? 0)
                    ->suffix(' hrs')
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit_margin')
                    ->label('Margin')
                    ->getStateUsing(fn ($record) => $performance->firstWhere('supplier_id', $record->id)['profit_margin_percent'] ?? 0)
                    ->suffix('%')
                    ->color(fn ($state) => $state >= 30 ? 'success' : ($state >= 20 ? 'warning' : 'danger'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('issues_count')
                    ->label('Issues')
                    ->getStateUsing(fn ($record) => $performance->firstWhere('supplier_id', $record->id)['issues_count'] ?? 0)
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->sortable(),
            ]);
    }

    protected function getTableHeading(): string
    {
        return 'Supplier Performance (Last 30 Days)';
    }
}
