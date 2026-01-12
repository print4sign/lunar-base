<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Store') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-50 antialiased">
    <div class="min-h-full flex flex-col">
        <!-- Navigation -->
        <x-storefront.header />

        <!-- Main Content -->
        <main class="flex-1 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="w-full max-w-md space-y-8">
                <div class="bg-white py-8 px-6 shadow-lg rounded-xl sm:px-10">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <!-- Footer -->
        <x-storefront.footer />

        <!-- Cart Sidebar -->
        <livewire:components.cart-sidebar />
    </div>

    @livewireScripts
</body>
</html>
