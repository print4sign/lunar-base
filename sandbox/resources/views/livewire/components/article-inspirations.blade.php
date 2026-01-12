@if($this->inspirations->count() > 0)
<div class="my-8 p-6 bg-gray-50 rounded-xl">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('inspiration.product.reviews_title') }}</h3>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach($this->inspirations as $inspiration)
            <div class="bg-white rounded-lg border border-gray-200 p-4 flex gap-4">
                <!-- Photo -->
                @php
                    $photos = $inspiration->getMedia('inspiration_photos');
                @endphp
                @if($photos->count() > 0)
                    <div class="flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden bg-gray-100">
                        <img
                            src="{{ $photos->first()->getUrl('small') }}"
                            alt="Klantfoto"
                            class="w-full h-full object-cover"
                        >
                    </div>
                @endif

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <!-- Rating -->
                    <div class="flex items-center gap-1 mb-1">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= $inspiration->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                        <span class="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                            <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('inspiration.badge.verified') }}
                        </span>
                    </div>

                    <!-- Text -->
                    <p class="text-sm text-gray-600 line-clamp-2">{{ $inspiration->getText() }}</p>

                    <!-- Product -->
                    @if($inspiration->getProductName())
                        <p class="mt-1 text-xs text-gray-400">{{ $inspiration->getProductName() }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- View All Link -->
    <div class="mt-4 text-center">
        <a href="{{ localizedUrl('inspirations.index') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
            {{ __('inspiration.product.view_all') }} &rarr;
        </a>
    </div>
</div>
@endif
