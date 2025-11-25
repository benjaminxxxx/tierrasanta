<x-tr class="">
    <x-th colspan="2" class="bg-gray-50 text-xs text-gray-700 uppercase  dark:bg-gray-700 dark:text-gray-400">POBLACIÓN PLANTAS</x-th>
    <x-td class="bg-gray-50 text-xs text-gray-700 uppercase  dark:bg-gray-700 dark:text-gray-400">
      
        @if ($campania)
            <x-button type="button" @click="$wire.dispatch('agregarEvaluacion',{campaniaId:{{ $campania->id }}})">
                <i class="fa fa-plus"></i> AGREGAR EVALUACIÓN
            </x-button>
            <x-button href="{{ route('reporte_campo.poblacion_plantas') }}" target="_blank">
                <i class="fa fa-link"></i> VER EVALUACIONES
            </x-button>
        @endif
    </x-td>
</x-tr>
<x-tr>
    <x-td>Fecha de evaluación día cero</x-td>
    <x-td>{{ formatear_fecha($campania->pp_dia_cero_fecha_evaluacion) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Nª de pencas madre día cero</x-td>
    <x-td>{{ formatear_numero($campania->pp_dia_cero_numero_pencas_madre) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Fecha de evaluación resiembra</x-td>
    <x-td>{{ formatear_fecha($campania->pp_resiembra_fecha_evaluacion) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Nª de pencas madre después de resiembra</x-td>
    <x-td>{{ formatear_numero($campania->pp_resiembra_numero_pencas_madre) }}</x-td>
</x-tr>