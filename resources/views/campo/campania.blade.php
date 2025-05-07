<x-app-layout>

    <link rel="stylesheet" href="{{ asset('css/handsontable-14-6-1.min.css') }}">
    <link rel="stylesheet" href="{{ asset('handsontable/ht-theme-main.min.css') }}">
    <script src="{{ asset('handsontable/handsontable.full.min.js') }}"></script>
    
    <livewire:campo-campania-component :campo="$campo" />
    <livewire:campo-campania-form-component />
    <livewire:campania-detalle-component />
    <livewire:reporte-campo-poblacion-planta-form-component :campaniaUnica="true"/>

</x-app-layout>
