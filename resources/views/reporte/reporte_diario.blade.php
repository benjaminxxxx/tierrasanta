<x-app-layout>
<!--
    <link rel="stylesheet" href="{{ asset('css/handsontable.css') }}">
    <script src="{{ asset('js/handsontable.js') }}"></script>-->

    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css">

    
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Reporte Diario de Trabajo
        </x-h3>
        <livewire:ver-labores-component />
    </div>
    <livewire:reporte-diario-component/>

</x-app-layout>
