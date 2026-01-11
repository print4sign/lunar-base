<?php

namespace Lunar\Admin\Filament\Resources\ArticleResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Lunar\Admin\Filament\Resources\ArticleResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;

class EditArticle extends BaseEditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\Action::make('rewrite')
                ->label(__('lunarpanel::article.actions.rewrite.label'))
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('lunarpanel::article.actions.rewrite.modal_heading'))
                ->modalDescription(__('lunarpanel::article.actions.rewrite.modal_description'))
                ->action(function () {
                    try {
                        $rewriter = app(\Lunar\Scraper\Services\ArticleRewriterService::class);
                        $rewriter->rewrite($this->record);
                        $this->record->update(['status' => 'rewritten']);

                        Notification::make()
                            ->title(__('lunarpanel::article.actions.rewrite.success'))
                            ->success()
                            ->send();

                        $this->refreshFormData(['body', 'status']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('lunarpanel::article.actions.rewrite.error'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status === 'draft'),
            Actions\DeleteAction::make(),
        ];
    }
}
