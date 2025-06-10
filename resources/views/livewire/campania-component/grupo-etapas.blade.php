<div>
    <x-flex class="w-full justify-between my-5">
        <x-h3>
            Etapas
        </x-h3>
    </x-flex>
    <x-flex class="!items-start w-full">
        @if ($campania)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 w-full">
                <x-card2>
                    <x-h3>Crecimiento</x-h3>
                    <x-table class="mt-3">
                        <x-slot name="thead">
                        </x-slot>
                        <x-slot name="tbody">
                            <x-tr>
                                <x-td><b>Inicio de campaña</b></x-td>
                                <x-td>{{ formatear_fecha($campania->fecha_inicio) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Fecha de siembra</b></x-td>
                                <x-td>{{ formatear_fecha($campania->fecha_siembra) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Fecha de resiembra</b></x-td>
                                <x-td>{{ formatear_fecha($campania->pp_resiembra_fecha_evaluacion) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Población inicial de pencas</b></x-td>
                                <x-td>{{ $campania->pp_dia_cero_numero_pencas_madre }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Población actual de pencas</b></x-td>
                                <x-td>{{ $campania->pp_resiembra_numero_pencas_madre }}</x-td>
                            </x-tr>

                            {{-- Nutrientes --}}
                            <x-tr>
                                <x-td><b>Nitrógeno aplicado/ha</b></x-td>
                                <x-td>{{ $campania->n_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Nitrógeno aplicado/penca</b></x-td>
                                <x-td>{{ $campania->n_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Fósforo aplicado/ha</b></x-td>
                                <x-td>{{ $campania->p_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Fósforo aplicado/penca</b></x-td>
                                <x-td>{{ $campania->p_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Potasio aplicado/ha</b></x-td>
                                <x-td>{{ $campania->k_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Potasio aplicado/penca</b></x-td>
                                <x-td>{{ $campania->k_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Calcio aplicado/ha</b></x-td>
                                <x-td>{{ $campania->ca_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Calcio aplicado/penca</b></x-td>
                                <x-td>{{ $campania->ca_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Magnesio aplicado/ha</b></x-td>
                                <x-td>{{ $campania->mg_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Magnesio aplicado/penca</b></x-td>
                                <x-td>{{ $campania->mg_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Zinc aplicado/ha</b></x-td>
                                <x-td>{{ $campania->zn_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Zinc aplicado/penca</b></x-td>
                                <x-td>{{ $campania->zn_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Manganeso aplicado/ha</b></x-td>
                                <x-td>{{ $campania->mn_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Manganeso aplicado/penca</b></x-td>
                                <x-td>{{ $campania->mn_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Fierro aplicado/ha</b></x-td>
                                <x-td>{{ $campania->fe_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Fierro aplicado/penca</b></x-td>
                                <x-td>{{ $campania->fe_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Saltrad aplicado/ha</b></x-td>
                                <x-td>{{ $campania->saltrad_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Saltrad aplicado/penca</b></x-td>
                                <x-td>{{ $campania->saltrad_aplicado_penca }}</x-td>
                            </x-tr>
                        </x-slot>
                    </x-table>
                </x-card2>

                <x-card2>
                    <x-h3>Infestación</x-h3>
                    <x-table class="mt-3">
                        <x-slot name="thead">
                        </x-slot>
                        <x-slot name="tbody">
                            <x-tr>
                                <x-td><b>Fecha de infestación</b></x-td>
                                <x-td>{{ formatear_fecha($campania->infestacion_fecha) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Edad de plantación a la infestación</b></x-td>
                                <x-td>{{ $campania->infestacion_duracion_desde_campania }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Nùmero de pencas a la infestación</b></x-td>
                                <x-td>{{ $campania->infestacion_numero_pencas }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Insfestadores colocados: cartón</b></x-td>
                                <x-td>{{ formatear_numero($campania->infestacion_cantidad_infestadores_carton) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Infestadores colocados: tubos</b></x-td>
                                <x-td>{{ formatear_numero($campania->infestacion_cantidad_infestadores_tubos) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Infestadores colocados: mallita</b></x-td>
                                <x-td>{{ formatear_numero($campania->infestacion_cantidad_infestadores_mallita) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Cantidad de mama usada en infestación: cartón (kg)</b></x-td>
                                <x-td>{{ formatear_numero($campania->infestacion_kg_madre_infestador_carton) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Cantidad de mama usada en infestación: tubos (kg)</b></x-td>
                                <x-td>{{ formatear_numero($campania->infestacion_kg_madre_infestador_tubos) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Cantidad de mama usada en infestación: mallita (kg)</b></x-td>
                                <x-td>{{ formatear_numero($campania->infestacion_kg_madre_infestador_mallita) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Procedencia de madres</b></x-td>
                                <x-td></x-td>
                            </x-tr>
                            @foreach ($campania->procedencias_madres as $procedencia)
                                <x-tr>
                                    <x-td>{{ $procedencia['campo_origen_nombre'] ?? 'No especificado' }}</x-td>
                                    <x-td>{{ number_format($procedencia['kg_madres'], 0) ?? 0 }}</x-td>
                                </x-tr>
                            @endforeach

                            <x-tr>
                                <x-td><b>Permanencia de infestadores campo (días)</b></x-td>
                                <x-td>{{ formatear_numero($campania->infestacion_permanencia_infestadores) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Fecha de colocación malla raschel</b></x-td>
                                <x-td>{{ formatear_fecha($campania->infestacion_fecha_colocacion_malla) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Permanencia de malla raschel en campo (días)</b></x-td>
                                <x-td>{{ formatear_numero($campania->infestacion_permanencia_malla) }}</x-td>
                            </x-tr>

                            {{-- Nutrientes --}}
                            <x-tr>
                                <x-td><b>Nitrógeno aplicado/ha</b></x-td>
                                <x-td>{{ $campania->n_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Nitrógeno aplicado/penca</b></x-td>
                                <x-td>{{ $campania->n_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Fósforo aplicado/ha</b></x-td>
                                <x-td>{{ $campania->p_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Fósforo aplicado/penca</b></x-td>
                                <x-td>{{ $campania->p_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Potasio aplicado/ha</b></x-td>
                                <x-td>{{ $campania->k_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Potasio aplicado/penca</b></x-td>
                                <x-td>{{ $campania->k_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Calcio aplicado/ha</b></x-td>
                                <x-td>{{ $campania->ca_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Calcio aplicado/penca</b></x-td>
                                <x-td>{{ $campania->ca_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Magnesio aplicado/ha</b></x-td>
                                <x-td>{{ $campania->mg_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Magnesio aplicado/penca</b></x-td>
                                <x-td>{{ $campania->mg_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Zinc aplicado/ha</b></x-td>
                                <x-td>{{ $campania->zn_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Zinc aplicado/penca</b></x-td>
                                <x-td>{{ $campania->zn_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Manganeso aplicado/ha</b></x-td>
                                <x-td>{{ $campania->mn_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Manganeso aplicado/penca</b></x-td>
                                <x-td>{{ $campania->mn_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Fierro aplicado/ha</b></x-td>
                                <x-td>{{ $campania->fe_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Fierro aplicado/penca</b></x-td>
                                <x-td>{{ $campania->fe_aplicado_penca }}</x-td>
                            </x-tr>

                            <x-tr>
                                <x-td><b>Saltrad aplicado/ha</b></x-td>
                                <x-td>{{ $campania->saltrad_aplicado_ha }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Saltrad aplicado/penca</b></x-td>
                                <x-td>{{ $campania->saltrad_aplicado_penca }}</x-td>
                            </x-tr>
                        </x-slot>
                    </x-table>
                </x-card2>
                <x-card2>
                    <x-h3>Re-infestación</x-h3>
                    <x-table class="mt-3">
                        <x-slot name="thead">
                        </x-slot>
                        <x-slot name="tbody">
                            <x-tr>
                                <x-td><b>Fecha de re-infestación</b></x-td>
                                <x-td>{{ formatear_fecha($campania->reinfestacion_fecha) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Días de infestación a re-infestación</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_duracion_desde_infestacion) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Número de pencas a la re-infestación</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_numero_pencas) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Infestadores colocados: cartón</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_cantidad_infestadores_carton) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Infestadores colocados: tubos</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_cantidad_infestadores_tubos) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Infestadores colocados: mallita</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_cantidad_infestadores_mallita) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Cantidad de mama usada en re-infestación: cartón (kg)</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_kg_madre_infestador_carton) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Cantidad de mama usada en re-infestación: tubos (kg)</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_kg_madre_infestador_tubos) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Cantidad de mama usada en re-infestación: mallita (kg)</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_kg_madre_infestador_mallita) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Procedencia de madres (re-infestación)</b></x-td>
                                <x-td></x-td>
                            </x-tr>
                            @foreach ($campania->procedencias_madres_reinfestacion as $procedencia)
                                <x-tr>
                                    <x-td>{{ $procedencia['campo_origen_nombre'] ?? 'No especificado' }}</x-td>
                                    <x-td>{{ number_format($procedencia['kg_madres'], 0) ?? 0 }}</x-td>
                                </x-tr>
                            @endforeach
                            <x-tr>
                                <x-td><b>Permanencia de infestadores campo (días)</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_permanencia_infestadores) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Fecha de colocación malla raschel</b></x-td>
                                <x-td>{{ formatear_fecha($campania->reinfestacion_fecha_colocacion_malla) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Permanencia de malla raschel en campo (días)</b></x-td>
                                <x-td>{{ formatear_numero($campania->reinfestacion_permanencia_malla) }}</x-td>
                            </x-tr>
                        </x-slot>
                    </x-table>

                </x-card2>
                <x-card2>
                    <x-h3>Cosecha</x-h3>
                    <x-table class="mt-3">
                        <x-slot name="thead"></x-slot>
                        <x-slot name="tbody">
                            <x-tr>
                                <x-td><b>Fecha de cosecha</b></x-td>
                                <x-td>{{ formatear_fecha($campania->cosch_fecha) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Tiempo de infestación a cosecha (días)</b></x-td>
                                <x-td>{{ formatear_numero($campania->cosch_tiempo_inf_cosch) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Tiempo de re-infestación a cosecha (días)</b></x-td>
                                <x-td>{{ formatear_numero($campania->cosch_tiempo_reinf_cosch) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Producción poda (kg seco)</b></x-td>
                                <x-td>{{ formatear_numero($campania->cosch_total_cosecha) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Producción total campaña (kg seco)</b></x-td>
                                <x-td>{{ formatear_numero($campania->cosch_total_campania) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Número de pencas a la cosecha</b></x-td>
                                <x-td>{{ formatear_numero($campania->eval_cosch_proj_penca_inf) }}</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Porcentaje ácido carmínico</b></x-td>
                                <x-td>{{ formatear_numero($campania->acid_prom) }}%</x-td>
                            </x-tr>
                            <x-tr>
                                <x-td><b>Tamaño cochinilla (N° individuos/gramo)</b></x-td>
                                <x-td>{{ formatear_numero($campania->acid_tam) }}</x-td>
                            </x-tr>
                        </x-slot>
                    </x-table>

                </x-card2>
            </div>
        @endif
    </x-flex>
</div>
