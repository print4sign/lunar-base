<?php

namespace App\Livewire\Components;

use App\Livewire\Concerns\HasLocale;
use App\Models\SavedCart;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;

class SaveCartModal extends Component implements HasActions, HasForms
{
    use HasLocale;
    use InteractsWithActions;
    use InteractsWithForms;

    public bool $showSuccessModal = false;

    public function mount(): void
    {
        $this->initializeLocale(app()->getLocale());
    }

    #[On('open-save-cart-modal')]
    public function openSaveModal(): void
    {
        if (! Auth::check()) {
            Notification::make()
                ->title(__('checkout.save_for_later.login_required'))
                ->warning()
                ->send();

            return;
        }

        $cart = CartSession::current();
        if (! $cart || $cart->lines->isEmpty()) {
            Notification::make()
                ->title(__('checkout.save_for_later.empty_cart'))
                ->danger()
                ->send();

            return;
        }

        $this->mountAction('saveCart');
    }

    public function saveCartAction(): Action
    {
        return Action::make('saveCart')
            ->modalHeading(__('checkout.save_for_later.title'))
            ->modalDescription(__('checkout.save_for_later.description'))
            ->modalSubmitActionLabel(__('checkout.save_for_later.save'))
            ->form([
                TextInput::make('name')
                    ->label(__('checkout.save_for_later.reference'))
                    ->placeholder(__('checkout.save_for_later.placeholder'))
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data): void {
                $this->saveCart($data['name']);
            });
    }

    public function successAction(): Action
    {
        return Action::make('success')
            ->modalHeading(__('checkout.save_for_later.saved_title'))
            ->modalDescription(__('checkout.save_for_later.saved_description'))
            ->modalSubmitActionLabel(__('checkout.save_for_later.to_overview'))
            ->extraModalFooterActions([
                Action::make('goHome')
                    ->label(__('checkout.save_for_later.to_home'))
                    ->color('gray')
                    ->url(route('home', ['locale' => $this->locale])),
            ])
            ->action(function (): void {
                $this->redirect(route('dashboard.saved-carts', [
                    'locale' => $this->locale,
                    'dashboardSegment' => localeSegment('dashboard', $this->locale),
                    'savedCartsSegment' => localeSegment('saved-carts', $this->locale),
                ]));
            });
    }

    protected function saveCart(string $name): void
    {
        if (! Auth::check()) {
            return;
        }

        $currentCart = CartSession::current();

        if (! $currentCart || $currentCart->lines->isEmpty()) {
            Notification::make()
                ->title(__('checkout.save_for_later.empty_cart'))
                ->danger()
                ->send();

            return;
        }

        // Clone the cart for saving
        // Note: We don't set user_id on the saved cart to prevent it from being
        // picked up as the "active" cart by CartSessionManager::fetchOrCreate()
        $newCart = Cart::create([
            'user_id' => null,  // Intentionally null - SavedCart links cart to user
            'customer_id' => $currentCart->customer_id,
            'currency_id' => $currentCart->currency_id,
            'channel_id' => $currentCart->channel_id,
            'coupon_code' => $currentCart->coupon_code,
        ]);

        // Clone cart lines
        foreach ($currentCart->lines as $line) {
            $newCart->lines()->create([
                'purchasable_type' => $line->purchasable_type,
                'purchasable_id' => $line->purchasable_id,
                'quantity' => $line->quantity,
                'meta' => $line->meta,
            ]);
        }

        // Create saved cart record
        SavedCart::create([
            'user_id' => Auth::id(),
            'cart_id' => $newCart->id,
            'name' => $name,
        ]);

        // Delete the original cart lines first
        $currentCart->lines()->delete();

        // Forget the cart session and delete the cart from DB
        // This clears the session key and soft-deletes the cart
        CartSession::forget();

        // Dispatch event to update cart components across the page
        $this->dispatch('cart-updated');
        $this->dispatch('cart-cleared');

        // Show success modal
        $this->mountAction('success');
    }

    public function render()
    {
        return view('livewire.components.save-cart-modal');
    }
}
