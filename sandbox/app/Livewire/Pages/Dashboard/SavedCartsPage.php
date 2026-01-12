<?php

namespace App\Livewire\Pages\Dashboard;

use App\Models\SavedCart;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;

class SavedCartsPage extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public string $locale = 'nl';

    public ?int $pendingRestoreCartId = null;

    public function mount(string $locale, string $dashboardSegment, string $savedCartsSegment): void
    {
        $this->locale = $locale;
        app()->setLocale($locale);
    }

    #[Computed]
    public function savedCarts()
    {
        return auth()->user()->savedCarts()
            ->with(['cart.lines.purchasable.product', 'cart.currency'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function currentCart()
    {
        return CartSession::current()?->calculate();
    }

    public function saveCartAction(): Action
    {
        return Action::make('saveCart')
            ->label(__('dashboard.saved_carts.save_current'))
            ->icon('heroicon-o-bookmark')
            ->modalHeading(__('dashboard.saved_carts.save_cart_title'))
            ->modalSubmitActionLabel(__('dashboard.saved_carts.save'))
            ->form([
                TextInput::make('name')
                    ->label(__('dashboard.saved_carts.cart_name'))
                    ->placeholder(__('dashboard.saved_carts.cart_name_placeholder'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('dashboard.saved_carts.cart_description'))
                    ->placeholder(__('dashboard.saved_carts.cart_description_placeholder'))
                    ->maxLength(1000)
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                $currentCart = $this->currentCart;

                if (! $currentCart || $currentCart->lines->isEmpty()) {
                    Notification::make()
                        ->title(__('dashboard.saved_carts.empty_cart'))
                        ->danger()
                        ->send();

                    return;
                }

                $this->saveCartAsNew($currentCart, $data['name'], $data['description'] ?? null);

                Notification::make()
                    ->title(__('dashboard.saved_carts.saved_success'))
                    ->success()
                    ->send();
            })
            ->visible(fn () => $this->currentCart && $this->currentCart->lines->isNotEmpty());
    }

    public function restoreCartAction(): Action
    {
        return Action::make('restoreCart')
            ->label(__('dashboard.saved_carts.restore'))
            ->icon('heroicon-o-shopping-cart')
            ->modalHeading(__('dashboard.saved_carts.conflict.title'))
            ->modalDescription(__('dashboard.saved_carts.conflict.description'))
            ->modalContent(view('livewire.pages.dashboard.partials.cart-conflict-options'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('dashboard.saved_carts.conflict.continue'));
    }

    public function clearAndRestoreAction(): Action
    {
        return Action::make('clearAndRestore')
            ->requiresConfirmation()
            ->modalHeading(__('dashboard.saved_carts.conflict.clear_confirm_title'))
            ->modalDescription(__('dashboard.saved_carts.conflict.clear_confirm_description'))
            ->modalSubmitActionLabel(__('dashboard.saved_carts.conflict.clear_confirm_button'))
            ->modalCancelActionLabel(__('dashboard.saved_carts.conflict.clear_cancel_button'))
            ->color('danger')
            ->action(function (): void {
                if ($this->pendingRestoreCartId) {
                    $this->handleClearAndRestore($this->pendingRestoreCartId);
                }
            });
    }

    public function clearAndRestore(): void
    {
        $this->mountAction('clearAndRestore');
    }

    public function saveAndRestore(): void
    {
        if (! $this->pendingRestoreCartId) {
            return;
        }

        $this->handleSaveAndRestore($this->pendingRestoreCartId);
    }

    public function mergeAndRestore(): void
    {
        if (! $this->pendingRestoreCartId) {
            return;
        }

        $this->handleMergeAndRestore($this->pendingRestoreCartId);
    }

    public function restoreCart(int $savedCartId): void
    {
        $savedCart = auth()->user()->savedCarts()->with('cart.lines')->find($savedCartId);
        abort_unless($savedCart, 404);

        $currentCart = CartSession::current();

        // If no active cart or cart is empty, restore directly and redirect to checkout
        if (! $currentCart || $currentCart->lines->isEmpty()) {
            $this->performRestore($savedCartId);
            $this->redirectToCheckout();

            return;
        }

        // Store the cart ID for use in the modal actions
        $this->pendingRestoreCartId = $savedCartId;

        // Active cart with items exists - show conflict modal
        $this->mountAction('restoreCart');
    }

    protected function handleClearAndRestore(int $savedCartId): void
    {
        $currentCart = CartSession::current();
        if ($currentCart) {
            $currentCart->lines()->delete();
            CartSession::forget();
        }

        $this->performRestore($savedCartId);
        $this->redirectToCheckout();
    }

    protected function handleSaveAndRestore(int $savedCartId): void
    {
        $currentCart = CartSession::current();
        if ($currentCart && $currentCart->lines->isNotEmpty()) {
            $this->saveCartAsNew(
                $currentCart,
                __('dashboard.saved_carts.auto_saved_name', ['date' => now()->format('d-m-Y H:i')])
            );

            $currentCart->lines()->delete();
            CartSession::forget();
        }

        $this->performRestore($savedCartId);
        $this->redirectToCheckout();
    }

    protected function handleMergeAndRestore(int $savedCartId): void
    {
        $savedCart = auth()->user()->savedCarts()->with('cart.lines')->find($savedCartId);
        if (! $savedCart) {
            return;
        }

        $cart = CartSession::manager();

        foreach ($savedCart->cart->lines as $line) {
            if ($line->purchasable) {
                $meta = $line->meta instanceof \ArrayObject ? $line->meta->getArrayCopy() : ($line->meta ?? []);
                $cart->add($line->purchasable, $line->quantity, $meta);
            }
        }

        $this->dispatch('cart-updated');
        $this->redirectToCheckout();
    }

    protected function saveCartAsNew(Cart $sourceCart, string $name, ?string $description = null): SavedCart
    {
        $newCart = Cart::create([
            'user_id' => null,
            'customer_id' => $sourceCart->customer_id,
            'currency_id' => $sourceCart->currency_id,
            'channel_id' => $sourceCart->channel_id,
            'coupon_code' => $sourceCart->coupon_code,
        ]);

        foreach ($sourceCart->lines as $line) {
            $newCart->lines()->create([
                'purchasable_type' => $line->purchasable_type,
                'purchasable_id' => $line->purchasable_id,
                'quantity' => $line->quantity,
                'meta' => $line->meta,
            ]);
        }

        return SavedCart::create([
            'user_id' => auth()->id(),
            'cart_id' => $newCart->id,
            'name' => $name,
            'description' => $description,
        ]);
    }

    protected function performRestore(int $savedCartId): void
    {
        $savedCart = auth()->user()->savedCarts()->with('cart.lines')->find($savedCartId);
        if (! $savedCart) {
            return;
        }

        $cart = CartSession::manager();

        foreach ($savedCart->cart->lines as $line) {
            if ($line->purchasable) {
                $meta = $line->meta instanceof \ArrayObject ? $line->meta->getArrayCopy() : ($line->meta ?? []);
                $cart->add($line->purchasable, $line->quantity, $meta);
            }
        }

        $this->dispatch('cart-updated');
    }

    protected function redirectToCheckout(): void
    {
        $this->redirect(route('checkout.view', [
            'locale' => $this->locale,
            'checkoutSegment' => localeSegment('checkout', $this->locale),
        ]));
    }

    public function deleteCartAction(): Action
    {
        return Action::make('deleteCart')
            ->label(__('dashboard.saved_carts.delete'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('dashboard.saved_carts.confirm_delete'))
            ->action(function (array $arguments): void {
                $savedCart = auth()->user()->savedCarts()->find($arguments['savedCartId']);
                $savedCart?->delete();

                Notification::make()
                    ->title(__('dashboard.saved_carts.deleted_success'))
                    ->success()
                    ->send();
            });
    }

    public function deleteSavedCart(int $savedCartId): void
    {
        $this->mountAction('deleteCart', ['savedCartId' => $savedCartId]);
    }

    public function render()
    {
        return view('livewire.pages.dashboard.saved-carts-page')
            ->layout('layouts.storefront', ['title' => __('dashboard.saved_carts.title')]);
    }
}
