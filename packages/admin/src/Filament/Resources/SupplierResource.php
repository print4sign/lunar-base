<?php

namespace Lunar\Admin\Filament\Resources;

use Awcodes\FilamentBadgeableColumn\Components\Badge;
use Awcodes\FilamentBadgeableColumn\Components\BadgeableColumn;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Lunar\Admin\Filament\Resources\SupplierResource\Pages;
use Lunar\Admin\Support\Resources\BaseResource;
use Lunar\Models\Contracts\Supplier as SupplierContract;

class SupplierResource extends BaseResource
{
    protected static ?string $permission = 'settings:core';

    protected static ?string $model = SupplierContract::class;

    protected static ?int $navigationSort = 2;

    public static function getLabel(): string
    {
        return __('lunarpanel::supplier.label');
    }

    public static function getPluralLabel(): string
    {
        return __('lunarpanel::supplier.plural_label');
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::suppliers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.settings');
    }

    protected static function getMainFormComponents(): array
    {
        return [
            static::getNameFormComponent(),
            static::getHandleFormComponent(),
            static::getDriverFormComponent(),
            static::getEnabledFormComponent(),
            static::getPriorityFormComponent(),
            static::getCredentialsFormComponent(),
            static::getCapabilitiesFormComponent(),
        ];
    }

    protected static function getNameFormComponent(): Component
    {
        return Forms\Components\TextInput::make('name')
            ->label(__('lunarpanel::supplier.form.name.label'))
            ->required()
            ->maxLength(255)
            ->afterStateUpdated(function (string $operation, $state, Forms\Set $set) {
                if ($operation !== 'create') {
                    return;
                }
                $set('handle', Str::slug($state));
            })
            ->live(onBlur: true)
            ->autofocus();
    }

    protected static function getHandleFormComponent(): Component
    {
        return Forms\Components\TextInput::make('handle')
            ->label(__('lunarpanel::supplier.form.handle.label'))
            ->required()
            ->unique(ignoreRecord: true)
            ->minLength(3)
            ->maxLength(255);
    }

    protected static function getDriverFormComponent(): Component
    {
        $drivers = collect(config('lunar.suppliers.drivers', []))
            ->keys()
            ->mapWithKeys(fn ($driver) => [$driver => Str::title($driver)])
            ->toArray();

        return Forms\Components\Select::make('driver')
            ->label(__('lunarpanel::supplier.form.driver.label'))
            ->options($drivers)
            ->required()
            ->helperText(__('lunarpanel::supplier.form.driver.helper_text'));
    }

    protected static function getEnabledFormComponent(): Component
    {
        return Forms\Components\Toggle::make('enabled')
            ->label(__('lunarpanel::supplier.form.enabled.label'))
            ->helperText(__('lunarpanel::supplier.form.enabled.helper_text'))
            ->default(false);
    }

    protected static function getPriorityFormComponent(): Component
    {
        return Forms\Components\TextInput::make('priority')
            ->label(__('lunarpanel::supplier.form.priority.label'))
            ->helperText(__('lunarpanel::supplier.form.priority.helper_text'))
            ->numeric()
            ->default(0);
    }

    protected static function getCredentialsFormComponent(): Component
    {
        return Forms\Components\KeyValue::make('credentials')
            ->label(__('lunarpanel::supplier.form.credentials.label'))
            ->helperText(__('lunarpanel::supplier.form.credentials.helper_text'))
            ->keyLabel(__('lunarpanel::supplier.form.credentials.key_label'))
            ->valueLabel(__('lunarpanel::supplier.form.credentials.value_label'))
            ->addActionLabel(__('lunarpanel::supplier.form.credentials.add_label'))
            ->columnSpanFull();
    }

    protected static function getCapabilitiesFormComponent(): Component
    {
        return Forms\Components\CheckboxList::make('capabilities')
            ->label(__('lunarpanel::supplier.form.capabilities.label'))
            ->helperText(__('lunarpanel::supplier.form.capabilities.helper_text'))
            ->options([
                'catalog_sync' => __('lunarpanel::supplier.form.capabilities.options.catalog_sync'),
                'pricing' => __('lunarpanel::supplier.form.capabilities.options.pricing'),
                'ordering' => __('lunarpanel::supplier.form.capabilities.options.ordering'),
                'file_upload' => __('lunarpanel::supplier.form.capabilities.options.file_upload'),
                'tracking' => __('lunarpanel::supplier.form.capabilities.options.tracking'),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    public static function getDefaultTable(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('enabled')
                    ->label(__('lunarpanel::supplier.table.enabled.label')),
            ])
            ->actions([
                Tables\Actions\Action::make('sync')
                    ->label(__('lunarpanel::supplier.table.actions.sync.label'))
                    ->icon('lucide-refresh-cw')
                    ->action(function (Model $record) {
                        // TODO: Dispatch sync job
                    })
                    ->visible(fn (Model $record) => $record->isEnabled() && $record->supports('catalog_sync')),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            BadgeableColumn::make('name')
                ->separator('')
                ->suffixBadges([
                    Badge::make('enabled')
                        ->label(__('lunarpanel::supplier.table.enabled.badge'))
                        ->color('success')
                        ->visible(fn (Model $record) => $record->enabled),
                    Badge::make('disabled')
                        ->label(__('lunarpanel::supplier.table.disabled.badge'))
                        ->color('gray')
                        ->visible(fn (Model $record) => ! $record->enabled),
                ])
                ->label(__('lunarpanel::supplier.table.name.label')),
            Tables\Columns\TextColumn::make('handle')
                ->label(__('lunarpanel::supplier.table.handle.label')),
            Tables\Columns\TextColumn::make('driver')
                ->label(__('lunarpanel::supplier.table.driver.label'))
                ->formatStateUsing(fn ($state) => Str::title($state)),
            Tables\Columns\TextColumn::make('priority')
                ->label(__('lunarpanel::supplier.table.priority.label'))
                ->sortable(),
            Tables\Columns\TextColumn::make('products_count')
                ->counts('products')
                ->label(__('lunarpanel::supplier.table.products_count.label')),
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
