<x-td class="text-center">
    {{ formatear_fecha($campania->reinfestacion_fecha) }}
</x-td>
<x-td class="text-center">
    {{ $campania->tipo_reinfestador }}
</x-td>
<x-td class="text-center">
    {{ $campania->numero_reinfestadores }}
</x-td>
<x-td class="text-center">
    {{ $campania->reinfestacion_kg_totales_madre }}
</x-td>
<x-td class="text-center">
    {{ $campania->reinfestacion_numero_pencas }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->numero_reinfestadores_por_penca) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->gramos_cochinilla_mama_por_reinfestador) }}
</x-td>
<x-td class="text-center">
    {{ $campania->reinfestacion_duracion_desde_infestacion }}
</x-td>
<x-td class="text-center">
    {{ $campania->nitrogeno_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->fosforo_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->potasio_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->calcio_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->magnesio_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->manganeso_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->zinc_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->fierro_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->corrector_salinidad_desde_infestacion_reinfestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->riego_m3_infest_reinf }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->riego_m3_infest_reinfest_por_penca) }}
</x-td>