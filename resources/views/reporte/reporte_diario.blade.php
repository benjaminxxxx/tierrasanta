<x-app-layout>

    <link rel="stylesheet" href="{{ asset('css/handsontable.css') }}">
    <script src="{{ asset('js/handsontable.js') }}"></script>

    
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Reporte Diario de Trabajo
        </x-h3>
        <livewire:ver-labores-component />
    </div>
    <livewire:reporte-diario-component/>

</x-app-layout>
