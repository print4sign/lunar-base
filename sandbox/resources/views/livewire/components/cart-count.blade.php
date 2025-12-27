<div wire:poll.30s="updateCount">
    @if($count > 0)
        <div class="absolute inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full -top-1 -end-1">
            {{ $count > 99 ? '99+' : $count }}
        </div>
    @endif
</div>
