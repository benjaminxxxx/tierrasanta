<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/handsontable-14-6-1.min.css') }}">
    <script src="{{ asset('handsontable/handsontable.full.min.js') }}"></script>
    
    <!--MODULO COCHINILLA VENTEADO-->
    @livewire('cochinilla-venteado-component')
    @livewire('cochinilla-venteado-form-component')

</x-app-layout>
