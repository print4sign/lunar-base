@props([
    'label',
    'value',
    'info' => null,
])

<div x-data="{ expanded: false }" class="py-4">
    <div class="flex items-center justify-between">
        <dt class="text-sm font-medium text-gray-900">
            {{ $label }}
        </dt>
        <dd class="flex items-center gap-2 text-sm text-gray-600">
            <span>{{ $value }}</span>
            @if($info)
                <button
                    type="button"
                    @click="expanded = !expanded"
                    class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center gap-1"
                >
                    {{ __('delivery_specs.explain') }}
                    <svg
                        class="w-4 h-4 transition-transform duration-200"
                        :class="{ 'rotate-180': expanded }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            @endif
        </dd>
    </div>
    @if($info)
        <div
            x-show="expanded"
            x-collapse
            class="mt-3 text-sm text-gray-500 bg-gray-50 rounded-lg p-3"
        >
            {{ $info }}
        </div>
    @endif
</div>
