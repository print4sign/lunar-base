<div
    x-data="{ open: @entangle('open') }"
    x-show="open"
    x-cloak
    class="relative z-50"
    aria-labelledby="slide-over-title"
    role="dialog"
    aria-modal="true"
>
    <!-- Background backdrop -->
    <div
        x-show="open"
        x-transition:enter="ease-in-out duration-500"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in-out duration-500"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500/75 transition-opacity"
        @click="open = false"
    ></div>

    <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div
                    x-show="open"
                    x-transition:enter="transform transition ease-in-out duration-500"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in-out duration-500"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="pointer-events-auto w-screen max-w-md"
                >
                    <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-gray-800 shadow-xl">
                        <!-- Header -->
                        <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                            <div class="flex items-start justify-between">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white" id="slide-over-title">Shopping cart</h2>
                                <div class="ml-3 flex h-7 items-center">
                                    <button type="button" @click="open = false" class="relative -m-2 p-2 text-gray-400 hover:text-gray-500">
                                        <span class="absolute -inset-0.5"></span>
                                        <span class="sr-only">Close panel</span>
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Cart Items -->
                            <div class="mt-8">
                                <div class="flow-root">
                                    @if($cart && $cart->lines->count() > 0)
                                        <ul role="list" class="-my-6 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($cart->lines as $line)
                                                <li class="flex py-6" wire:key="cart-line-{{ $line->id }}">
                                                    <div class="h-24 w-24 flex-shrink-0 overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                                                        @if($line->purchasable->getThumbnail())
                                                            <img src="{{ $line->purchasable->getThumbnail()->getUrl('small') }}" alt="{{ $line->purchasable->getDescription() }}" class="h-full w-full object-cover object-center">
                                                        @else
                                                            <div class="h-full w-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                </svg>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="ml-4 flex flex-1 flex-col">
                                                        <div>
                                                            <div class="flex justify-between text-base font-medium text-gray-900 dark:text-white">
                                                                <h3>
                                                                    <a href="{{ route('product.view', $line->purchasable->product->defaultUrl?->slug ?? $line->purchasable->product->id) }}">
                                                                        {{ $line->purchasable->getDescription() }}
                                                                    </a>
                                                                </h3>
                                                                <p class="ml-4">{{ $line->subTotal?->formatted() }}</p>
                                                            </div>
                                                            @if($line->purchasable->sku)
                                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">SKU: {{ $line->purchasable->sku }}</p>
                                                            @endif
                                                        </div>
                                                        <div class="flex flex-1 items-end justify-between text-sm">
                                                            <div class="flex items-center">
                                                                <button
                                                                    wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity - 1 }})"
                                                                    class="px-2 py-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                                                >
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                                    </svg>
                                                                </button>
                                                                <span class="mx-2 text-gray-700 dark:text-gray-300">{{ $line->quantity }}</span>
                                                                <button
                                                                    wire:click="updateQuantity({{ $line->id }}, {{ $line->quantity + 1 }})"
                                                                    class="px-2 py-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                                                >
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>

                                                            <div class="flex">
                                                                <button
                                                                    type="button"
                                                                    wire:click="removeLine({{ $line->id }})"
                                                                    class="font-medium text-red-600 hover:text-red-500"
                                                                >
                                                                    Remove
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="text-center py-12">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Your cart is empty</h3>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start shopping to add items to your cart.</p>
                                            <div class="mt-6">
                                                <a href="{{ route('products.index') }}" @click="open = false" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700">
                                                    Browse Products
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        @if($cart && $cart->lines->count() > 0)
                            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-6 sm:px-6">
                                <div class="flex justify-between text-base font-medium text-gray-900 dark:text-white">
                                    <p>Subtotal</p>
                                    <p>{{ $cart->subTotal?->formatted() }}</p>
                                </div>
                                @if($cart->taxTotal?->value > 0)
                                    <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        <p>Tax</p>
                                        <p>{{ $cart->taxTotal?->formatted() }}</p>
                                    </div>
                                @endif
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Shipping calculated at checkout.</p>
                                <div class="mt-6">
                                    <a href="{{ route('checkout.view') }}" class="flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-primary-700">
                                        Checkout
                                    </a>
                                </div>
                                <div class="mt-6 flex justify-center text-center text-sm text-gray-500 dark:text-gray-400">
                                    <p>
                                        or
                                        <button type="button" @click="open = false" class="font-medium text-primary-600 hover:text-primary-500">
                                            Continue Shopping
                                            <span aria-hidden="true"> &rarr;</span>
                                        </button>
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
