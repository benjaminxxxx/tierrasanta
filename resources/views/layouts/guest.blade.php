<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{asset('images/icon/favicon.png')}}" type="image/png">

    <title>{{ config('app.name', 'Tierra Santa Holding S.A.C.') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>



<body>
    <div
        class="font-sans text-gray-900 dark:text-gray-100 min-h-screen bg-gray-50 dark:bg-gray-950 antialiased flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
    @livewireScripts
</body>

</html>