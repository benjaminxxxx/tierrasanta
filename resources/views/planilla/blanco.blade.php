<x-app-layout title="Planilla - Blanco">


    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css">
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

    @can(\App\Constants\Permisos::PLANILLA_BLANCO_VER)
        <livewire:planilla-blanco-component />
    @else
        <x-danger>
            No tienes permiso para acceder a esta sección.
        </x-danger>
    @endcan


    <style>
        .has-explanation {
            position: relative;
        }

        .has-explanation::before {
            content: '';
            /* Triángulo de advertencia */
            position: absolute;
            top: 0;
            right: 0;
            border-right: 10px solid red;
            border-bottom: 10px solid transparent;
            border-top: 0 solid transparent;
        }
    </style>
</x-app-layout>