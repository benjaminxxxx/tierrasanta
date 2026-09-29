<x-td class="text-center">
    {{ formatear_fecha($campania->cosch_fecha) }}
</x-td>
<x-td class="text-center">
    {{ $campania->cosch_tiempo_inf_cosch }}
</x-td>
<x-td class="text-center">
    {{ $campania->cosch_tiempo_reinf_cosch }}
</x-td>
<x-td class="text-center">
    {{ $campania->cosch_tiempo_ini_cosch }}
</x-td>
<x-td class="text-center">
    {{ $campania->nutriente_nitrogeno_kg }}
</x-td>

<x-td class="text-center">
    {{ $campania->nutriente_fosforo_kg }}
</x-td>

<x-td class="text-center">
    {{ $campania->nutriente_potasio_kg }}
</x-td>

<x-td class="text-center">
    {{ $campania->nutriente_calcio_kg }}
</x-td>

<x-td class="text-center">
    {{ $campania->nutriente_magnesio_kg }}
</x-td>

<x-td class="text-center">
    {{ $campania->nutriente_manganeso_kg }}
</x-td>

<x-td class="text-center">
    {{ $campania->nutriente_zinc_kg }}
</x-td>

<x-td class="text-center">
    {{ $campania->nutriente_fierro_kg }}
</x-td>

<x-td class="text-center">
    {{ $campania->corrector_salinidad_cant }}
</x-td>

<x-td class="text-center">
    {{ $campania->riego_hrs_acumuladas }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->riego_m3_inicio_a_reinfestacion_por_penca) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->cosch_total_cosecha) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->cosch_produccion_total_kg_seco) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->cosch_rendimiento_por_infestador) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->cosch_rendimiento_x_penca) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->eval_cosch_proj_rdto_ha) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->proj_rdto_prom_rdto_ha) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->proj_diferencia_conteo) }}
</x-td>
<x-td class="text-center">
    {{ formatear_numero($campania->proj_diferencia_poda) }}
</x-td>
