<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/handsontable@16.0.1/dist/handsontable.full.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/handsontable@16.0.1/dist/handsontable.full.min.css" rel="stylesheet">
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Reporte Diario de Trabajo
        </x-h3>
        <livewire:ver-labores-component />
    </div>
    <livewire:reporte-diario-component />

</x-app-layout>