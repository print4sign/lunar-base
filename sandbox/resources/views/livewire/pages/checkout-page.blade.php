<div>
    <div class="max-w-screen-xl px-4 py-8 mx-auto lg:py-16">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Checkout</h1>

        <div class="lg:grid lg:grid-cols-12 lg:gap-12">
            <!-- Checkout Form -->
            <div class="lg:col-span-7">
                <!-- Progress Steps -->
                <ol class="flex items-center w-full mb-8 text-sm font-medium text-center text-gray-500 dark:text-gray-400 sm:text-base">
                    <li @class([
                        'flex md:w-full items-center sm:after:content-[\'\'] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-4 xl:after:mx-6 dark:after:border-gray-700',
                        'text-primary-600 dark:text-primary-500' => $step === 'shipping',
                    ])>
                        <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            @if(in_array($step, ['billing', 'payment']))
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 me-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            @else
                                <span class="me-2">1</span>
                            @endif
                            Shipping
                        </span>
                    </li>
                    <li @class([
                        'flex md:w-full items-center after:content-[\'\'] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-4 xl:after:mx-6 dark:after:border-gray-700',
                        'text-primary-600 dark:text-primary-500' => $step === 'billing',
                    ])>
                        <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            @if($step === 'payment')
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 me-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            @else
                                <span class="me-2">2</span>
                            @endif
                            Billing
                        </span>
                    </li>
                    <li @class([
                        'flex items-center',
                        'text-primary-600 dark:text-primary-500' => $step === 'payment',
                    ])>
                        <span class="me-2">3</span>
                        Payment
                    </li>
                </ol>

                <!-- Shipping Step -->
                @if($step === 'shipping')
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Shipping Information</h2>

                        <form wire:submit="saveShippingAddress" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="shipping_first_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">First Name *</label>
                                    <input type="text" id="shipping_first_name" wire:model="shipping_first_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('shipping_first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="shipping_last_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last Name *</label>
                                    <input type="text" id="shipping_last_name" wire:model="shipping_last_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('shipping_last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="shipping_email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email *</label>
                                    <input type="email" id="shipping_email" wire:model="shipping_email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('shipping_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="shipping_phone" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Phone</label>
                                    <input type="tel" id="shipping_phone" wire:model="shipping_phone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('shipping_phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div>
                                <label for="shipping_line_one" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Address Line 1 *</label>
                                <input type="text" id="shipping_line_one" wire:model="shipping_line_one" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                @error('shipping_line_one') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="shipping_line_two" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Address Line 2</label>
                                <input type="text" id="shipping_line_two" wire:model="shipping_line_two" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="shipping_city" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">City *</label>
                                    <input type="text" id="shipping_city" wire:model="shipping_city" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('shipping_city') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="shipping_state" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">State/Province</label>
                                    <input type="text" id="shipping_state" wire:model="shipping_state" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="shipping_postcode" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Postal Code *</label>
                                    <input type="text" id="shipping_postcode" wire:model="shipping_postcode" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('shipping_postcode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="shipping_country_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Country *</label>
                                    <select id="shipping_country_id" wire:model="shipping_country_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('shipping_country_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <button type="submit" class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-3 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                                Continue to Billing
                            </button>
                        </form>
                    </div>
                @endif

                <!-- Billing Step -->
                @if($step === 'billing')
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Billing Information</h2>

                        <form wire:submit="saveBillingAddress" class="space-y-4">
                            <div class="flex items-center mb-4">
                                <input type="checkbox" id="same_as_shipping" wire:model.live="same_as_shipping" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="same_as_shipping" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">Same as shipping address</label>
                            </div>

                            @if(!$same_as_shipping)
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="billing_first_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">First Name *</label>
                                        <input type="text" id="billing_first_name" wire:model="billing_first_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('billing_first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="billing_last_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last Name *</label>
                                        <input type="text" id="billing_last_name" wire:model="billing_last_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('billing_last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="billing_line_one" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Address Line 1 *</label>
                                    <input type="text" id="billing_line_one" wire:model="billing_line_one" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('billing_line_one') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label for="billing_line_two" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Address Line 2</label>
                                    <input type="text" id="billing_line_two" wire:model="billing_line_two" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="billing_city" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">City *</label>
                                        <input type="text" id="billing_city" wire:model="billing_city" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('billing_city') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="billing_state" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">State/Province</label>
                                        <input type="text" id="billing_state" wire:model="billing_state" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="billing_postcode" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Postal Code *</label>
                                        <input type="text" id="billing_postcode" wire:model="billing_postcode" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        @error('billing_postcode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label for="billing_country_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Country *</label>
                                        <select id="billing_country_id" wire:model="billing_country_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            @foreach($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('billing_country_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="flex gap-4">
                                <button type="button" wire:click="goToStep('shipping')" class="flex-1 text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-3 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
                                    Back
                                </button>
                                <button type="submit" class="flex-1 text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-3 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                                    Continue to Payment
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                <!-- Payment Step -->
                @if($step === 'payment')
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Payment</h2>

                        <div class="space-y-4">
                            <div class="flex items-center p-4 border border-gray-200 rounded-lg dark:border-gray-700">
                                <input checked id="payment-offline" type="radio" wire:model="payment_method" value="offline" name="payment_method" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="payment-offline" class="w-full ms-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Pay on Delivery / Invoice</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">Pay when you receive your order or via invoice</div>
                                </label>
                            </div>

                            <div class="flex gap-4 mt-6">
                                <button type="button" wire:click="goToStep('billing')" class="flex-1 text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-3 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
                                    Back
                                </button>
                                <button type="button" wire:click="placeOrder" wire:loading.attr="disabled" class="flex-1 text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-3 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="placeOrder">Place Order</span>
                                    <span wire:loading wire:target="placeOrder">Processing...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-5 mt-8 lg:mt-0">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Order Summary</h2>

                    <div class="space-y-4 mb-6">
                        @foreach($cart->lines as $line)
                            <div class="flex gap-4">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
                                    @if($line->purchasable->getThumbnail())
                                        <img src="{{ $line->purchasable->getThumbnail()->getUrl('small') }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $line->purchasable->getDescription() }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Qty: {{ $line->quantity }}</p>
                                </div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $line->subTotal?->formatted() }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                            <span class="text-gray-900 dark:text-white">{{ $cart->subTotal?->formatted() }}</span>
                        </div>
                        @if($cart->shippingTotal?->value > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                                <span class="text-gray-900 dark:text-white">{{ $cart->shippingTotal?->formatted() }}</span>
                            </div>
                        @endif
                        @if($cart->taxTotal?->value > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Tax</span>
                                <span class="text-gray-900 dark:text-white">{{ $cart->taxTotal?->formatted() }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-base font-semibold pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-gray-900 dark:text-white">Total</span>
                            <span class="text-gray-900 dark:text-white">{{ $cart->total?->formatted() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
