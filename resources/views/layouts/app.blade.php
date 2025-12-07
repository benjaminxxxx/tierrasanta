<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="{{ asset('images/icon/favicon.png') }}" type="image/png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tierra Santa Holding S.A.C.') }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fontawesome.min.css') }}" />
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @include('comun.handsontable')
    <style>
        /* Sidebar Styles */
        .sidebar {
            width: 82px;
            transition: width 0.3s ease-in-out;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 9999;
            background: #1F2937;
            overflow: hidden;
        }

        .sidebar.expanded {
            width: 18rem;
        }

        /* Menu text animations */
        .menu-text {
            opacity: 0;
            width: 0;
        }

        .sidebar.expanded .menu-text {
            opacity: 1;
            width: auto;
        }

        .sidebar.expanded .hidden-on-expanded {
            opacity: 0;
            width: 0;
        }

        .sidebar.expanded .buton-on-sidebar {
            gap: 0.75rem;
        }

        /* Submenu animations */
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }

        .submenu.open {
            max-height: 500px;
        }

        /* Content area adjustment */
        .content-area {
            margin-left: 82px;
            transition: margin-left 0.3s ease-in-out;
            width: calc(100% - 82px);
        }

        .sidebar .ultra-thin-scroll {
            overflow-y: hidden;
        }

        .sidebar.expanded .ultra-thin-scroll {
            overflow-y: auto;
        }


        /* Scroll ultrafino y moderno */
        .ultra-thin-scroll {
            scrollbar-width: thin;
            /* Firefox */
            scrollbar-color: transparent transparent;
        }

        .ultra-thin-scroll:hover {
            scrollbar-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0);
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
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 9999px;
            border: none;
        }

        .ultra-thin-scroll::-webkit-scrollbar-thumb:hover {
            background-color: rgba(0, 0, 0, 0.2);
        }


        /* Mobile styles */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                width: 18rem;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .content-area {
                margin-left: 0;
                width: 100%;
            }

            .mobile-overlay {
                position: fixed;
                inset: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 9998;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
            }

            .mobile-overlay.active {
                opacity: 1;
                visibility: visible;
            }
        }
    </style>
    @fluxStyles
</head>

<body
    x-data="{ page: 'ecommerce', 'loaded': true,'darkMode': true,  'stickyMenu': false, 'sidebarToggle': false, 'scrollTop': false }"
     x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'));
$watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
    :class="{ 'dark': darkMode === true }">
    <x-preloader />

    <div class="flex h-screen bg-gray-100 dark:bg-gray-900">
        <x-sidebar />
        <main class="flex-1 p-5 overflow-auto">
            {{ $slot }}
        </main>
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
    @fluxScripts
</body>

</html>