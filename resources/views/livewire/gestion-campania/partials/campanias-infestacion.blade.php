<x-td class="text-center">
    {{ formatear_fecha($campania->infestacion_fecha) }}
</x-td>
<x-td class="text-center">
    {{ $campania->tipo_infestador }}
</x-td>
<x-td class="text-center">
    {{ $campania->numero_infestadores }}
</x-td>
<x-td class="text-center">
    {{ $campania->infestacion_kg_totales_madre }}
</x-td>
<x-td class="text-center">
    {{ $campania->infestacion_numero_pencas }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->numero_infestadores_por_penca) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->gramos_cochinilla_mama_por_infestador) }}
</x-td>
<x-td class="text-center">
    {{ $campania->infestacion_duracion_desde_campania }}
</x-td>
<x-td class="text-center">
    {{ $campania->nitrogeno_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->fosforo_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->potasio_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->calcio_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->magnesio_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->manganeso_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->zinc_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center">
    {{ $campania->fierro_desde_inicio_infestacion }}
</x-td>
<x-td class="text-center">
    {{ $campania->corrector_salinidad_desde_inicio_infestacion }}
</x-td>
<x-td class="text-center">
    {{ $campania->riego_m3_ini_infest }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->riego_m3_ini_infest_por_penca) }}
</x-td>