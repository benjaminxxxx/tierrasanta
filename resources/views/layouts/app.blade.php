@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ \App\Services\Sistema\Empresa\SistemaEmpresaProceso::logoSubido('icono') ? \App\Services\Sistema\Empresa\SistemaEmpresaProceso::logoUrl('icono') : asset('images/icon/favicon.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? \App\Services\Sistema\Empresa\SistemaEmpresaProceso::nombre() }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}" />
    {{-- https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js --}}
    <script src="{{ asset('js/flatpickr/es.js') }}"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

</head>

<body
    x-data="{ page: 'ecommerce', 'loaded': true,'darkMode': true,  'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
     x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
$watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{ 'dark': darkMode === true }">
    <x-preloader />
    
    {{-- Escritorio: menú a la izquierda (fijo al alto de la pantalla). Celular/tablet: barra superior fija (h-14) y
         menú flotante; por eso el contenido baja 3.5rem. min-w-0 deja que el contenido se ajuste al espacio que queda. --}}
    <div class="lg:flex min-h-screen bg-background">
        <x-layouts.sidebar />
        <main class="flex-1 min-w-0 p-3 pt-[4.25rem] sm:p-5 sm:pt-[4.75rem] lg:pt-5 overflow-visible ultra-thin-scroll">
            {{ $slot }}
        </main>
    </div>

    @stack('modals')

    <script src="{{ asset('js/chart.js') }}"></script>
    @livewireScripts
    <script src="{{ asset('js/sweetalert2@11.js') }}"></script>
    <x-livewire-alert::scripts />

    
    <livewire:sistema.tareas-pendientes-component />
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('log', (event) => {
                console.log(event[0]);
            });
        });
    </script>
</body>

</html>