<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Validate;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\Country;

class CheckoutPage extends Component
{
    public ?Cart $cart = null;

    public string $step = 'shipping';

    // Shipping Address
    #[Validate('required|string|max:255')]
    public string $shipping_first_name = '';

    #[Validate('required|string|max:255')]
    public string $shipping_last_name = '';

    #[Validate('required|email')]
    public string $shipping_email = '';

    #[Validate('nullable|string|max:50')]
    public string $shipping_phone = '';

    #[Validate('required|string|max:255')]
    public string $shipping_line_one = '';

    #[Validate('nullable|string|max:255')]
    public string $shipping_line_two = '';

    #[Validate('required|string|max:100')]
    public string $shipping_city = '';

    #[Validate('nullable|string|max:100')]
    public string $shipping_state = '';

    #[Validate('required|string|max:20')]
    public string $shipping_postcode = '';

    #[Validate('required|exists:lunar_countries,id')]
    public ?int $shipping_country_id = null;

    // Billing Address
    public bool $same_as_shipping = true;

    #[Validate('required_if:same_as_shipping,false|string|max:255')]
    public string $billing_first_name = '';

    #[Validate('required_if:same_as_shipping,false|string|max:255')]
    public string $billing_last_name = '';

    #[Validate('required_if:same_as_shipping,false|string|max:255')]
    public string $billing_line_one = '';

    #[Validate('nullable|string|max:255')]
    public string $billing_line_two = '';

    #[Validate('required_if:same_as_shipping,false|string|max:100')]
    public string $billing_city = '';

    #[Validate('nullable|string|max:100')]
    public string $billing_state = '';

    #[Validate('required_if:same_as_shipping,false|string|max:20')]
    public string $billing_postcode = '';

    #[Validate('required_if:same_as_shipping,false|exists:lunar_countries,id')]
    public ?int $billing_country_id = null;

    // Payment
    public string $payment_method = 'offline';

    public function mount(): void
    {
        $this->cart = CartSession::current()?->calculate();

        if (!$this->cart || $this->cart->lines->isEmpty()) {
            $this->redirect(route('products.index'));
            return;
        }

        // Pre-fill from existing cart addresses
        if ($shippingAddress = $this->cart->shippingAddress) {
            $this->shipping_first_name = $shippingAddress->first_name ?? '';
            $this->shipping_last_name = $shippingAddress->last_name ?? '';
            $this->shipping_email = $shippingAddress->contact_email ?? '';
            $this->shipping_phone = $shippingAddress->contact_phone ?? '';
            $this->shipping_line_one = $shippingAddress->line_one ?? '';
            $this->shipping_line_two = $shippingAddress->line_two ?? '';
            $this->shipping_city = $shippingAddress->city ?? '';
            $this->shipping_state = $shippingAddress->state ?? '';
            $this->shipping_postcode = $shippingAddress->postcode ?? '';
            $this->shipping_country_id = $shippingAddress->country_id;
        }

        // Set default country
        if (!$this->shipping_country_id) {
            $this->shipping_country_id = Country::where('iso2', 'NL')->first()?->id
                ?? Country::first()?->id;
        }

        $this->billing_country_id = $this->shipping_country_id;
    }

    public function saveShippingAddress(): void
    {
        $this->validate([
            'shipping_first_name' => 'required|string|max:255',
            'shipping_last_name' => 'required|string|max:255',
            'shipping_email' => 'required|email',
            'shipping_phone' => 'nullable|string|max:50',
            'shipping_line_one' => 'required|string|max:255',
            'shipping_line_two' => 'nullable|string|max:255',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postcode' => 'required|string|max:20',
            'shipping_country_id' => 'required|exists:lunar_countries,id',
        ]);

        $this->cart->setShippingAddress([
            'first_name' => $this->shipping_first_name,
            'last_name' => $this->shipping_last_name,
            'contact_email' => $this->shipping_email,
            'contact_phone' => $this->shipping_phone,
            'line_one' => $this->shipping_line_one,
            'line_two' => $this->shipping_line_two,
            'city' => $this->shipping_city,
            'state' => $this->shipping_state,
            'postcode' => $this->shipping_postcode,
            'country_id' => $this->shipping_country_id,
        ]);

        if ($this->same_as_shipping) {
            $this->cart->setBillingAddress([
                'first_name' => $this->shipping_first_name,
                'last_name' => $this->shipping_last_name,
                'contact_email' => $this->shipping_email,
                'contact_phone' => $this->shipping_phone,
                'line_one' => $this->shipping_line_one,
                'line_two' => $this->shipping_line_two,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postcode' => $this->shipping_postcode,
                'country_id' => $this->shipping_country_id,
            ]);
        }

        $this->cart = $this->cart->calculate();
        $this->step = 'billing';
    }

    public function saveBillingAddress(): void
    {
        if (!$this->same_as_shipping) {
            $this->validate([
                'billing_first_name' => 'required|string|max:255',
                'billing_last_name' => 'required|string|max:255',
                'billing_line_one' => 'required|string|max:255',
                'billing_line_two' => 'nullable|string|max:255',
                'billing_city' => 'required|string|max:100',
                'billing_state' => 'nullable|string|max:100',
                'billing_postcode' => 'required|string|max:20',
                'billing_country_id' => 'required|exists:lunar_countries,id',
            ]);

            $this->cart->setBillingAddress([
                'first_name' => $this->billing_first_name,
                'last_name' => $this->billing_last_name,
                'line_one' => $this->billing_line_one,
                'line_two' => $this->billing_line_two,
                'city' => $this->billing_city,
                'state' => $this->billing_state,
                'postcode' => $this->billing_postcode,
                'country_id' => $this->billing_country_id,
            ]);
        }

        $this->cart = $this->cart->calculate();
        $this->step = 'payment';
    }

    public function placeOrder(): void
    {
        $this->cart = $this->cart->calculate();

        $order = $this->cart->createOrder();

        // Mark order as placed (for offline payment)
        $order->update([
            'placed_at' => now(),
            'status' => 'payment-received',
        ]);

        // Clear the cart
        CartSession::forget();

        $this->redirect(route('checkout-success.view', ['order' => $order->id]));
    }

    public function goToStep(string $step): void
    {
        $this->step = $step;
    }

    public function render()
    {
        $countries = Country::orderBy('name')->get();

        return view('livewire.pages.checkout-page', [
            'countries' => $countries,
        ])->layout('layouts.storefront', ['title' => 'Checkout']);
    }
}
