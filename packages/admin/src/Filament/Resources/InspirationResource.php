<?php

namespace Lunar\Admin\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Lunar\Admin\Filament\Resources\InspirationResource\Pages;
use Lunar\Admin\Support\Forms\Components\TranslatedText;
use Lunar\Admin\Support\Resources\BaseResource;
use Lunar\Models\Contracts\Inspiration as InspirationContract;

class InspirationResource extends BaseResource
{
    protected static ?string $permission = 'settings';

    protected static ?string $model = InspirationContract::class;

    protected static ?int $navigationSort = 110;

    public static function getLabel(): string
    {
        return __('lunarpanel::inspiration.label');
    }

    public static function getPluralLabel(): string
    {
        return __('lunarpanel::inspiration.plural_label');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-star';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.content');
    }

    public static function getDefaultForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make()
                ->schema([
                    Forms\Components\Section::make(__('lunarpanel::inspiration.form.content.heading'))
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label(__('lunarpanel::inspiration.form.type.label'))
                                ->options([
                                    'review' => __('lunarpanel::inspiration.form.type.options.review'),
                                    'case_study' => __('lunarpanel::inspiration.form.type.options.case_study'),
                                ])
                                ->default('review')
                                ->required()
                                ->live(),
                            Forms\Components\Select::make('rating')
                                ->label(__('lunarpanel::inspiration.form.rating.label'))
                                ->options([
                                    1 => '★☆☆☆☆',
                                    2 => '★★☆☆☆',
                                    3 => '★★★☆☆',
                                    4 => '★★★★☆',
                                    5 => '★★★★★',
                                ])
                                ->default(5)
                                ->required(),
                            TranslatedText::make('text')
                                ->label(__('lunarpanel::inspiration.form.text.label'))
                                ->optionRichtext(true)
                                ->required()
                                ->columnSpanFull(),
                            TranslatedText::make('title')
                                ->label(__('lunarpanel::inspiration.form.title.label'))
                                ->visible(fn (Get $get) => $get('type') === 'case_study')
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('company_name')
                                ->label(__('lunarpanel::inspiration.form.company_name.label'))
                                ->visible(fn (Get $get) => $get('type') === 'case_study'),
                            Forms\Components\TextInput::make('project_type')
                                ->label(__('lunarpanel::inspiration.form.project_type.label'))
                                ->visible(fn (Get $get) => $get('type') === 'case_study'),
                            SpatieMediaLibraryFileUpload::make('photos')
                                ->label(__('lunarpanel::inspiration.form.photos.label'))
                                ->collection('inspiration_photos')
                                ->multiple()
                                ->image()
                                ->imageEditor()
                                ->reorderable()
                                ->maxSize(10240)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpan(['lg' => 2]),
                    Forms\Components\Section::make(__('lunarpanel::inspiration.form.details.heading'))
                        ->schema([
                            Forms\Components\Placeholder::make('order_info')
                                ->label(__('lunarpanel::inspiration.form.order.label'))
                                ->content(fn ($record) => $record?->order?->reference ?? '-'),
                            Forms\Components\Placeholder::make('product_info')
                                ->label(__('lunarpanel::inspiration.form.product.label'))
                                ->content(fn ($record) => $record?->orderLine?->description ?? '-'),
                            Forms\Components\Placeholder::make('customer_info')
                                ->label(__('lunarpanel::inspiration.form.customer.label'))
                                ->content(fn ($record) => $record?->order?->billingAddress?->fullName ?? '-'),
                            Forms\Components\Select::make('status')
                                ->label(__('lunarpanel::inspiration.form.status.label'))
                                ->options([
                                    'pending' => __('lunarpanel::inspiration.form.status.options.pending'),
                                    'approved' => __('lunarpanel::inspiration.form.status.options.approved'),
                                    'rejected' => __('lunarpanel::inspiration.form.status.options.rejected'),
                                ])
                                ->default('pending')
                                ->required()
                                ->live(),
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label(__('lunarpanel::inspiration.form.rejection_reason.label'))
                                ->visible(fn (Get $get) => $get('status') === 'rejected')
                                ->rows(3),
                            Forms\Components\Toggle::make('featured')
                                ->label(__('lunarpanel::inspiration.form.featured.label'))
                                ->helperText(__('lunarpanel::inspiration.form.featured.helper')),
                            Forms\Components\DateTimePicker::make('published_at')
                                ->label(__('lunarpanel::inspiration.form.published_at.label')),
                            Forms\Components\Toggle::make('permission_granted')
                                ->label(__('lunarpanel::inspiration.form.permission_granted.label'))
                                ->disabled()
                                ->helperText(__('lunarpanel::inspiration.form.permission_granted.helper')),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(3),
        ]);
    }

    public static function getDefaultTable(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('photos')
                    ->label(__('lunarpanel::inspiration.table.photos.label'))
                    ->collection('inspiration_photos')
                    ->conversion('small')
                    ->circular(false)
                    ->stacked()
                    ->limit(3)
                    ->height(50),
                Tables\Columns\TextColumn::make('order.reference')
                    ->label(__('lunarpanel::inspiration.table.order.label'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orderLine.description')
                    ->label(__('lunarpanel::inspiration.table.product.label'))
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->orderLine?->description),
                Tables\Columns\TextColumn::make('rating')
                    ->label(__('lunarpanel::inspiration.table.rating.label'))
                    ->formatStateUsing(fn ($state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('lunarpanel::inspiration.table.type.label'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'review' => 'primary',
                        'case_study' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'review' => __('lunarpanel::inspiration.form.type.options.review'),
                        'case_study' => __('lunarpanel::inspiration.form.type.options.case_study'),
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('lunarpanel::inspiration.table.status.label'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('lunarpanel::inspiration.form.status.options.pending'),
                        'approved' => __('lunarpanel::inspiration.form.status.options.approved'),
                        'rejected' => __('lunarpanel::inspiration.form.status.options.rejected'),
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('featured')
                    ->label(__('lunarpanel::inspiration.table.featured.label'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('lunarpanel::inspiration.table.created_at.label'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('lunarpanel::inspiration.form.status.label'))
                    ->options([
                        'pending' => __('lunarpanel::inspiration.form.status.options.pending'),
                        'approved' => __('lunarpanel::inspiration.form.status.options.approved'),
                        'rejected' => __('lunarpanel::inspiration.form.status.options.rejected'),
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('lunarpanel::inspiration.form.type.label'))
                    ->options([
                        'review' => __('lunarpanel::inspiration.form.type.options.review'),
                        'case_study' => __('lunarpanel::inspiration.form.type.options.case_study'),
                    ]),
                Tables\Filters\TernaryFilter::make('featured')
                    ->label(__('lunarpanel::inspiration.form.featured.label')),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('lunarpanel::inspiration.actions.approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->approve()),
                Tables\Actions\Action::make('reject')
                    ->label(__('lunarpanel::inspiration.actions.reject'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label(__('lunarpanel::inspiration.form.rejection_reason.label'))
                            ->required(),
                    ])
                    ->action(fn ($record, array $data) => $record->reject($data['rejection_reason'])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label(__('lunarpanel::inspiration.actions.approve_selected'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->approve()),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListInspirations::route('/'),
            'create' => Pages\CreateInspiration::route('/create'),
            'edit' => Pages\EditInspiration::route('/{record}/edit'),
        ];
    }
}
