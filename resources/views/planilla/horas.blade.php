<x-app-layout>

    <link rel="stylesheet" href="{{ asset('css/handsontable-14-6-1.min.css') }}">
    <script src="{{ asset('js/handsontable-14-6-1.min.js') }}"></script>
   
    <livewire:planilla-asistencia-component :mes="$mes" :anio="$anio" />
</x-app-layout>
