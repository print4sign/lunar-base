<div @if($isPolling) wire:poll.{{ $pollInterval }}ms="checkStatus" @endif>
    {{-- Debug: Show current status --}}
    <div class="text-xs text-gray-400 mb-2">Status: {{ $status }} | UploaderId: {{ $uploaderId ?? 'none' }}</div>

    {{-- Pending state - no session yet --}}
    @if($status === 'pending')
        <div class="text-center py-8">
            <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                {{ __('Probo Uploader') }}
            </h3>
            <p class="text-sm text-gray-600 mb-6 max-w-md mx-auto">
                {{ __('Use Probo\'s professional uploader to prepare your print files. The uploader will open in a new tab.') }}
            </p>
            <button
                type="button"
                wire:click="createSession"
                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                {{ __('Start Probo Uploader') }}
            </button>
        </div>
    @endif

    {{-- Creating state --}}
    @if($status === 'creating')
        <div class="text-center py-8">
            <div class="mx-auto w-16 h-16 flex items-center justify-center mb-4">
                <svg class="animate-spin h-12 w-12 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                {{ __('Creating uploader session...') }}
            </h3>
            <p class="text-sm text-gray-600">
                {{ __('Please wait while we prepare the uploader.') }}
            </p>
        </div>
    @endif

    {{-- Created state - session ready, waiting for user to upload --}}
    {{-- Also handle 'pending_callback' as it means the same thing (waiting for upload completion) --}}
    @if(in_array($status, ['created', 'pending_callback']))
        <div class="text-center py-8">
            <div class="mx-auto w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                {{ __('Waiting for upload') }}
            </h3>
            <p class="text-sm text-gray-600 mb-4 max-w-md mx-auto">
                {{ __('Click the button below to open the Probo uploader in a new tab.') }}
            </p>
            <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                {{ __('After uploading your files in the Probo uploader, click "Check Status" to verify.') }}
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button
                    type="button"
                    wire:click="openUploader"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    {{ __('Open Probo Uploader') }}
                </button>
                <button
                    type="button"
                    wire:click="checkStatus"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors disabled:opacity-50"
                >
                    <svg wire:loading.remove wire:target="checkStatus" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <svg wire:loading wire:target="checkStatus" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('Check Status') }}
                </button>
            </div>

            {{-- Polling indicator --}}
            @if($isPolling)
                <p class="mt-4 text-xs text-gray-500 flex items-center justify-center gap-1">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    {{ __('Automatically checking for updates...') }}
                </p>
            @endif

            {{-- Info about callback --}}
            <div class="mt-6 p-3 bg-blue-50 rounded-lg text-sm text-blue-700 max-w-md mx-auto">
                <p>{{ __('The status will update automatically when Probo notifies us that your upload is complete.') }}</p>
            </div>

            {{-- Dev: Manual mark as complete (webhooks don't work locally) --}}
            @if(config('app.debug'))
                <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg max-w-md mx-auto">
                    <p class="text-xs text-orange-700 mb-2">
                        <strong>Dev Mode:</strong> Webhooks can't reach localhost. After uploading in Probo, click below to simulate the callback.
                    </p>
                    <button
                        type="button"
                        wire:click="markAsComplete"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-orange-500 text-white text-xs font-medium rounded hover:bg-orange-600 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Mark as Complete (Dev)
                    </button>
                </div>
            @endif

            {{-- Reset option --}}
            <button
                type="button"
                wire:click="resetSession"
                class="mt-4 text-sm text-gray-500 hover:text-gray-700 underline"
            >
                {{ __('Start over') }}
            </button>
        </div>
    @endif

    {{-- Confirmed/Processed state - upload complete --}}
    @if(in_array($status, ['confirmed', 'processed']))
        <div class="text-center py-8">
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                {{ __('Upload complete!') }}
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                {{ __('Your files have been uploaded successfully via Probo.') }}
            </p>

            @if($uploaderId)
                <p class="text-xs text-gray-400">
                    {{ __('Session ID: :id', ['id' => $uploaderId]) }}
                </p>
            @endif

            {{-- Option to re-upload --}}
            <button
                type="button"
                wire:click="resetSession"
                wire:confirm="{{ __('Are you sure you want to upload new files? This will replace the current upload.') }}"
                class="mt-6 text-sm text-gray-500 hover:text-gray-700 underline"
            >
                {{ __('Upload different files') }}
            </button>
        </div>
    @endif

    {{-- Error state --}}
    @if($status === 'error')
        <div class="text-center py-8">
            <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                {{ __('Something went wrong') }}
            </h3>
            @if($errorMessage)
                <p class="text-sm text-red-600 mb-4">
                    {{ $errorMessage }}
                </p>
            @else
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('Failed to create the uploader session. Please try again.') }}
                </p>
            @endif

            <button
                type="button"
                wire:click="createSession"
                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ __('Try Again') }}
            </button>
        </div>
    @endif
</div>
