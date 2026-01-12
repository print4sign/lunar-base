<?php

namespace Lunar\Admin\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Admin\Filament\Resources\SupplierOrderResource\Pages;
use Lunar\Models\SupplierOrder;

class SupplierOrderResource extends Resource
{
    protected static ?string $model = SupplierOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Fulfillment';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'reference')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'submitted' => 'Submitted',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                                'failed' => 'Failed',
                            ])
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Pricing & Costs')
                    ->schema([
                        Forms\Components\TextInput::make('estimated_cost_price')
                            ->label('Estimated Cost (cents)')
                            ->numeric()
                            ->suffix('cents'),
                        Forms\Components\TextInput::make('actual_cost_price')
                            ->label('Actual Cost (cents)')
                            ->numeric()
                            ->suffix('cents'),
                        Forms\Components\TextInput::make('order_line_total')
                            ->label('Revenue (cents)')
                            ->numeric()
                            ->suffix('cents'),
                        Forms\Components\TextInput::make('profit_margin_cents')
                            ->label('Profit Margin (cents)')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('External Data')
                    ->schema([
                        Forms\Components\TextInput::make('external_order_id')
                            ->label('External Order ID'),
                        Forms\Components\KeyValue::make('external_data')
                            ->label('Selection Data'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.reference')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'pending',
                        'info' => 'approved',
                        'warning' => ['submitted', 'processing'],
                        'primary' => 'shipped',
                        'success' => 'delivered',
                        'danger' => ['cancelled', 'failed'],
                    ]),
                Tables\Columns\TextColumn::make('estimated_cost_price')
                    ->label('Est. Cost')
                    ->money('EUR', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_line_total')
                    ->label('Revenue')
                    ->money('EUR', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('profit_margin_cents')
                    ->label('Margin')
                    ->money('EUR', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Delivered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'submitted' => 'Submitted',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
                Tables\Filters\Filter::make('requires_approval')
                    ->query(fn ($query) => $query->where('requires_approval', true))
                    ->label('Requires Approval'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->requires_approval && !$record->approved_at)
                    ->action(function ($record) {
                        $record->update([
                            'approved_at' => now(),
                            'artwork_status' => 'approved',
                        ]);
                    }),
                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->canBeCancelled())
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label('Cancellation Reason'),
                    ])
                    ->action(function ($record, array $data) {
                        $service = app(\Lunar\Services\OrderCancellationService::class);
                        $service->cancelSupplierOrder(
                            $record,
                            auth()->user(),
                            $data['reason'],
                            false
                        );
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierOrders::route('/'),
            'create' => Pages\CreateSupplierOrder::route('/create'),
            'view' => Pages\ViewSupplierOrder::route('/{record}'),
            'edit' => Pages\EditSupplierOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('requires_approval', true)
            ->whereNull('approved_at')
            ->count() ?: null;
    }
}
