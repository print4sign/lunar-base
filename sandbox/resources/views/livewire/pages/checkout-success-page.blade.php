<div>
    <div class="max-w-3xl px-4 py-16 mx-auto text-center">
        <!-- Success Icon -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900 mb-6">
            <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Thank you for your order!</h1>
        <p class="text-lg text-gray-500 dark:text-gray-400 mb-2">Your order has been confirmed and will be processed shortly.</p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-8">Order reference: <span class="font-medium text-gray-900 dark:text-white">{{ $order->reference }}</span></p>

        <!-- Order Details -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-left mb-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Order Details</h2>

            <div class="space-y-4 mb-6">
                @foreach($order->lines as $line)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $line->description }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Qty: {{ $line->quantity }}</p>
                        </div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $line->sub_total?->formatted() }}</p>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                    <span class="text-gray-900 dark:text-white">{{ $order->sub_total?->formatted() }}</span>
                </div>
                @if($order->shipping_total?->value > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Shipping</span>
                        <span class="text-gray-900 dark:text-white">{{ $order->shipping_total?->formatted() }}</span>
                    </div>
                @endif
                @if($order->tax_total?->value > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Tax</span>
                        <span class="text-gray-900 dark:text-white">{{ $order->tax_total?->formatted() }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-lg font-semibold pt-2 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-gray-900 dark:text-white">Total</span>
                    <span class="text-gray-900 dark:text-white">{{ $order->total?->formatted() }}</span>
                </div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            @if($order->shippingAddress)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-left">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">Shipping Address</h3>
                    <p class="text-gray-900 dark:text-white">{{ $order->shippingAddress->first_name }} {{ $order->shippingAddress->last_name }}</p>
                    <p class="text-gray-600 dark:text-gray-300">{{ $order->shippingAddress->line_one }}</p>
                    @if($order->shippingAddress->line_two)
                        <p class="text-gray-600 dark:text-gray-300">{{ $order->shippingAddress->line_two }}</p>
                    @endif
                    <p class="text-gray-600 dark:text-gray-300">{{ $order->shippingAddress->city }}, {{ $order->shippingAddress->postcode }}</p>
                    <p class="text-gray-600 dark:text-gray-300">{{ $order->shippingAddress->country?->name }}</p>
                </div>
            @endif

            @if($order->billingAddress)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-left">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">Billing Address</h3>
                    <p class="text-gray-900 dark:text-white">{{ $order->billingAddress->first_name }} {{ $order->billingAddress->last_name }}</p>
                    <p class="text-gray-600 dark:text-gray-300">{{ $order->billingAddress->line_one }}</p>
                    @if($order->billingAddress->line_two)
                        <p class="text-gray-600 dark:text-gray-300">{{ $order->billingAddress->line_two }}</p>
                    @endif
                    <p class="text-gray-600 dark:text-gray-300">{{ $order->billingAddress->city }}, {{ $order->billingAddress->postcode }}</p>
                    <p class="text-gray-600 dark:text-gray-300">{{ $order->billingAddress->country?->name }}</p>
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
                Continue Shopping
            </a>
        </div>
    </div>
</div>
