<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/handsontable@16.0.1/dist/handsontable.full.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/handsontable@16.0.1/dist/handsontable.full.min.css" rel="stylesheet">
    <livewire:gestion-cuadrilla.gestion-cuadrilla-reporte-semanal-component />

    {{-- Este formulario tiene un handsontable dentro de un modal, debe estar aqui para evitar error de dom con id no encontrado --}}
    <livewire:gestion-cuadrilla.gestion-cuadrilla-gastos-adicionales-component />
    <livewire:gestion-cuadrilla.gestion-cuadrilla-asignacion-costos-component />
</x-app-layout>