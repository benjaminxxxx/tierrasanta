<x-app-layout>

    
    <link rel="stylesheet" href="{{ asset('handsontable/handsontable.min.css') }}">
    <link rel="stylesheet" href="{{ asset('handsontable/ht-theme-main.min.css') }}">
    <script src="{{ asset('handsontable/handsontable.full.min.js') }}"></script>

    <livewire:cuadrilla-asistencia-component/>
    <livewire:cuadrilla-desde-empleados-component/>
    <livewire:cuadrillero-precio-por-dia-component/>
    <livewire:cuadrilla-asistencia-labores-component/>
    {{-- holaaaa <livewire:actividades-form-component/> --}}
    <livewire:cuadrilla-asistencia-labores-cuadrilleros-component/>
    <livewire:gasto-adicional-por-grupo-component/>
    <livewire:cuadrillero-detalle-horas-actividades-component/>
    <livewire:actividades-diarias.actividades-diarias-form-component/>
</x-app-layout>