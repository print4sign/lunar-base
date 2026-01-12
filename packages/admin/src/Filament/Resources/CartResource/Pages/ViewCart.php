<?php

namespace Lunar\Admin\Filament\Resources\CartResource\Pages;

use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;
use Lunar\Admin\Filament\Resources\CartResource;
use Lunar\Admin\Filament\Resources\CustomerResource;
use Lunar\Admin\Support\Pages\BaseViewRecord;
use Lunar\Models\Cart;

/**
 * @property Cart $record
 */
class ViewCart extends BaseViewRecord
{
    protected static string $resource = CartResource::class;

    protected ?string $maxContentWidth = 'screen-2xl';

    public function getBreadcrumb(): string
    {
        return __('lunarpanel::cart.breadcrumb.view');
    }

    public function getTitle(): string|Htmlable
    {
        return __('lunarpanel::cart.title', ['id' => $this->record->id]);
    }

    public function getDefaultInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()
                    ->schema([
                        $this->getCartLinesSection(),
                        $this->getCartMetaSection(),
                    ])
                    ->columnSpan(['lg' => 2]),
                Infolists\Components\Group::make()
                    ->schema([
                        $this->getCartSummarySection(),
                        $this->getCustomerSection(),
                        $this->getAddressesSection(),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    protected function getCartSummarySection(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make(__('lunarpanel::cart.infolist.summary.label'))
            ->compact()
            ->schema([
                Infolists\Components\TextEntry::make('id')
                    ->label(__('lunarpanel::cart.infolist.id.label')),

                Infolists\Components\TextEntry::make('status')
                    ->label(__('lunarpanel::cart.infolist.status.label'))
                    ->badge()
                    ->getStateUsing(function () {
                        if ($this->record->completedOrders()->exists()) {
                            return __('lunarpanel::cart.status.completed');
                        }

                        $minutesAgo = $this->record->updated_at->diffInMinutes(now());

                        if ($minutesAgo <= 15) {
                            return __('lunarpanel::cart.status.active');
                        } elseif ($minutesAgo <= 60) {
                            return __('lunarpanel::cart.status.idle');
                        } elseif ($minutesAgo <= 1440) {
                            return __('lunarpanel::cart.status.inactive');
                        }

                        return __('lunarpanel::cart.status.abandoned');
                    })
                    ->color(fn (string $state): string => match ($state) {
                        __('lunarpanel::cart.status.completed') => 'success',
                        __('lunarpanel::cart.status.active') => 'info',
                        __('lunarpanel::cart.status.idle') => 'warning',
                        __('lunarpanel::cart.status.inactive') => 'gray',
                        default => 'danger',
                    }),

                Infolists\Components\TextEntry::make('currency.code')
                    ->label(__('lunarpanel::cart.infolist.currency.label')),

                Infolists\Components\TextEntry::make('coupon_code')
                    ->label(__('lunarpanel::cart.infolist.coupon.label'))
                    ->default('-'),

                Infolists\Components\TextEntry::make('created_at')
                    ->label(__('lunarpanel::cart.infolist.created_at.label'))
                    ->dateTime(),

                Infolists\Components\TextEntry::make('updated_at')
                    ->label(__('lunarpanel::cart.infolist.updated_at.label'))
                    ->dateTime()
                    ->since(),
            ]);
    }

    protected function getCustomerSection(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make(__('lunarpanel::cart.infolist.customer.label'))
            ->compact()
            ->schema([
                Infolists\Components\TextEntry::make('user.name')
                    ->label(__('lunarpanel::cart.infolist.customer_name.label'))
                    ->default(__('lunarpanel::cart.guest'))
                    ->weight(FontWeight::SemiBold)
                    ->size(TextEntrySize::Large),

                Infolists\Components\TextEntry::make('user.email')
                    ->label(__('lunarpanel::cart.infolist.customer_email.label'))
                    ->default('-')
                    ->copyable(),

                Infolists\Components\TextEntry::make('customer.fullName')
                    ->label(__('lunarpanel::cart.infolist.customer_account.label'))
                    ->visible(fn () => $this->record->customer_id)
                    ->suffixAction(
                        Infolists\Components\Actions\Action::make('view_customer')
                            ->icon('heroicon-o-arrow-top-right-on-square')
                            ->url(fn () => $this->record->customer_id
                                ? CustomerResource::getUrl('edit', ['record' => $this->record->customer_id])
                                : null)
                    ),
            ]);
    }

    protected function getAddressesSection(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make(__('lunarpanel::cart.infolist.addresses.label'))
            ->compact()
            ->visible(fn () => $this->record->addresses->isNotEmpty())
            ->schema([
                Infolists\Components\RepeatableEntry::make('addresses')
                    ->hiddenLabel()
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label(__('lunarpanel::cart.infolist.address_type.label'))
                            ->badge(),

                        Infolists\Components\TextEntry::make('full_name')
                            ->label(__('lunarpanel::cart.infolist.address_name.label'))
                            ->getStateUsing(fn ($record) => trim($record->first_name.' '.$record->last_name)),

                        Infolists\Components\TextEntry::make('line_one')
                            ->label(__('lunarpanel::cart.infolist.address_line.label')),

                        Infolists\Components\TextEntry::make('city')
                            ->label(__('lunarpanel::cart.infolist.address_city.label')),

                        Infolists\Components\TextEntry::make('postcode')
                            ->label(__('lunarpanel::cart.infolist.address_postcode.label')),

                        Infolists\Components\TextEntry::make('country.name')
                            ->label(__('lunarpanel::cart.infolist.address_country.label')),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getCartLinesSection(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make(__('lunarpanel::cart.infolist.lines.label'))
            ->schema([
                Infolists\Components\RepeatableEntry::make('lines')
                    ->hiddenLabel()
                    ->schema([
                        Infolists\Components\ImageEntry::make('purchasable.product.thumbnail')
                            ->hiddenLabel()
                            ->getStateUsing(fn ($record) => $record->purchasable?->product?->thumbnail?->getUrl('small'))
                            ->circular()
                            ->size(50),

                        Infolists\Components\TextEntry::make('purchasable.product.name')
                            ->label(__('lunarpanel::cart.infolist.line_product.label'))
                            ->weight(FontWeight::SemiBold)
                            ->getStateUsing(fn ($record) => $record->purchasable?->product?->translateAttribute('name') ?? 'Unknown Product'),

                        Infolists\Components\TextEntry::make('purchasable.sku')
                            ->label(__('lunarpanel::cart.infolist.line_sku.label'))
                            ->default('-'),

                        Infolists\Components\TextEntry::make('quantity')
                            ->label(__('lunarpanel::cart.infolist.line_quantity.label')),

                        Infolists\Components\TextEntry::make('meta')
                            ->label(__('lunarpanel::cart.infolist.line_meta.label'))
                            ->getStateUsing(function ($record) {
                                if (empty($record->meta)) {
                                    return '-';
                                }

                                return collect($record->meta)
                                    ->map(fn ($value, $key) => is_array($value) ? "$key: ".json_encode($value) : "$key: $value")
                                    ->join(', ');
                            }),
                    ])
                    ->columns(5),
            ]);
    }

    protected function getCartMetaSection(): Infolists\Components\Section
    {
        return Infolists\Components\Section::make(__('lunarpanel::cart.infolist.meta.label'))
            ->compact()
            ->visible(fn () => ! empty($this->record->meta))
            ->schema(fn () => collect($this->record->meta ?? [])
                ->map(function ($value, $key) {
                    if (is_array($value)) {
                        return Infolists\Components\KeyValueEntry::make('meta_'.$key)
                            ->label($key)
                            ->state($value);
                    }

                    return Infolists\Components\TextEntry::make('meta_'.$key)
                        ->label($key)
                        ->state($value)
                        ->copyable();
                })
                ->toArray());
    }

    #[Computed]
    public function calculatedCart(): Cart
    {
        return $this->record->calculate();
    }

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\Action::make('view_order')
                ->label(__('lunarpanel::cart.action.view_order.label'))
                ->icon('heroicon-o-document-text')
                ->url(fn () => $this->record->completedOrders()->first()
                    ? \Lunar\Admin\Filament\Resources\OrderResource::getUrl('order', ['record' => $this->record->completedOrders()->first()])
                    : null)
                ->visible(fn () => $this->record->completedOrders()->exists()),

            Actions\Action::make('delete')
                ->label(__('lunarpanel::cart.action.delete.label'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->lines()->delete();
                    $this->record->delete();

                    return redirect(CartResource::getUrl('index'));
                })
                ->visible(fn () => ! $this->record->completedOrders()->exists()),
        ];
    }
}
