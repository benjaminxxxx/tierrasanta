<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::logoSubido('icono') ? \App\Services\Sistema\Empresa\SistemaEmpresaProceso::logoUrl('icono') : asset('images/icon/favicon.png') }}">

    <title>{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre() }}</title>

    <!-- Fuente e íconos (los mismos del sistema; ver DESIGN.md) -->
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>



<body
    x-data="{ 'darkMode': true }" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
$watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{ 'dark': darkMode === true }">
    <div
        class="font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em] min-h-screen bg-fog antialiased flex items-center justify-center py-8 px-4 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
    @livewireScripts
</body>

</html>