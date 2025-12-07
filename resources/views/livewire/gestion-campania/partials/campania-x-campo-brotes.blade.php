<x-tr class="">
    <x-th-header colspan="3">EVALUACIÓN DE BROTES</x-th-header>
    <x-td class=" text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-600">
      
        @if ($campania)
            <x-button type="button" @click="$wire.dispatch('agregarEvaluacionBrote',{campaniaId:{{ $campania->id }}})">
                <i class="fa fa-plus"></i> AGREGAR EVALUACIÓN
            </x-button>
            <x-button href="{{ route('reporte_campo.evaluacion_brotes') }}" target="_blank">
                <i class="fa fa-link"></i> VER EVALUACIONES
            </x-button>
        @endif
    </x-td>
</x-tr>
<x-tr>
    <x-td>Fecha de evaluación</x-td>
    <x-td>{{ formatear_fecha($campania->brotexpiso_fecha_evaluacion) }}</x-td>
    <x-td></x-td>
    <x-td  class="bg-gray-100 dark:bg-gray-600" rowspan="7"></x-td>
</x-tr>

<x-tr>
    <x-td>Número actual de brotes aptos 2° piso</x-td>
    <x-td>{{ number_format($campania->brotexpiso_actual_brotes_2piso, 0) }}</x-td>
</x-tr>

<x-tr>
    <x-td>Número de brotes aptos 2° piso después de 60 días</x-td>
    <x-td>{{ number_format($campania->brotexpiso_brotes_2piso_n_dias, 0) }}</x-td>
</x-tr>

<x-tr>
    <x-td>Número actual de brotes aptos 3° piso</x-td>
    <x-td>{{ number_format($campania->brotexpiso_actual_brotes_3piso, 0) }}</x-td>
</x-tr>

<x-tr>
    <x-td>Número de brotes aptos 3° piso después de 60 días</x-td>
    <x-td>{{ number_format($campania->brotexpiso_brotes_3piso_n_dias, 0) }}</x-td>
</x-tr>

<x-tr>
    <x-td>Número actual total de brotes aptos 2° y 3° piso</x-td>
    <x-td>{{ number_format($campania->brotexpiso_actual_total_brotes_2y3piso, 0) }}</x-td>
</x-tr>

<x-tr>
    <x-td>Número total de brotes aptos 2° y 3° piso en 60 días</x-td>
    <x-td>{{ number_format($campania->brotexpiso_total_brotes_2y3piso_n_dias, 0) }}</x-td>
</x-tr>
