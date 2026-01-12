@props([
    'product' => null,
    'supplierProduct' => null,
    'images' => [],
    'name' => 'Product Name',
    'shortDescription' => '',
    'description' => '',
    'specifications' => [],
    'features' => [],
    'warranty' => '',
])

@php
    $productImages = $images ?: [
        ['url' => 'https://flowbite.s3.amazonaws.com/blocks/e-commerce/imac-front.svg', 'alt' => 'Product image'],
    ];
@endphp

<section class="py-8 bg-white md:py-16 xl:py-24 antialiased">
    <div class="max-w-screen-xl mx-auto">
        <div class="lg:flex justify-between">
            {{-- Left Column: Image Gallery & Accordion --}}
            <div class="px-4">
                <div class="max-w-md lg:max-w-none mx-auto flex flex-col lg:flex-row justify-center mb-4">
                    {{-- Thumbnail Navigation --}}
                    <ul class="grid grid-cols-4 lg:block gap-4 order-2 lg:order-1 lg:space-y-4 mt-8 lg:mt-0" id="product-gallery-tabs" data-tabs-toggle="#product-gallery-content" data-tabs-active-classes="border-primary-500" data-tabs-inactive-classes="border-transparent hover:border-gray-200" role="tablist">
                        @foreach($productImages as $index => $image)
                        <li class="me-2" role="presentation">
                            <button class="h-20 w-20 overflow-hidden border-2 rounded-lg sm:h-20 sm:w-20 md:h-24 md:w-24 p-2 cursor-pointer mx-auto" id="product-image-{{ $index }}-tab" data-tabs-target="#product-image-{{ $index }}" type="button" role="tab" aria-controls="product-image-{{ $index }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                <img class="object-contain w-full h-full" src="{{ $image['url'] ?? $image }}" alt="{{ $image['alt'] ?? 'Thumbnail ' . ($index + 1) }}" />
                            </button>
                        </li>
                        @endforeach
                    </ul>

                    {{-- Main Image Display --}}
                    <div id="product-gallery-content" class="order-1 lg:order-2">
                        @foreach($productImages as $index => $image)
                        <div class="{{ $index === 0 ? '' : 'hidden' }} px-4 rounded-lg bg-white" id="product-image-{{ $index }}" role="tabpanel" aria-labelledby="product-image-{{ $index }}-tab">
                            <img class="w-full max-w-lg mx-auto" src="{{ $image['url'] ?? $image }}" alt="{{ $image['alt'] ?? 'Product image ' . ($index + 1) }}" />
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Accordion: Product Details, Specifications, Warranty --}}
                <div id="product-accordion" data-accordion="collapse" data-active-classes="bg-white text-gray-900" data-inactive-classes="text-gray-500">
                    {{-- Product Details --}}
                    <h2 id="accordion-heading-details">
                        <button type="button" class="flex items-center justify-between w-full py-5 font-medium rtl:text-right text-gray-500 border-b border-gray-200 gap-3" data-accordion-target="#accordion-body-details" aria-expanded="true" aria-controls="accordion-body-details">
                            <span>Product Details</span>
                            <svg data-accordion-icon class="w-5 h-5 rotate-180 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>
                    </h2>
                    <div id="accordion-body-details" class="hidden" aria-labelledby="accordion-heading-details">
                        <div class="py-5 border-b border-gray-200">
                            @if($shortDescription)
                            <p class="mb-2 text-gray-500">{{ $shortDescription }}</p>
                            @endif
                            @if($description)
                            <div class="text-gray-500 prose prose-sm max-w-none">
                                {!! $description !!}
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Specifications --}}
                    <h2 id="accordion-heading-specs">
                        <button type="button" class="flex items-center justify-between w-full py-5 font-medium rtl:text-right text-gray-500 border-b border-gray-200 gap-3" data-accordion-target="#accordion-body-specs" aria-expanded="false" aria-controls="accordion-body-specs">
                            <span>Specifications</span>
                            <svg data-accordion-icon class="w-5 h-5 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>
                    </h2>
                    <div id="accordion-body-specs" class="hidden" aria-labelledby="accordion-heading-specs">
                        <div class="py-5 border-b border-gray-200">
                            @if(count($specifications) > 0)
                            <dl class="space-y-2">
                                @foreach($specifications as $spec)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">{{ $spec['name'] ?? $spec['label'] ?? '' }}</dt>
                                    <dd class="font-medium text-gray-900">{{ $spec['value'] ?? '' }}</dd>
                                </div>
                                @endforeach
                            </dl>
                            @else
                            <p class="text-gray-500">No specifications available.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Warranty --}}
                    <h2 id="accordion-heading-warranty">
                        <button type="button" class="flex items-center justify-between w-full py-5 font-medium rtl:text-right text-gray-500 border-b border-gray-200 gap-3" data-accordion-target="#accordion-body-warranty" aria-expanded="false" aria-controls="accordion-body-warranty">
                            <span>Warranty & Returns</span>
                            <svg data-accordion-icon class="w-5 h-5 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/>
                            </svg>
                        </button>
                    </h2>
                    <div id="accordion-body-warranty" class="hidden" aria-labelledby="accordion-heading-warranty">
                        <div class="py-5 border-b border-gray-200">
                            @if($warranty)
                            <p class="text-gray-500">{{ $warranty }}</p>
                            @else
                            <p class="mb-2 text-gray-500">This product comes with a standard warranty covering manufacturing defects and malfunctions.</p>
                            <p class="text-gray-500">Contact us for more information about returns and exchanges.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Product Info & Configuration --}}
            <div class="w-full mt-6 lg:max-w-lg lg:mt-0 shrink-0 px-4">
                <div class="p-4 border border-gray-200 rounded-lg sm:p-6 lg:p-8 bg-gray-50">
                    {{-- Product Title --}}
                    <h1 class="text-xl font-semibold text-gray-900">
                        {{ $name }}
                    </h1>

                    {{-- Rating & Reviews --}}
                    <div class="mt-4 sm:gap-4 sm:items-center sm:flex">
                        <div class="flex items-center gap-2 mt-4 sm:mt-0">
                            <div class="flex items-center gap-1">
                                @for($i = 0; $i < 5; $i++)
                                <svg class="w-4 h-4 text-yellow-300" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z"/>
                                </svg>
                                @endfor
                            </div>
                            <a href="#reviews" class="text-sm font-medium leading-none text-gray-900 underline hover:no-underline">
                                Reviews
                            </a>
                        </div>
                    </div>

                    {{-- Delivery Location --}}
                    <div class="flex items-center gap-1 mt-4">
                        <svg class="w-5 h-5 text-primary-700" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.8 13.938h-.011a7 7 0 1 0-11.464.144h-.016l.14.171c.1.127.2.251.3.371L12 21l5.13-6.248c.194-.209.374-.429.54-.659l.13-.155Z"/>
                        </svg>
                        <p class="text-sm font-medium text-primary-700">
                            Deliver to Netherlands
                        </p>
                    </div>

                    {{-- Probo Product Configurator --}}
                    @if($supplierProduct)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <livewire:probo-configurator
                            :supplier-product-id="$supplierProduct->id"
                            :product-variant-id="$product?->variants?->first()?->id"
                            :key="'configurator-'.$supplierProduct->id"
                        />
                    </div>
                    @else
                    {{-- Non-supplier product: show simple add to cart --}}
                    <div class="gap-4 mt-4 md:mt-6 flex items-center justify-between">
                        <p class="text-2xl font-extrabold text-gray-900 sm:text-3xl">
                            Price on request
                        </p>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="gap-4 mt-4 md:mt-6 sm:flex sm:items-center lg:flex-col">
                        <a href="#" class="flex items-center w-full justify-center py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-100" role="button">
                            <svg class="w-5 h-5 -ms-2 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.01 6.001C6.5 1 1 8 5.782 13.001L12.011 20l6.23-7C23 8 17.5 1 12.01 6.002Z"/>
                            </svg>
                            Add to favorites
                        </a>

                        <button type="button" class="text-white w-full mt-4 sm:mt-0 bg-primary-700 hover:bg-primary-800 focus:ring-4 focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none flex items-center justify-center">
                            <svg class="w-5 h-5 -ms-2 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h1.5L8 16m0 0h8m-8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm8 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm.75-3H7.5M11 7H6.312M17 4v6m-3-3h6"/>
                            </svg>
                            Request Quote
                        </button>
                    </div>
                    @endif

                    <p class="mt-4 md:mt-6 text-sm font-normal text-gray-500">
                        Free delivery within 5 business days. Contact us for
                        <a href="#" class="font-medium underline text-primary-700 hover:no-underline">express delivery</a>
                        options.
                    </p>

                    {{-- Features List --}}
                    @if(count($features) > 0)
                    <div class="pt-8 mt-8 border-t border-gray-200">
                        <p class="text-base font-medium text-gray-900 mb-4">Features</p>
                        <ul class="space-y-2">
                            @foreach($features as $feature)
                            <li class="flex items-center gap-2 text-sm text-gray-500">
                                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    {{-- Shipping Options --}}
                    <div class="pt-8 mt-8 border-t border-gray-200">
                        <p class="text-base font-medium text-gray-900 mb-4">Delivery options</p>
                        <div class="flex flex-col gap-4">
                            <div class="flex">
                                <div class="flex items-center h-5">
                                    <input id="shipping-standard" type="radio" value="standard" name="shipping" checked
                                        class="w-4 h-4 bg-white border-gray-300 rounded-full text-primary-600 focus:ring-primary-500 focus:ring-2" />
                                </div>
                                <div class="text-sm ms-2">
                                    <label for="shipping-standard" class="font-medium text-gray-900">
                                        Standard delivery - Free
                                    </label>
                                    <p class="text-xs font-normal text-gray-500">
                                        5-7 business days
                                    </p>
                                </div>
                            </div>

                            <div class="flex">
                                <div class="flex items-center h-5">
                                    <input id="shipping-express" type="radio" value="express" name="shipping"
                                        class="w-4 h-4 bg-white border-gray-300 rounded-full text-primary-600 focus:ring-primary-500 focus:ring-2" />
                                </div>
                                <div class="text-sm ms-2">
                                    <label for="shipping-express" class="font-medium text-gray-900">
                                        Express delivery - €19
                                    </label>
                                    <p class="text-xs font-normal text-gray-500">
                                        2-3 business days
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Product Description Section --}}
<section class="bg-white py-8 antialiased md:py-16">
    <div class="mx-auto max-w-screen-xl px-4 2xl:px-0">
        @if($description)
        <h2 class="mb-6 text-xl font-semibold text-gray-900 sm:mb-8 sm:text-2xl lg:mb-8">Product description</h2>
        <div class="mb-6 space-y-6 sm:mb-8 lg:mb-8">
            <div class="prose prose-lg max-w-none text-gray-500">
                {!! $description !!}
            </div>
        </div>
        @endif

        @if(count($specifications) > 0)
        <h2 class="mb-6 text-xl font-semibold text-gray-900 sm:mb-8 sm:text-2xl lg:mb-8">Technical details</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-gray-500">
                <thead>
                    <tr>
                        <th scope="col" class="min-w-72"><span class="sr-only">Specification</span></th>
                        <th scope="col" class="min-w-[36rem] whitespace-nowrap"><span class="sr-only">Value</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($specifications as $index => $spec)
                    <tr class="{{ $index % 2 === 0 ? 'bg-gray-50' : '' }}">
                        <th scope="row" class="p-4 font-medium text-gray-900">{{ $spec['name'] ?? $spec['label'] ?? '' }}</th>
                        <td class="p-4">{{ $spec['value'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</section>

