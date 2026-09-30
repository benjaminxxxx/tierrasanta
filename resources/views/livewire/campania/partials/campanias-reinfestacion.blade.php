<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ formatear_fecha($campania->reinfestacion_fecha) }}
</x-td>
<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->tipo_reinfestador }}
</x-td>
<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->numero_reinfestadores }}
</x-td>
<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->reinfestacion_kg_totales_madre }}
</x-td>
<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->reinfestacion_numero_pencas }}
</x-td>
<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ formatear_numero($campania->numero_reinfestadores_por_penca) }}
</x-td>
<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ formatear_numero($campania->gramos_cochinilla_mama_por_reinfestador) }}
</x-td>
<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->reinfestacion_duracion_desde_infestacion }}
</x-td>
<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->nitrogeno_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->fosforo_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->potasio_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->calcio_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->magnesio_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->manganeso_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->zinc_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->fierro_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->corrector_salinidad_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ $campania->riego_m3_infest_reinf }}
</x-td>
<x-td class="text-center" x-show="bloques.reinfestacion">
    {{ formatear_numero($campania->riego_m3_infest_reinfest_por_penca) }}
</x-td>