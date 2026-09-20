@php
    // Lista de nombres de rutas de Laravel que ya están actualizadas a v18
    $rutasActualizadas = [
        'gestion_cuadrilleros.bonificaciones',
        'gestion_cuadrilleros.reporte-semanal.index',
        'gestion_insumos.kardex.detalle'
    ];

    // Condición: se activa si la ruta actual está en la lista o si se pasa $useV18 = true explícitamente desde la vista
    $cargarV18 = (isset($useV18) && $useV18) || (request()->route() && in_array(request()->route()->getName(), $rutasActualizadas));
@endphp

@if ($cargarV18)
    {{-- Version 18.1.1 para Módulos Actualizados --}}
    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/themes/main.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable/styles/ht-theme-main.min.css" />
@else
    {{-- Versión Legacy (Local) para Módulos Antiguos --}}
    <link rel="stylesheet" href="{{ asset('handsontable/handsontable.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('handsontable/ht-theme-main.min.css') }}" />
    <script src="{{ asset('handsontable/handsontable.full.min.js') }}"></script>
@endif