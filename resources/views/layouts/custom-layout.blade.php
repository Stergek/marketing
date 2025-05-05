<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ config('filament.dark_mode.enabled') && request()->hasCookie('dark_mode', 'true') ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Meta Marketing Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @filamentStyles
</head>
<body class="filament-panels antialiased font-sans bg-gray-50 dark:bg-gray-950">
    <!-- Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
        <a href="{{ route('filament.admin.pages.custom-dashboard') }}" class="text-xl font-bold text-gray-900 dark:text-gray-100">Meta Marketing</a>            <!-- Dark mode toggle -->
            <button id="dark-mode-toggle" class="p-2 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <span id="mode-icon" class="material-icons">brightness_4</span>
            </button>
        </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @yield('content')
    </div>

    @livewireScripts
    @filamentScripts

    <script>
        document.getElementById('dark-mode-toggle').addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            document.getElementById('mode-icon').textContent = isDark ? 'brightness_7' : 'brightness_4';
            document.cookie = `dark_mode=${isDark}; path=/`;
        });
    </script>
</body>
</html>