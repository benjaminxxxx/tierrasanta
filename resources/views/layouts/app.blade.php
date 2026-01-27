@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('images/icon/favicon.png') }}" type="image/png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ??  'Tierra Santa Holding S.A.C.'}}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('comun.handsontable')
    <style>
      


        /* Scroll ultrafino y moderno */
        .ultra-thin-scroll {
            scrollbar-width: thin;
            /* Firefox */
            scrollbar-color: transparent transparent;
        }

        .ultra-thin-scroll:hover {
            scrollbar-color: rgba(114, 114, 114, 0.5) rgba(124, 124, 124, 0);
        }

        /* Webkit (Chrome, Edge, Safari) */
        .ultra-thin-scroll::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        .ultra-thin-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .ultra-thin-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(212, 212, 212, 0.5);
            border-radius: 9999px;
            border: none;
        }

        .ultra-thin-scroll::-webkit-scrollbar-thumb:hover {
            background-color: rgba(136, 136, 136, 0.7);
        }
    </style>
</head>

<body
    x-data="{ page: 'ecommerce', 'loaded': true,'darkMode': true,  'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
     x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
$watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{ 'dark': darkMode === true }">
    <x-preloader />
    <livewire:menu-bar-component/>
    <div class="flex min-h-screen bg-gray-100 dark:bg-gray-900 pt-[32px]">
        
        <x-sidebar />
        <main class="flex-1 p-5 overflow-visible ultra-thin-scroll">
            {{ $slot }}
        </main>
        @include('comun.components')
    </div>

    @stack('modals')

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @livewireScripts
    <script src="{{ asset('js/sweetalert2@11.js') }}"></script>
    <x-livewire-alert::scripts />

    

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('log', (event) => {
                console.log(event[0]);
            });
        });
    </script>
</body>

</html>