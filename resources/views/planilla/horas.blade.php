<x-app-layout>

    <link rel="stylesheet" href="{{ asset('css/handsontable.css') }}">
    <script src="{{ asset('js/handsontable.js') }}"></script>
   
    <livewire:planilla-asistencia-component :mes="$mes" :anio="$anio" />
</x-app-layout>
