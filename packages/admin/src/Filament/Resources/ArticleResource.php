<?php

namespace Lunar\Admin\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Admin\Filament\Resources\ArticleResource\Pages;
use Lunar\Admin\Support\Resources\BaseResource;
use Lunar\Models\Contracts\Article as ArticleContract;

class ArticleResource extends BaseResource
{
    protected static ?string $permission = 'content:manage-articles';

    protected static ?string $model = ArticleContract::class;

    protected static ?int $navigationSort = 100;

    public static function getLabel(): string
    {
        return __('lunarpanel::article.label');
    }

    public static function getPluralLabel(): string
    {
        return __('lunarpanel::article.plural_label');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
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
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\TextInput::make('title.nl')
                                ->label(__('lunarpanel::article.form.title.label').' (NL)')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('title.en')
                                ->label(__('lunarpanel::article.form.title.label').' (EN)')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('slug')
                                ->label(__('lunarpanel::article.form.slug.label'))
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            Forms\Components\Textarea::make('excerpt.nl')
                                ->label(__('lunarpanel::article.form.excerpt.label').' (NL)')
                                ->rows(3),
                            Forms\Components\Textarea::make('excerpt.en')
                                ->label(__('lunarpanel::article.form.excerpt.label').' (EN)')
                                ->rows(3),
                            Forms\Components\RichEditor::make('body.nl')
                                ->label(__('lunarpanel::article.form.body.label').' (NL)')
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\RichEditor::make('body.en')
                                ->label(__('lunarpanel::article.form.body.label').' (EN)')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpan(['lg' => 2]),
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label(__('lunarpanel::article.form.status.label'))
                                ->options([
                                    'draft' => __('lunarpanel::article.form.status.options.draft'),
                                    'rewritten' => __('lunarpanel::article.form.status.options.rewritten'),
                                    'published' => __('lunarpanel::article.form.status.options.published'),
                                ])
                                ->default('draft')
                                ->required(),
                            Forms\Components\Select::make('category')
                                ->label(__('lunarpanel::article.form.category.label'))
                                ->options([
                                    'klantenservice' => __('lunarpanel::article.form.category.options.klantenservice'),
                                    'blog' => __('lunarpanel::article.form.category.options.blog'),
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('subcategory')
                                ->label(__('lunarpanel::article.form.subcategory.label')),
                            Forms\Components\TagsInput::make('tags')
                                ->label(__('lunarpanel::article.form.tags.label')),
                            Forms\Components\DateTimePicker::make('published_at')
                                ->label(__('lunarpanel::article.form.published_at.label')),
                            Forms\Components\TextInput::make('source_url')
                                ->label(__('lunarpanel::article.form.source_url.label'))
                                ->url()
                                ->disabled(),
                            Forms\Components\Textarea::make('meta_description.nl')
                                ->label(__('lunarpanel::article.form.meta_description.label').' (NL)')
                                ->rows(2),
                            Forms\Components\Textarea::make('meta_description.en')
                                ->label(__('lunarpanel::article.form.meta_description.label').' (EN)')
                                ->rows(2),
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
                Tables\Columns\TextColumn::make('title.nl')
                    ->label(__('lunarpanel::article.table.title.label'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('lunarpanel::article.table.category.label'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'klantenservice' => 'info',
                        'blog' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('lunarpanel::article.table.status.label'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'rewritten' => 'warning',
                        'published' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('published_at')
                    ->label(__('lunarpanel::article.table.published_at.label'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('lunarpanel::article.table.updated_at.label'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => __('lunarpanel::article.form.status.options.draft'),
                        'rewritten' => __('lunarpanel::article.form.status.options.rewritten'),
                        'published' => __('lunarpanel::article.form.status.options.published'),
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'klantenservice' => __('lunarpanel::article.form.category.options.klantenservice'),
                        'blog' => __('lunarpanel::article.form.category.options.blog'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
