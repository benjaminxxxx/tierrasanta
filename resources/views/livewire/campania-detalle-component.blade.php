<div>
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            @if ($campania)
                Campaña {{ $campania->nombre_campania }}
            @endif
        </x-slot>

        <x-slot name="content">
            @if ($campania)
                <x-flex class="justify-end w-full mb-4">
                    <x-button type="button" wire:click="actualizarInformacionCampania">
                        <i class="fa fa-refresh"></i> Actualizar información
                    </x-button>
                </x-flex>
                <x-group-field>

                    <div>
                        <x-table>
                            <x-slot name="thead">

                            </x-slot>
                            <x-slot name="tbody">
                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        INFORMACIÓN GENERAL
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">Lote</p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->campo }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Variedad de tuna
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->variedad_tuna }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Campaña
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->nombre_campania }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Área
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->campo_model->area }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Sistema de cultivo
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->sistema_cultivo }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Pencas x Hectárea
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ number_format($campania->pencas_x_hectarea, 0) }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            T.C.
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->tipo_cambio }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        FECHA
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">Fecha de siembra</p>
                                        <p class="text-xs font-normal">
                                            La fecha de siembra se obtiene de la ultima siembra del campo<br />antes de
                                            la fecha de inicio de camapaña
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->fecha_siembra }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">Fecha de Inicio de Camapaña</p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->fecha_inicio }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fin de Campaña
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->fecha_fin }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        POBLACION PLANTAS
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha de evaluación día cero
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->pp_dia_cero_fecha_evaluacion }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Nª de pencas madre día cero
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->pp_dia_cero_numero_pencas_madre }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha de evaluación resiembra
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ formatear_fecha($campania->pp_resiembra_fecha_evaluacion) }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Nª de pencas madre después de resiembra
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->pp_resiembra_numero_pencas_madre }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        EVALUACION DE BROTES
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha de evaluación
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->brotexpiso_fecha_evaluacion }}
                                    </x-td>
                                </x-tr>

                                @if ($mostrarVacios || (!is_null($campania->brotexpiso_actual_brotes_2piso) && $campania->brotexpiso_actual_brotes_2piso != 0))
<x-tr>
    <x-td class="bg-gray-100">
        <p class="font-bold">Número actual de brotes aptos 2° piso</p>
    </x-td>
    <x-td class="text-center">
        {{ number_format($campania->brotexpiso_actual_brotes_2piso, 0) }}
    </x-td>
</x-tr>
@endif

@if ($mostrarVacios || (!is_null($campania->brotexpiso_brotes_2piso_n_dias) && $campania->brotexpiso_brotes_2piso_n_dias != 0))
<x-tr>
    <x-td class="bg-gray-100">
        <p class="font-bold">Número de brotes aptos 2° piso después de 60 días</p>
    </x-td>
    <x-td class="text-center">
        {{ number_format($campania->brotexpiso_brotes_2piso_n_dias, 0) }}
    </x-td>
</x-tr>
@endif

@if ($mostrarVacios || (!is_null($campania->brotexpiso_actual_brotes_3piso) && $campania->brotexpiso_actual_brotes_3piso != 0))
<x-tr>
    <x-td class="bg-gray-100">
        <p class="font-bold">Número actual de brotes aptos 3° piso</p>
    </x-td>
    <x-td class="text-center">
        {{ number_format($campania->brotexpiso_actual_brotes_3piso, 0) }}
    </x-td>
</x-tr>
@endif

@if ($mostrarVacios || (!is_null($campania->brotexpiso_brotes_3piso_n_dias) && $campania->brotexpiso_brotes_3piso_n_dias != 0))
<x-tr>
    <x-td class="bg-gray-100">
        <p class="font-bold">Número de brotes aptos 3° piso después de 60 días</p>
    </x-td>
    <x-td class="text-center">
        {{ number_format($campania->brotexpiso_brotes_3piso_n_dias, 0) }}
    </x-td>
</x-tr>
@endif

@if ($mostrarVacios || (!is_null($campania->brotexpiso_actual_total_brotes_2y3piso) && $campania->brotexpiso_actual_total_brotes_2y3piso != 0))
<x-tr>
    <x-td class="bg-gray-100">
        <p class="font-bold">Número actual total de brotes aptos 2° y 3° piso</p>
    </x-td>
    <x-td class="text-center">
        {{ number_format($campania->brotexpiso_actual_total_brotes_2y3piso, 0) }}
    </x-td>
</x-tr>
@endif

@if ($mostrarVacios || (!is_null($campania->brotexpiso_total_brotes_2y3piso_n_dias) && $campania->brotexpiso_total_brotes_2y3piso_n_dias != 0))
<x-tr>
    <x-td class="bg-gray-100">
        <p class="font-bold">Número total de brotes aptos 2° y 3° piso en 60 días</p>
    </x-td>
    <x-td class="text-center">
        {{ number_format($campania->brotexpiso_total_brotes_2y3piso_n_dias, 0) }}
    </x-td>
