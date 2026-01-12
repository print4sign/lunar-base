@if($this->totalCount > 0)
<div class="mt-8 border-t pt-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ __('inspiration.product.reviews_title') }}</h3>
        <div class="flex items-center gap-2">
            <div class="flex items-center">
                @for ($i = 1; $i <= 5; $i++)
                    <svg class="w-5 h-5 {{ $i <= round($this->averageRating ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
            <span class="text-sm text-gray-600">
                {{ number_format($this->averageRating ?? 0, 1) }} ({{ $this->totalCount }} {{ __('inspiration.stats.reviews') }})
            </span>
        </div>
    </div>

    <!-- Gallery Grid -->
    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
        @foreach($this->inspirations as $inspiration)
            @php
                $photos = $inspiration->getMedia('inspiration_photos');
            @endphp
            @if($photos->count() > 0)
                <div class="group relative aspect-square rounded-lg overflow-hidden bg-gray-100 cursor-pointer">
                    <img
                        src="{{ $photos->first()->getUrl('gallery') }}"
                        alt="Klantfoto"
                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                    >
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity text-white text-center">
                            <div class="flex justify-center mb-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-3 h-3 {{ $i <= $inspiration->rating ? 'text-yellow-400' : 'text-white/50' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                            @if($photos->count() > 1)
                                <span class="text-xs">+{{ $photos->count() - 1 }} foto's</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
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
