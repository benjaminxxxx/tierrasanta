<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/handsontable-14-6-1.min.css') }}">
    <script src="{{ asset('handsontable/handsontable.full.min.js') }}"></script>
    
    <!--MODULO COCHINILLA FILTRADO-->
    @livewire('cochinilla-filtrado-component')
    @livewire('cochinilla-filtrado-form-component')

</x-app-layout>