</x-tr>
@endif



                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        INFESTACIÓN
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha infestación
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->infestacion_fecha }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Tiempo de siembra o inicio de campaña a infestación
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->infestacion_duracion_desde_campania }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Número de pencas a la infestación
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ number_format($campania->infestacion_numero_pencas, 0) }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Kg totales de madres
                                        </p>
                                    </x-td>
                                    <x-td class="text-center bg-purple-100">
                                        {{ number_format($campania->infestacion_kg_totales_madre, 0) }}
                                    </x-td>
                                </x-tr>
                                @if ($campania->infestacion_kg_madre_infestador_carton != null && $campania->infestacion_kg_madre_infestador_carton != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">
                                                Kg de madres para infestador cartón
                                            </p>
                                        </x-td>
                                        <x-td class="text-center bg-orange-100">
                                            {{ number_format($campania->infestacion_kg_madre_infestador_carton, 0) }}
                                        </x-td>
                                    </x-tr>
                                @endif
                                @if ($campania->infestacion_kg_madre_infestador_tubos != null && $campania->infestacion_kg_madre_infestador_tubos != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">
                                                Kg de madres para infestador tubos
                                            </p>
                                        </x-td>
                                        <x-td class="text-center bg-indigo-100">
                                            {{ number_format($campania->infestacion_kg_madre_infestador_tubos, 0) }}
                                        </x-td>
                                    </x-tr>
                                @endif
                                @if (
                                    $campania->infestacion_kg_madre_infestador_mallita != null &&
                                        $campania->infestacion_kg_madre_infestador_mallita != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">
                                                Kg de madres para infestador mallita
                                            </p>
                                        </x-td>
                                        <x-td class="text-center bg-stone-100">
                                            {{ number_format($campania->infestacion_kg_madre_infestador_mallita, 0) }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Procedencia de las madres
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        @php
                                            $procedencias = [];
                                            if ($campania->infestacion_procedencia_madres) {
                                                if (is_string($campania->infestacion_procedencia_madres)) {
                                                    try {
                                                        $procedencias =
                                                            json_decode(
                                                                $campania->infestacion_procedencia_madres,
                                                                true,
                                                            ) ?:
                                                            [];
                                                    } catch (\Exception $e) {
                                                        $procedencias = [];
                                                    }
                                                } elseif (is_array($campania->infestacion_procedencia_madres)) {
                                                    $procedencias = $campania->infestacion_procedencia_madres;
                                                }
                                            }
                                        @endphp

                                        @if (count($procedencias) > 0)
                                            @foreach ($procedencias as $procedencia)
                                                <p>{{ $procedencia['campo_origen_nombre'] ?? 'No especificado' }}:
                                                    {{ number_format($procedencia['kg_madres'], 0) ?? 0 }}</p>
                                            @endforeach
                                        @else
                                            <p>No hay procedencia especificada</p>
                                        @endif
                                    </x-td>
                                </x-tr>

                                @if (
                                    !is_null($campania->infestacion_cantidad_madres_por_infestador_carton) &&
                                        $campania->infestacion_cantidad_madres_por_infestador_carton != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de madres por infestador cartón</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ number_format($campania->infestacion_cantidad_madres_por_infestador_carton, 0) }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->infestacion_cantidad_madres_por_infestador_tubos) &&
                                        $campania->infestacion_cantidad_madres_por_infestador_tubos != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de madres por infestador tubo</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->infestacion_cantidad_madres_por_infestador_tubos }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->infestacion_cantidad_madres_por_infestador_mallita) &&
                                        $campania->infestacion_cantidad_madres_por_infestador_mallita != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de madres por infestador mallita</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->infestacion_cantidad_madres_por_infestador_mallita }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->infestacion_cantidad_infestadores_carton) &&
                                        $campania->infestacion_cantidad_infestadores_carton != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de infestadores cartón</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->infestacion_cantidad_infestadores_carton }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->infestacion_cantidad_infestadores_tubos) &&
                                        $campania->infestacion_cantidad_infestadores_tubos != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de infestadores tubos</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->infestacion_cantidad_infestadores_tubos }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->infestacion_cantidad_infestadores_mallita) &&
                                        $campania->infestacion_cantidad_infestadores_mallita != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de infestadores mallita</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->infestacion_cantidad_infestadores_mallita }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha recojo y vaciado de infestadores
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->infestacion_fecha_recojo_vaciado_infestadores }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Permanencia infestadores (días)
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->infestacion_permanencia_infestadores }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha colocación de malla
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->infestacion_fecha_colocacion_malla }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha retiro de malla
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->infestacion_fecha_retiro_malla }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Permanencia de malla (días)
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->infestacion_permanencia_malla }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-th class="!text-primary bg-gray-100" colspan="2">
                                        RE-INFESTACIÓN
                                    </x-th>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha re-infestación
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->reinfestacion_fecha }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Tiempo desde infestación a re-infestación
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->reinfestacion_duracion_desde_infestacion }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Número de pencas a la re-infestación
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->reinfestacion_numero_pencas }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Kg totales de madres
                                        </p>
                                    </x-td>
                                    <x-td class="text-center bg-purple-100">
                                        {{ $campania->reinfestacion_kg_totales_madre }}
                                    </x-td>
                                </x-tr>
                                @if (
                                    !is_null($campania->reinfestacion_kg_madre_infestador_carton) &&
                                        $campania->reinfestacion_kg_madre_infestador_carton != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Kg de madres para infestador cartón</p>
                                        </x-td>
                                        <x-td class="text-center bg-orange-100">
                                            {{ $campania->reinfestacion_kg_madre_infestador_carton }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->reinfestacion_kg_madre_infestador_tubos) &&
                                        $campania->reinfestacion_kg_madre_infestador_tubos != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Kg de madres para infestador tubos</p>
                                        </x-td>
                                        <x-td class="text-center bg-indigo-100">
                                            {{ $campania->reinfestacion_kg_madre_infestador_tubos }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->reinfestacion_kg_madre_infestador_mallita) &&
                                        $campania->reinfestacion_kg_madre_infestador_mallita != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Kg de madres para infestador mallita</p>
                                        </x-td>
                                        <x-td class="text-center bg-stone-100">
                                            {{ $campania->reinfestacion_kg_madre_infestador_mallita }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Procedencia de las madres
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        @php
                                            $procedencias = [];
                                            if ($campania->reinfestacion_procedencia_madres) {
                                                if (is_string($campania->reinfestacion_procedencia_madres)) {
                                                    try {
                                                        $procedencias =
                                                            json_decode(
                                                                $campania->reinfestacion_procedencia_madres,
                                                                true,
                                                            ) ?:
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
                                                <p>{{ $procedencia['campo_origen_nombre'] ?? 'No especificado' }}:
                                                    {{ $procedencia['kg_madres'] ?? 0 }}</p>
                                            @endforeach
                                        @else
                                            <p>No hay procedencia especificada</p>
                                        @endif
                                    </x-td>
                                </x-tr>
                                @if (
                                    !is_null($campania->reinfestacion_cantidad_madres_por_infestador_carton) &&
                                        $campania->reinfestacion_cantidad_madres_por_infestador_carton != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de madres por infestador cartón</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->reinfestacion_cantidad_madres_por_infestador_carton }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->reinfestacion_cantidad_madres_por_infestador_tubos) &&
                                        $campania->reinfestacion_cantidad_madres_por_infestador_tubos != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de madres por infestador tubo</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->reinfestacion_cantidad_madres_por_infestador_tubos }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->reinfestacion_cantidad_madres_por_infestador_mallita) &&
                                        $campania->reinfestacion_cantidad_madres_por_infestador_mallita != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de madres por infestador mallita</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->reinfestacion_cantidad_madres_por_infestador_mallita }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->reinfestacion_cantidad_infestadores_carton) &&
                                        $campania->reinfestacion_cantidad_infestadores_carton != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de infestadores cartón</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->reinfestacion_cantidad_infestadores_carton }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->reinfestacion_cantidad_infestadores_tubos) &&
                                        $campania->reinfestacion_cantidad_infestadores_tubos != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de infestadores tubos</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->reinfestacion_cantidad_infestadores_tubos }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                @if (
                                    !is_null($campania->reinfestacion_cantidad_infestadores_mallita) &&
                                        $campania->reinfestacion_cantidad_infestadores_mallita != 0)
                                    <x-tr>
                                        <x-td class="bg-gray-100">
                                            <p class="font-bold">Cantidad de infestadores mallita</p>
                                        </x-td>
                                        <x-td class="text-center">
                                            {{ $campania->reinfestacion_cantidad_infestadores_mallita }}
                                        </x-td>
                                    </x-tr>
                                @endif

                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha recojo y vaciado de infestadores
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->reinfestacion_fecha_recojo_vaciado_infestadores }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Permanencia infestadores (días)
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->reinfestacion_permanencia_infestadores }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha colocación de malla
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->reinfestacion_fecha_colocacion_malla }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Fecha retiro de malla
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->reinfestacion_fecha_retiro_malla }}
                                    </x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td class="bg-gray-100">
                                        <p class="font-bold">
                                            Permanencia de malla (días)
                                        </p>
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ $campania->reinfestacion_permanencia_malla }}
                                    </x-td>
                                </x-tr>
                            </x-slot>
                        </x-table>
                    </div>
                </x-group-field>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
