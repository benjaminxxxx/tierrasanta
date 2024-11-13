<x-app-layout>
<!--
    <link rel="stylesheet" href="{{ asset('css/handsontable.css') }}">
    <script src="{{ asset('js/handsontable.js') }}"></script>-->

    <script src="{{asset('js/handsontable-14-6-1.min.js')}}"></script>
    <link rel="stylesheet" href="{{asset('css/handsontable-14-6-1.min.css')}}">

    
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Reporte Diario de Trabajo
        </x-h3>
        <livewire:ver-labores-component />
    </div>
    <livewire:reporte-diario-component/>

</x-app-layout>
