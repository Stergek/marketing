<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @filamentStyles
</head>
<body class="filament-panels antialiased font-sans bg-gray-50 dark:bg-gray-950">
    @yield('content')
    @livewireScripts
    @filamentScripts
</body>
</html>