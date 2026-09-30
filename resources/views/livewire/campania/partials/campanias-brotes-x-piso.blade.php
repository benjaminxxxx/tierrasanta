<x-td class="text-center" x-show="bloques.brotes">
    {{ formatear_fecha($campania->brotexpiso_fecha_evaluacion) }}
</x-td>

<x-td class="text-center" x-show="bloques.brotes">
    {{$campania->brotexpiso_actual_brotes_2piso}}
</x-td>

<x-td class="text-center" x-show="bloques.brotes">
    {{$campania->brotexpiso_brotes_2piso_n_dias}}
</x-td>

<x-td class="text-center" x-show="bloques.brotes">
    {{$campania->brotexpiso_actual_brotes_3piso}}
</x-td>

<x-td class="text-center" x-show="bloques.brotes">
    {{$campania->brotexpiso_brotes_3piso_n_dias}}
</x-td>

<x-td class="text-center" x-show="bloques.brotes">
    {{$campania->brotexpiso_actual_total_brotes_2y3piso}}
</x-td>

<x-td class="text-center" x-show="bloques.brotes">
    {{$campania->brotexpiso_total_brotes_2y3piso_n_dias}}
</x-td>