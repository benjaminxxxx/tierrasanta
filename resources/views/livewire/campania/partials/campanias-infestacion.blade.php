<x-td class="text-center" x-show="bloques.infestacion">
    {{ formatear_fecha($campania->infestacion_fecha) }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->tipo_infestador }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->numero_infestadores }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->infestacion_kg_totales_madre }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->infestacion_numero_pencas }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ formatear_numero($campania->numero_infestadores_por_penca) }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ formatear_numero($campania->gramos_cochinilla_mama_por_infestador) }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->infestacion_duracion_desde_campania }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->nitrogeno_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->fosforo_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->potasio_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->calcio_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->magnesio_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->manganeso_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->zinc_desde_inicio_infestacion }}
</x-td>

<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->fierro_desde_inicio_infestacion }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->corrector_salinidad_desde_inicio_infestacion }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ $campania->riego_m3_ini_infest }}
</x-td>
<x-td class="text-center" x-show="bloques.infestacion">
    {{ formatear_numero($campania->riego_m3_ini_infest_por_penca) }}
</x-td>