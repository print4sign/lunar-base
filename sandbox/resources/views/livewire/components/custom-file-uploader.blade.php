<div class="space-y-4">
    {{-- Requirements info --}}
    <div class="bg-gray-50 rounded-lg p-4 text-sm">
        <h4 class="font-medium text-gray-900 mb-2">{{ __('Upload requirements') }}</h4>
        <ul class="space-y-1 text-gray-600">
            @if($requirement->width && $requirement->height)
                <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ __('Dimensions: :width x :height mm', ['width' => $requirement->width, 'height' => $requirement->height]) }}
                </li>
            @endif
            @if($requirement->minimalDpi)
                <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('Minimum DPI: :dpi', ['dpi' => $requirement->minimalDpi]) }}
                </li>
            @endif
            @if($requirement->fileLimit)
                <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    {{ __('Max file size: :size MB', ['size' => $requirement->fileLimit]) }}
                </li>
            @endif
            @if($requirement->type === 'frontback')
                <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    {{ __('Double-sided print (front & back)') }}
                </li>
            @endif
        </ul>
    </div>

    {{-- Status bar --}}
    <div class="flex items-center justify-between text-sm">
        <span class="text-gray-600">
            {{ __(':uploaded of :required files uploaded', ['uploaded' => $status['uploaded'], 'required' => $status['required']]) }}
        </span>
        @if($status['complete'])
            <span class="inline-flex items-center gap-1 text-green-600">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                {{ __('Complete') }}
            </span>
        @endif
    </div>

    {{-- File slots --}}
    <div class="grid gap-4 {{ $requirement->getRequiredFileCount() > 1 ? 'sm:grid-cols-2' : '' }}">
        @for($fileIndex = 0; $fileIndex < $requirement->getRequiredFileCount(); $fileIndex++)
            <div class="border rounded-lg {{ isset($uploadedAssets[$fileIndex]) ? 'border-green-200 bg-green-50' : 'border-gray-200' }}">
                {{-- Slot header --}}
                <div class="px-4 py-2 border-b {{ isset($uploadedAssets[$fileIndex]) ? 'border-green-200 bg-green-100' : 'border-gray-200 bg-gray-50' }}">
                    <span class="font-medium text-sm {{ isset($uploadedAssets[$fileIndex]) ? 'text-green-800' : 'text-gray-700' }}">
                        {{ $this->getSlotLabel($fileIndex) }}
                    </span>
                </div>

                <div class="p-4">
                    @if(isset($uploadedAssets[$fileIndex]))
                        {{-- Uploaded file preview --}}
                        <div class="space-y-3">
                            {{-- Image preview --}}
                            @if($uploadedAssets[$fileIndex]['is_image'] && $uploadedAssets[$fileIndex]['url'])
                                <div class="aspect-[4/3] rounded-lg overflow-hidden bg-gray-100">
                                    <img
                                        src="{{ $uploadedAssets[$fileIndex]['url'] }}"
                                        alt="{{ $uploadedAssets[$fileIndex]['original_filename'] }}"
                                        class="w-full h-full object-contain"
                                    />
                                </div>
                            @elseif($uploadedAssets[$fileIndex]['is_pdf'])
                                <div class="aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10.92,12.31C10.68,11.54 10.15,9.08 11.55,9.04C12.95,9 12.03,12.16 12.03,12.16C12.42,13.65 14.05,14.72 14.05,14.72C14.55,14.57 17.4,14.24 17,15.72C16.57,17.2 13.5,15.81 13.5,15.81C11.55,15.95 10.09,16.47 10.09,16.47C8.96,18.58 7.64,19.5 7.1,18.61C6.43,17.5 9.23,16.07 9.23,16.07C10.68,13.72 10.9,12.35 10.92,12.31Z" />
                                    </svg>
                                </div>
                            @else
                                <div class="aspect-[4/3] rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            @endif

                            {{-- File info --}}
                            <div class="space-y-1 text-sm">
                                <p class="font-medium text-gray-900 truncate" title="{{ $uploadedAssets[$fileIndex]['original_filename'] }}">
                                    {{ $uploadedAssets[$fileIndex]['original_filename'] }}
                                </p>
                                <p class="text-gray-500">
                                    {{ $uploadedAssets[$fileIndex]['human_size'] }}
                                    @if($uploadedAssets[$fileIndex]['width'] && $uploadedAssets[$fileIndex]['height'])
                                        &middot; {{ $uploadedAssets[$fileIndex]['width'] }}x{{ $uploadedAssets[$fileIndex]['height'] }}px
                                    @endif
                                    @if($uploadedAssets[$fileIndex]['dpi'])
                                        &middot; {{ $uploadedAssets[$fileIndex]['dpi'] }} DPI
                                    @endif
                                </p>
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex gap-2" x-data="{ uploading: false }">
                                {{-- Change file button --}}
                                <label
                                    class="flex-1 relative cursor-pointer"
                                    :class="{ 'opacity-50 pointer-events-none': uploading }"
                                >
                                    <input
                                        type="file"
                                        x-ref="changeInput{{ $fileIndex }}"
                                        accept="image/jpeg,image/png,image/tiff,application/pdf"
                                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        @change="
                                            uploading = true;
                                            $wire.upload('files.{{ $fileIndex }}', $event.target.files[0], () => {
                                                uploading = false;
                                                $refs.changeInput{{ $fileIndex }}.value = '';
                                            }, () => {
                                                uploading = false;
                                            });
                                        "
                                    />
                                    <span class="block w-full px-3 py-2 text-sm font-medium text-center text-primary-600 bg-white border border-primary-200 rounded-lg hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                                        <template x-if="!uploading">
                                            <span>{{ __('checkout.cart.change_files') }}</span>
                                        </template>
                                        <template x-if="uploading">
                                            <span class="inline-flex items-center">
                                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                {{ __('Uploading...') }}
                                            </span>
                                        </template>
                                    </span>
                                </label>

                                {{-- Remove button --}}
                                <button
                                    type="button"
                                    wire:click="removeFile({{ $fileIndex }})"
                                    :disabled="uploading"
                                    :class="{ 'opacity-50': uploading }"
                                    class="px-3 py-2 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    title="{{ __('Remove file') }}"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- Upload dropzone --}}
                        <div
                            x-data="{ isDragging: false }"
                            x-on:dragover.prevent="isDragging = true"
                            x-on:dragleave.prevent="isDragging = false"
                            x-on:drop.prevent="isDragging = false; $refs.fileInput{{ $fileIndex }}.files = $event.dataTransfer.files; $refs.fileInput{{ $fileIndex }}.dispatchEvent(new Event('change'))"
                            :class="isDragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 hover:border-gray-400'"
                            class="relative border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors"
                        >
                            <input
                                x-ref="fileInput{{ $fileIndex }}"
                                type="file"
                                wire:model="files.{{ $fileIndex }}"
                                accept="image/jpeg,image/png,image/tiff,application/pdf"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            />

                            {{-- Upload icon and text --}}
                            <div wire:loading.remove wire:target="files.{{ $fileIndex }}">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">
                                    <span class="font-medium text-primary-600">{{ __('Click to upload') }}</span>
                                    {{ __('or drag and drop') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ __('PNG, JPG, TIFF or PDF up to :size MB', ['size' => $requirement->fileLimit]) }}
                                </p>
                            </div>

                            {{-- Loading state --}}
                            <div wire:loading wire:target="files.{{ $fileIndex }}" class="py-4">
                                <svg class="animate-spin mx-auto h-10 w-10 text-primary-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-600">{{ __('Uploading...') }}</p>
                            </div>
                        </div>

                        {{-- Progress bar --}}
                        @if($uploadProgress[$fileIndex] ?? 0 > 0 && $uploadProgress[$fileIndex] < 100)
                            <div class="mt-2">
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-primary-600 transition-all duration-300"
                                        style="width: {{ $uploadProgress[$fileIndex] }}%"
                                    ></div>
                                </div>
                            </div>
                        @endif

                        {{-- Validation errors --}}
                        @if(!empty($validationErrors[$fileIndex] ?? []))
                            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                                    @foreach($validationErrors[$fileIndex] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Validation warnings --}}
                        @if(!empty($validationWarnings[$fileIndex] ?? []))
                            <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1">
                                    @foreach($validationWarnings[$fileIndex] as $warning)
                                        <li>{{ $warning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endfor
    </div>
</div>
