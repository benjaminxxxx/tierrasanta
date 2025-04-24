<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/handsontable-14-6-1.min.css') }}">
    <script src="{{ asset('handsontable/handsontable.full.min.js') }}"></script>
    
    <!--MODULO COCHINILLA INGRESO-->
    @livewire('cochinilla-ingreso-mapa-component')
    @livewire('cochinilla-ingreso-component')
    @livewire('cochinilla-ingreso-form-component')
    @livewire('cochinilla-ingreso-detalle-component')
    @livewire('cochinilla-venteado-form-component')
    @livewire('cochinilla-filtrado-form-component')
</x-app-layout>
