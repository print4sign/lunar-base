@props(['title' => 'Dashboard'])

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="mx-auto max-w-screen-xl px-4 py-8 md:px-6 2xl:px-0">
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            {{-- Sidebar Navigation --}}
            <aside class="lg:col-span-3">
                <x-dashboard.sidebar />
            </aside>

            {{-- Main Content --}}
            <main class="lg:col-span-9 mt-8 lg:mt-0">
                {{ $slot }}
            </main>
        </div>
    </div>
</div>
