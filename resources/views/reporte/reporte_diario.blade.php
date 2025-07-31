<x-app-layout>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable/styles/handsontable.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable/styles/ht-theme-main.min.css" />
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Reporte Diario de Trabajo
        </x-h3>
        <livewire:ver-labores-component />
    </div>
    <livewire:reporte-diario-component />
    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>

</x-app-layout>