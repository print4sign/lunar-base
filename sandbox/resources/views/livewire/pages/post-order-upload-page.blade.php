<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('upload.post_order.title') }}</h1>
            @if($this->orderReference)
                <p class="mt-2 text-gray-600">
                    {{ __('upload.post_order.order_reference', ['reference' => $this->orderReference]) }}
                </p>
            @endif
        </div>

        {{-- Invalid/Expired Token --}}
        @if(!$orderLine && !$isComplete)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                @if($isExpired)
                    <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ __('upload.post_order.link_expired') }}</h2>
                    <p class="text-gray-600 mb-6">{{ __('upload.post_order.link_expired_description') }}</p>
                @else
                    <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ __('upload.post_order.invalid_link') }}</h2>
                    <p class="text-gray-600 mb-6">{{ __('upload.post_order.invalid_link_description') }}</p>
                @endif
                <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700">
                    {{ __('upload.post_order.go_home') }}
                </a>
            </div>
        @elseif($isComplete)
            {{-- Already Completed --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ __('upload.post_order.already_complete') }}</h2>
                <p class="text-gray-600 mb-6">{{ __('upload.post_order.already_complete_description') }}</p>
                <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700">
                    {{ __('upload.post_order.go_home') }}
                </a>
            </div>
        @else
            {{-- Upload Form --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                {{-- Product Info --}}
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $productName }}</h2>
                    @if($orderLine->quantity > 1)
                        <p class="text-sm text-gray-600">{{ __('upload.post_order.quantity', ['qty' => $orderLine->quantity]) }}</p>
                    @endif
                </div>

                {{-- Upload Area --}}
                <div class="p-6">
                    @if($uploadSpec)
                        <div class="space-y-6">
                            @foreach($this->uploaders as $index => $uploader)
                                <div @class(['pt-6 border-t border-gray-200' => $index > 0])>
                                    @if(count($this->uploaders) > 1)
                                        <h4 class="text-sm font-medium text-gray-900 mb-4">
                                            {{ __('Upload Area :number', ['number' => $index + 1]) }}
                                        </h4>
                                    @endif
                                    {{-- Note: For order lines, we need a different uploader component
                                         that works with order_line_id instead of cart_line_id.
                                         For now, showing a simplified version --}}
                                    <livewire:components.custom-file-uploader
                                        :cart-line-id="0"
                                        :uploader-index="$index"
                                        :uploader-requirement="$uploader"
                                        :key="'uploader-' . $orderLine->id . '-' . $index"
                                    />
                                </div>
                            @endforeach
                        </div>

                        {{-- Status --}}
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">
                                    {{ __('upload.post_order.progress', [
                                        'uploaded' => $uploadStatus['uploaded'] ?? 0,
                                        'required' => $uploadStatus['required'] ?? 0,
                                    ]) }}
                                </span>
                                @if($uploadStatus['complete'] ?? false)
                                    <span class="inline-flex items-center gap-1 text-sm text-green-600 font-medium">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        {{ __('upload.post_order.ready') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">{{ __('upload.post_order.no_spec') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex justify-end">
                        <button
                            type="button"
                            wire:click="confirmUploads"
                            @disabled(!$this->canConfirm)
                            @class([
                                'px-6 py-3 font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors',
                                'bg-primary-600 text-white hover:bg-primary-700' => $this->canConfirm,
                                'bg-gray-300 text-gray-500 cursor-not-allowed' => !$this->canConfirm,
                            ])
                        >
                            {{ __('upload.post_order.confirm_uploads') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Help text --}}
            <p class="mt-4 text-sm text-gray-500 text-center">
                {{ __('upload.post_order.help_text') }}
            </p>
        @endif
    </div>
</div>
