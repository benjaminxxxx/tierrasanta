<x-tr class="">
    <x-th-header colspan="2">INFESTACIÓN</x-th-header>
    <x-td class="bg-gray-50 text-xs text-gray-700 uppercase  dark:bg-gray-700 dark:text-gray-400">

        @if ($campania)
            <x-button type="button" @click="$wire.dispatch('editarInfestacion',{campaniaId:{{ $campania->id }}})">
                <i class="fa fa-edit"></i> EDITAR
            </x-button>
            <x-button type="button" @click="$wire.dispatch('sincronizarInformacionInfestacion',{campaniaId:{{ $campania->id }}})">
                <i class="fa fa-sync"></i> SINCRONIZAR DESDE INFESTACIÓN
            </x-button>
            
        @endif
    </x-td>
</x-tr>
<x-tr>
    <x-td>Fecha Infestación</x-td>
    <x-td>
        {{ formatear_fecha($campania->infestacion_fecha) }}
    </x-td>
</x-tr>
<x-tr>
    <x-td>Tiempo de siembra o inicio de campaña a infestación</x-td>
    <x-td>{{ $campania->infestacion_duracion_desde_campania }}</x-td>
</x-tr>
<x-tr>
    <x-td>Número de pencas a la infestación</x-td>
    <x-td>{{ number_format($campania->infestacion_numero_pencas, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg totales de madres</x-td>
    <x-td>{{ number_format($campania->infestacion_kg_totales_madre, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg de madres para infestador cartón</x-td>
    <x-td>{{ number_format($campania->infestacion_kg_madre_infestador_carton, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg de madres para infestador tubos</x-td>
    <x-td>{{ number_format($campania->infestacion_kg_madre_infestador_tubos, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Kg de madres para infestador mallita</x-td>
    <x-td>{{ number_format($campania->infestacion_kg_madre_infestador_mallita, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Procedencia de las madres</x-td>
    <x-td></x-td>
</x-tr>
@foreach ($campania->procedencias_madres as $procedencia)
    <x-tr>
        <x-td>{{ $procedencia['campo_origen_nombre'] ?? 'No especificado' }}</x-td>
        <x-td>{{ number_format($procedencia['kg_madres'], 0) ?? 0 }}</x-td>
    </x-tr>
@endforeach

<x-tr>
    <x-td>Cantidad de madres por infestador cartón</x-td>
    <x-td>{{ $campania->infestacion_cantidad_madres_por_infestador_carton_alias }}</x-td>
</x-tr>
<x-tr>
    <x-td>Cantidad de madres por infestador tubo</x-td>
    <x-td>{{ $campania->infestacion_cantidad_madres_por_infestador_tubos_alias }}</x-td>
</x-tr>
<x-tr>
    <x-td>Cantidad de madres por infestador mallita</x-td>
    <x-td>{{ $campania->infestacion_cantidad_madres_por_infestador_mallita_alias }}</x-td>
</x-tr>
<x-tr>
    <x-td>Cantidad de infestadores cartón</x-td>
    <x-td>{{ number_format($campania->infestacion_cantidad_infestadores_carton, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Cantidad de infestadores tubos</x-td>
    <x-td>{{ number_format($campania->infestacion_cantidad_infestadores_tubos, 0) }}</x-td>
</x-tr>

<x-tr>
    <x-td>Cantidad de infestadores mallita</x-td>
    <x-td>{{ number_format($campania->infestacion_cantidad_infestadores_mallita, 0) }}</x-td>
</x-tr>
<x-tr>
    <x-td>Fecha recojo y vaciado de infestadores</x-td>
    <x-td>
        {{ formatear_fecha($campania->infestacion_fecha_recojo_vaciado_infestadores) }}
    </x-td>

</x-tr>
<x-tr>
    <x-td>Permanencia infestadores (días)</x-td>
    <x-td>{{ $campania->infestacion_permanencia_infestadores }}</x-td>
</x-tr>
<x-tr>
    <x-td>Fecha colocación de malla</x-td>
    <x-td>
        {{ formatear_fecha($campania->infestacion_fecha_colocacion_malla) }}
    </x-td>
</x-tr>

<x-tr>
    <x-td>Fecha retiro de malla</x-td>
    <x-td>
        {{ formatear_fecha($campania->infestacion_fecha_retiro_malla) }}
    </x-td>
</x-tr>