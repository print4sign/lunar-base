<?php

namespace App\Livewire\Pages\Dashboard;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Lunar\Models\Address;
use Lunar\Models\Country;

class AddressesPage extends Component
{
    public string $locale = 'nl';

    public bool $showForm = false;

    public ?int $editingAddressId = null;

    #[Validate('required|string|max:255')]
    public string $first_name = '';

    #[Validate('required|string|max:255')]
    public string $last_name = '';

    #[Validate('nullable|string|max:255')]
    public string $company_name = '';

    #[Validate('required|string|max:255')]
    public string $line_one = '';

    #[Validate('nullable|string|max:255')]
    public string $line_two = '';

    #[Validate('required|string|max:100')]
    public string $city = '';

    #[Validate('required|string|max:20')]
    public string $postcode = '';

    #[Validate('required|exists:lunar_countries,id')]
    public ?int $country_id = null;

    #[Validate('nullable|string|max:50')]
    public string $contact_phone = '';

    public bool $shipping_default = false;

    public bool $billing_default = false;

    public function mount(string $locale, string $dashboardSegment, string $addressesSegment): void
    {
        $this->locale = $locale;
        app()->setLocale($locale);
        $this->country_id = Country::where('iso2', 'NL')->first()?->id;
    }

    #[Computed]
    public function customer()
    {
        return auth()->user()->latestCustomer();
    }

    #[Computed]
    public function addresses()
    {
        return $this->customer?->addresses()
            ->with('country')
            ->orderByDesc('shipping_default')
            ->orderByDesc('billing_default')
            ->orderByDesc('last_used_at')
            ->get() ?? collect();
    }

    #[Computed]
    public function countries()
    {
        return Country::orderBy('name')->get();
    }

    public function createAddress(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editAddress(int $id): void
    {
        $address = $this->customer?->addresses()->find($id);
        abort_unless($address, 404);

        $this->editingAddressId = $id;
        $this->first_name = $address->first_name;
        $this->last_name = $address->last_name;
        $this->company_name = $address->company_name ?? '';
        $this->line_one = $address->line_one;
        $this->line_two = $address->line_two ?? '';
        $this->city = $address->city;
        $this->postcode = $address->postcode;
        $this->country_id = $address->country_id;
        $this->contact_phone = $address->contact_phone ?? '';
        $this->shipping_default = $address->shipping_default;
        $this->billing_default = $address->billing_default;
        $this->showForm = true;
    }

    public function saveAddress(): void
    {
        $this->validate();

        $data = [
            'customer_id' => $this->customer->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company_name' => $this->company_name ?: null,
            'line_one' => $this->line_one,
            'line_two' => $this->line_two ?: null,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'country_id' => $this->country_id,
            'contact_phone' => $this->contact_phone ?: null,
            'shipping_default' => $this->shipping_default,
            'billing_default' => $this->billing_default,
        ];

        // Clear other defaults if this is being set as default
        if ($this->shipping_default) {
            $this->customer->addresses()->update(['shipping_default' => false]);
        }
        if ($this->billing_default) {
            $this->customer->addresses()->update(['billing_default' => false]);
        }

        if ($this->editingAddressId) {
            $this->customer->addresses()->find($this->editingAddressId)->update($data);
            session()->flash('success', __('dashboard.addresses.updated_success'));
        } else {
            Address::create($data);
            session()->flash('success', __('dashboard.addresses.saved_success'));
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function deleteAddress(int $id): void
    {
        $this->customer?->addresses()->find($id)?->delete();
        session()->flash('success', __('dashboard.addresses.deleted_success'));
    }

    public function setDefaultShipping(int $id): void
    {
        $this->customer->addresses()->update(['shipping_default' => false]);
        $this->customer->addresses()->find($id)->update(['shipping_default' => true]);
    }

    public function setDefaultBilling(int $id): void
    {
        $this->customer->addresses()->update(['billing_default' => false]);
        $this->customer->addresses()->find($id)->update(['billing_default' => true]);
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingAddressId = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->company_name = '';
        $this->line_one = '';
        $this->line_two = '';
        $this->city = '';
        $this->postcode = '';
        $this->country_id = Country::where('iso2', 'NL')->first()?->id;
        $this->contact_phone = '';
        $this->shipping_default = false;
        $this->billing_default = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.pages.dashboard.addresses-page')
            ->layout('layouts.storefront', ['title' => __('dashboard.addresses.title')]);
    }
}
