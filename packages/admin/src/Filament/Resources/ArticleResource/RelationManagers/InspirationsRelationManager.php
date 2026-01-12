<?php

namespace Lunar\Admin\Filament\Resources\ArticleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Lunar\Models\Inspiration;

class InspirationsRelationManager extends RelationManager
{
    protected static string $relationship = 'inspirations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('position')
                    ->label(__('lunarpanel::inspiration.form.position.label'))
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                SpatieMediaLibraryImageColumn::make('photos')
                    ->label(__('lunarpanel::inspiration.table.photos.label'))
                    ->collection('inspiration_photos')
                    ->conversion('small')
                    ->circular(false)
                    ->stacked()
                    ->limit(2)
                    ->height(40),
                Tables\Columns\TextColumn::make('orderLine.description')
                    ->label(__('lunarpanel::inspiration.table.product.label'))
                    ->limit(30),
                Tables\Columns\TextColumn::make('rating')
                    ->label(__('lunarpanel::inspiration.table.rating.label'))
                    ->formatStateUsing(fn ($state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('lunarpanel::inspiration.table.type.label'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'review' => 'primary',
                        'case_study' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('pivot.position')
                    ->label(__('lunarpanel::inspiration.form.position.label'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query->approved())
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('position')
                            ->label(__('lunarpanel::inspiration.form.position.label'))
                            ->numeric()
                            ->default(0),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->reorderable('pivot.position')
            ->defaultSort('pivot.position');
    }
}
