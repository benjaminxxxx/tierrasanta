<div>
    <x-flex class="w-full justify-between my-5">
        <x-h3>
            Riego
        </x-h3>
    </x-flex>
    <x-flex class="!items-start w-full">
        @if ($campania)
            <x-card class="md:w-[35rem]">
                <x-spacing>
                    <x-h3>Resumen de riego</x-h3>

                    @if ($campania)
                        <x-table class="mt-3">
                            <x-slot name="thead">
                            </x-slot>
                            <x-slot name="tbody">
                                <x-tr>
                                    <x-td><b>Inicio</b></x-td>
                                    <x-td>{{ formatear_fecha($campania->riego_inicio) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Fin</b></x-td>
                                    <x-td>{{ formatear_fecha($campania->riego_fin) }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Descarga por hectárea (m3/há/hora)</b></x-td>
                                    <x-td x-data="{ editando: false }" class="bg-cyan-100">
                                        <template x-if="editando">
                                            <x-flex class="space-x-2 items-center">
                                                <x-input type="number" wire:model="riego_descarga_ha_hora" />
                                                <x-button type="button" wire:click="registrarRiegoDescargaHa"
                                                    @click="editando = false">
                                                    <i class="fa fa-save"></i>
                                                </x-button>
                                                <x-danger-button type="button" @click="editando = false"
                                                    color="secondary">
                                                    <i class="fa fa-times"></i>
                                                </x-danger-button>
                                            </x-flex>
                                        </template>

                                        <template x-if="!editando">
                                            <x-flex class="space-x-2 items-center">
                                                <span>{{ $campania->riego_descarga_ha_hora }}</span>

                                                <x-button type="button" @click="editando = true">
                                                    <i class="fa fa-edit"></i>
                                                </x-button>
                                            </x-flex>
                                        </template>
                                    </x-td>
                                </x-tr>
                                @php
                                    $formatear = fn($fecha) => $fecha ? formatear_fecha($fecha) : '—';

                                    $fechaInicio = $formatear($campania->fecha_inicio);
                                    $fechaInfest = $formatear($campania->infestacion_fecha);
                                    $fechaReinfest = $formatear($campania->reinfestacion_fecha);
                                    $fechaCosecha = $formatear($campania->cosch_fecha);

                                    $fechaDesde = $campania->reinfestacion_fecha ?? $campania->infestacion_fecha;
                                @endphp

                                <x-tr>
                                    <x-td>
                                        <b>Horas de riego de inicio a infestación</b>
                                        <p>({{ $fechaInicio }} - {{ $fechaInfest }})</p>
                                    </x-td>
                                    <x-td>{{ $campania->riego_hrs_ini_infest }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td><b>Metros cúbicos de inicio a infestación</b></x-td>
                                    <x-td>{{ $campania->riego_m3_ini_infest }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td>
                                        <b>Horas de infestación a reinfestación</b>
                                        <p>({{ $fechaInfest }} - {{ $fechaReinfest }})</p>
                                    </x-td>
                                    <x-td>{{ $campania->riego_hrs_infest_reinf }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td><b>Metros cúbicos de infestación a reinfestación</b></x-td>
                                    <x-td>{{ $campania->riego_m3_infest_reinf }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td>
                                        <b>Horas de infestación o reinfestación a cosecha</b>
                                        <p>({{ formatear_fecha($fechaDesde) }} - {{ $fechaCosecha }})</p>
                                    </x-td>
                                    <x-td>{{ $campania->riego_hrs_reinf_cosecha }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td><b>Metros cúbicos de infestación o reinfestación a cosecha</b></x-td>
                                    <x-td>{{ $campania->riego_m3_reinf_cosecha }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Horas de riego acumuladas</b></x-td>
                                    <x-td>{{ $campania->riego_hrs_acumuladas }}</x-td>
                                </x-tr>
                                <x-tr>
                                    <x-td><b>Acumulado x Há (m3)</b></x-td>
                                    <x-td>{{ $campania->riego_m3_acum_ha }}</x-td>
                                </x-tr>

                            </x-slot>
                        </x-table>
                    @endif


                </x-spacing>

            </x-card>
            <div class="flex-1 overflow-auto">
                <x-flex class="w-full justify-end mb-5">
                    <x-button type="button" wire:click="sincronizarRiegos">
                        <i class="fa fa-sync mr-2"></i> Sincronizar desde riegos
                    </x-button>
                </x-flex>
                <livewire:campo-campania-riego-component campaniaId="{{ $campania->id }}"
                    campaniaUnica="{{ true }}" wire:key="grupo_riego.{{ $campania->id }}" />

                <div class="mt-5">
                    <x-button-a href="{{ route('reporte.reporte_diario_riego') }}" target="_blank">
                        <i class="fa fa-link"></i> Registrar riego
                    </x-button-a>
                </div>

            </div>
        @endif
    </x-flex>
</div>
