<?php

namespace Lunar\Admin\Filament\Resources\ArticleResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\ArticleResource;
use Lunar\Admin\Support\Pages\BaseListRecords;

class ListArticles extends BaseListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
