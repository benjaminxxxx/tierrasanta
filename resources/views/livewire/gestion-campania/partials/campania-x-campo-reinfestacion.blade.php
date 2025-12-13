<x-tr>
    <x-td>Fecha Re-infestación</x-td>
    <x-td class="text-right">
        {{ formatear_fecha($campania->reinfestacion_fecha) }}
    </x-td>
    <x-td></x-td>
    <x-td class="bg-gray-100 dark:bg-gray-600" rowspan="100%">
        @if ($campania)
            <div class="flex flex-wrap gap-4 space-y-2">
                <x-button type="button" @click="$wire.dispatch('editarReinfestacion',{campaniaId:{{ $campania->id }}})">
                    <i class="fa fa-edit"></i> EDITAR
                </x-button>
                <x-button type="button"
                    @click="$wire.dispatch('sincronizarReinformacionInfestacion',{campaniaId:{{ $campania->id }}})">
                    <i class="fa fa-sync"></i> SINCRONIZAR DESDE RE-INFESTACIÓN
                </x-button>
            </div>

        @endif
    </x-td>
</x-tr>
<x-tr>
    <x-td>Tiempo de infestación a re-infestación</x-td>
    <x-td class="text-right">{{ $campania->reinfestacion_duracion_desde_infestacion }}</x-td>
</x-tr>
<x-tr>
    <x-td>Número de pencas a la infestación</x-td>
    <x-td class="text-right">{{ number_format($campania->reinfestacion_numero_pencas, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg totales de madres</x-td>
    <x-td class="text-right">{{ number_format($campania->reinfestacion_kg_totales_madre, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg de madres para infestador cartón</x-td>
    <x-td class="text-right">{{ number_format($campania->reinfestacion_kg_madre_infestador_carton, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg de madres para infestador tubos</x-td>
    <x-td class="text-right">{{ number_format($campania->reinfestacion_kg_madre_infestador_tubos, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg de madres para infestador mallita</x-td>
    <x-td class="text-right">{{ number_format($campania->reinfestacion_kg_madre_infestador_mallita, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Procedencia de las madres</x-td>
    <x-td class="text-right"></x-td>
</x-tr>
@php
    $procedencias = [];
    if ($campania->reinfestacion_procedencia_madres) {
        // Asegurar que sea un array, deserializando si es necesario
        if (is_string($campania->reinfestacion_procedencia_madres)) {
            try {
                $procedencias =
                    json_decode($campania->reinfestacion_procedencia_madres, true) ?:
                    [];
            } catch (\Exception $e) {
                $procedencias = [];
            }
        } elseif (is_array($campania->reinfestacion_procedencia_madres)) {
            $procedencias = $campania->reinfestacion_procedencia_madres;
        }
    }
@endphp

@if (count($procedencias) > 0)
    @foreach ($procedencias as $procedencia)
        <x-tr>
            <x-td>{{ $procedencia['campo_origen_nombre'] ?? 'No especificado' }}</x-td>
            <x-td class="text-right">{{ number_format($procedencia['kg_madres'], 0) ?? 0 }}</x-td>
        </x-tr>
    @endforeach
@endif

<x-tr>
    <x-td>Cantidad de madres por infestador cartón</x-td>
    <x-td class="text-right">{{ $campania->reinfestacion_cantidad_madres_por_infestador_carton_alias }}</x-td>
</x-tr>
<x-tr>
    <x-td>Cantidad de madres por infestador tubo</x-td>
    <x-td class="text-right">{{ $campania->reinfestacion_cantidad_madres_por_infestador_tubos_alias }}</x-td>
</x-tr>
<x-tr>
    <x-td>Cantidad de madres por infestador mallita</x-td>
    <x-td class="text-right">{{ $campania->reinfestacion_cantidad_madres_por_infestador_mallita_alias }}</x-td>
</x-tr>
<x-tr>
    <x-td>Cantidad de infestadores cartón</x-td>
    <x-td class="text-right">{{ number_format($campania->reinfestacion_cantidad_infestadores_carton, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Cantidad de infestadores tubos</x-td>
    <x-td class="text-right">{{ number_format($campania->reinfestacion_cantidad_infestadores_tubos, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Cantidad de infestadores mallita</x-td>
    <x-td class="text-right">{{ number_format($campania->reinfestacion_cantidad_infestadores_mallita, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Fecha recojo y vaciado de infestadores</x-td>
    <x-td class="text-right">
        {{ formatear_fecha($campania->reinfestacion_fecha_recojo_vaciado_infestadores) }}
    </x-td>

</x-tr>
<x-tr>
    <x-td>Permanencia infestadores (días)</x-td>
    <x-td class="text-right">{{ $campania->reinfestacion_permanencia_infestadores }}</x-td>
</x-tr>
<x-tr>
    <x-td>Fecha colocación de malla</x-td>
    <x-td class="text-right">
        {{ formatear_fecha($campania->reinfestacion_fecha_colocacion_malla) }}
    </x-td>
</x-tr>

<x-tr>
    <x-td>Fecha retiro de malla</x-td>
    <x-td class="text-right">
        {{ formatear_fecha($campania->reinfestacion_fecha_retiro_malla) }}
    </x-td>
</x-tr>