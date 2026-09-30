<x-td class="text-center" x-show="bloques.poblacion">
    {{ formatear_fecha($campania->pp_dia_cero_fecha_evaluacion) }}
</x-td>
<x-td class="text-center" x-show="bloques.poblacion">
    {{$campania->pp_dia_cero_numero_pencas_madre}}
</x-td>
<x-td class="text-center" x-show="bloques.poblacion">
    {{ formatear_fecha($campania->pp_resiembra_fecha_evaluacion) }}
</x-td>
<x-td class="text-center" x-show="bloques.poblacion">
    {{$campania->pp_resiembra_numero_pencas_madre}}
</x-td>