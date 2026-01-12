@props(['notifications' => []])

@if($notifications && count($notifications) > 0)
    <div {{ $attributes->merge(['class' => 'space-y-2']) }}>
        @foreach($notifications as $notification)
            @php
                $type = $notification['type'] ?? 'info';
                $message = $notification['message'] ?? '';
                $classes = match($type) {
                    'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                    'success' => 'bg-green-50 border-green-200 text-green-800',
                    'error' => 'bg-red-50 border-red-200 text-red-800',
                    default => 'bg-blue-50 border-blue-200 text-blue-800',
                };
                $icon = match($type) {
                    'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
                    'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                };
            @endphp
            @if($message)
            <div class="flex items-center gap-3 p-4 border rounded-lg {{ $classes }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $icon !!}
                </svg>
                <p class="text-sm font-medium">{!! $message !!}</p>
            </div>
            @endif
        @endforeach
    </div>
@endif
