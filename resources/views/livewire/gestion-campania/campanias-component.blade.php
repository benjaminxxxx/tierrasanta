<div>
    <x-loading wire:loading />

    <x-flex>
        <x-h3>
            Campañas
        </x-h3>
        <x-button @click="$wire.dispatch('registroCampania')">
            <i class="fa fa-plus"></i> Registrar nueva campaña
        </x-button>
    </x-flex>
    <x-card2 class="mt-4">
        <x-flex class="justify-between">
            <x-flex>
                <x-select-campo label="Filtrar por Campo" wire:model.live="campoSeleccionado" />

                @if (is_array($campanias) && count($campanias) > 0)
                    <x-select wire:model.live="campaniaSeleccionada" label="Seleccionar Campaña">
                        <option value="">Elegir Campaña</option>
                        @foreach ($campanias as $campaniaId => $campaniaNombre)
                            <option value="{{ $campaniaId }}">{{ $campaniaNombre }}</option>
                        @endforeach
                    </x-select>

                @endif

            </x-flex>
            <x-flex>
                <x-button variant="success" wire:click="descargarReporteCampania">
                    <i class="fa fa-file-excel"></i> Descargar Reporte
                </x-button>
            </x-flex>
        </x-flex>
    </x-card2>
    <x-card class="mt-3">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>

                        <x-th class="text-center" rowspan="2">
                            N°
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            Acciones
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            Campaña
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            Campo
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            Área
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            Siembra
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            Inicio de Campaña
                        </x-th>
                        <x-th class="text-center" rowspan="2">
                            Fin de Campaña
                        </x-th>
                        <x-th class="text-center bg-amber-600 text-white" colspan="4">
                            Población de Plantas
                        </x-th>
                    </x-tr>
                    <x-tr>
                        <x-th class="text-center bg-amber-600 text-white">
                            Fecha de evaluación día cero
                        </x-th>
                        <x-th class="text-center bg-amber-600 text-white">
                            Nª de pencas madre día cero
                        </x-th>
                        <x-th class="text-center bg-amber-600 text-white">
                            Fecha de evaluación resiembra
                        </x-th>
                        <x-th class="text-center bg-amber-600 text-white">
                            Nª de pencas madre después de resiembra
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($campaniasGenerales as $indice => $campania)
                        <x-tr>
                            <x-td class="text-center">
                                {{$indice + 1}}
                            </x-td>
                            <x-td class="text-center">
                                <x-dropdown align="left">
                                    <x-slot name="trigger">
                                        <span class="inline-flex rounded-md w-full lg:w-auto">
                                            <x-button type="button" class="flex items-center justify-center">
                                                Opciones
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                </svg>
                                            </x-button>
                                        </span>
                                    </x-slot>

                                    <x-slot name="content">
                                        <div class="w-full text-center">
                                            <x-dropdown-link class="text-center"
                                                @click="$wire.dispatch('editarCampania',{campaniaId:{{ $campania->id }}})">
                                                Editar Campaña
                                            </x-dropdown-link>
                                            <x-dropdown-link class="text-center !text-red-600"
                                                wire:confirm="¿Estás seguro de eliminar esta campaña?"
                                                wire:click="eliminarCampania({{ $campania->id }})">
                                                Eliminar Campaña
                                            </x-dropdown-link>
                                        </div>
                                    </x-slot>
                                </x-dropdown>
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->nombre_campania}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->campo}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->fecha_siembra}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->fecha_inicio}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->fecha_fin}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->pp_dia_cero_fecha_evaluacion}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->pp_dia_cero_numero_pencas_madre}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->pp_resiembra_fecha_evaluacion}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->pp_resiembra_numero_pencas_madre}}
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="my-4">
                {{$campaniasGenerales->links()}}
            </div>
        </x-spacing>
    </x-card>
</div>