<div>
    <x-dashboard.layout>
        {{-- Page Header --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('dashboard.addresses.title') }}</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('dashboard.addresses.subtitle') }}</p>
                </div>
                @if(!$showForm)
                    <button wire:click="createAddress"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('dashboard.addresses.add_new') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        {{-- Address Form --}}
        @if($showForm)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    {{ $editingAddressId ? __('dashboard.addresses.edit_address') : __('dashboard.addresses.new_address') }}
                </h2>

                <form wire:submit="saveAddress" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('dashboard.addresses.first_name') }} *
                            </label>
                            <input type="text" id="first_name" wire:model="first_name"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('first_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('dashboard.addresses.last_name') }} *
                            </label>
                            <input type="text" id="last_name" wire:model="last_name"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('last_name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('dashboard.addresses.company') }}
                        </label>
                        <input type="text" id="company_name" wire:model="company_name"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label for="line_one" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('dashboard.addresses.address_line_1') }} *
                        </label>
                        <input type="text" id="line_one" wire:model="line_one"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('line_one') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="line_two" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('dashboard.addresses.address_line_2') }}
                        </label>
                        <input type="text" id="line_two" wire:model="line_two"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="postcode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('dashboard.addresses.postcode') }} *
                            </label>
                            <input type="text" id="postcode" wire:model="postcode"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('postcode') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('dashboard.addresses.city') }} *
                            </label>
                            <input type="text" id="city" wire:model="city"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('city') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="country_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('dashboard.addresses.country') }} *
                            </label>
                            <select id="country_id" wire:model="country_id"
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @foreach($this->countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                            @error('country_id') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('dashboard.addresses.phone') }}
                        </label>
                        <input type="tel" id="contact_phone" wire:model="contact_phone"
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div class="flex gap-6">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="shipping_default"
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('dashboard.addresses.set_shipping_default') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="billing_default"
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('dashboard.addresses.set_billing_default') }}</span>
                        </label>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit"
                                class="px-6 py-2.5 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                            {{ $editingAddressId ? __('dashboard.addresses.update') : __('dashboard.addresses.save') }}
                        </button>
                        <button type="button" wire:click="cancelForm"
                                class="px-6 py-2.5 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            {{ __('dashboard.addresses.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Addresses List --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            @if($this->addresses->isNotEmpty())
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->addresses as $address)
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ $address->first_name }} {{ $address->last_name }}
                                        </p>
                                        @if($address->shipping_default)
                                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-400">
                                                {{ __('dashboard.addresses.shipping_default') }}
                                            </span>
                                        @endif
                                        @if($address->billing_default)
                                            <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400">
                                                {{ __('dashboard.addresses.billing_default') }}
                                            </span>
                                        @endif
                                    </div>
                                    <address class="text-sm text-gray-600 dark:text-gray-400 not-italic">
                                        @if($address->company_name)
                                            <p>{{ $address->company_name }}</p>
                                        @endif
                                        <p>{{ $address->line_one }}</p>
                                        @if($address->line_two)
                                            <p>{{ $address->line_two }}</p>
                                        @endif
                                        <p>{{ $address->postcode }} {{ $address->city }}</p>
                                        <p>{{ $address->country?->name }}</p>
                                        @if($address->contact_phone)
                                            <p class="mt-1">{{ $address->contact_phone }}</p>
                                        @endif
                                    </address>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if(!$address->shipping_default)
                                        <button wire:click="setDefaultShipping({{ $address->id }})"
                                                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                            {{ __('dashboard.addresses.set_as_shipping') }}
                                        </button>
                                    @endif
                                    @if(!$address->billing_default)
                                        <button wire:click="setDefaultBilling({{ $address->id }})"
                                                class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                            {{ __('dashboard.addresses.set_as_billing') }}
                                        </button>
                                    @endif
                                    <button wire:click="editAddress({{ $address->id }})"
                                            class="px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-100 rounded-lg hover:bg-primary-200 transition-colors dark:bg-primary-900/50 dark:text-primary-400 dark:hover:bg-primary-900">
                                        {{ __('dashboard.addresses.edit') }}
                                    </button>
                                    <button wire:click="deleteAddress({{ $address->id }})"
                                            wire:confirm="{{ __('dashboard.addresses.confirm_delete') }}"
                                            class="px-3 py-1.5 text-xs font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200 transition-colors dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900">
                                        {{ __('dashboard.addresses.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p>{{ __('dashboard.addresses.no_addresses') }}</p>
                    <button wire:click="createAddress"
                            class="inline-block mt-4 text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                        {{ __('dashboard.addresses.add_first') }}
                    </button>
                </div>
            @endif
        </div>
    </x-dashboard.layout>
</div>
