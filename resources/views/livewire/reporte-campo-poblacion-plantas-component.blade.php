<div>
    <x-loading wire:loading />
    @if (!$campaniaUnica)
        <x-flex>
            <x-h3>
                Población de Plantas
            </x-h3>
            <x-button type="button" @click="$wire.dispatch('agregarEvaluacion')">
                <i class="fa fa-plus"></i> Agregar Evaluación
            </x-button>
        </x-flex>
        <x-card class="my-4">
            <x-spacing>
                <x-select-campo wire:model.live="campoFiltrado" class="!w-auto" label="Filtrar por lote" error="false" />
            </x-spacing>
        </x-card>
    @endif

    <x-card>
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        @if (!$campaniaUnica)
                            <x-th class="text-center">
                                Campo
                            </x-th>
                            <x-th class="text-center">
                                Campaña
                            </x-th>
                        @endif
                        <x-th class="text-center">
                            Área
                        </x-th>
                        <x-th class="text-center">
                            Siembra
                        </x-th>
                        <x-th>
                            Evaluador
                        </x-th>
                        <x-th class="text-center">
                            Metros de<br />Cama/Ha
                        </x-th>
                        <x-th class="text-center">
                            Tipo de<br />Evaluación
                        </x-th>
                        <x-th class="text-center">
                            Fecha de<br />Evaluación
                        </x-th>
                        <x-th class="text-center">
                            Promedio<br />Plantas<br />x Cama
                        </x-th>
                        <x-th class="text-center">
                            Promedio<br />Plantas<br />x Metro
                        </x-th>
                        <x-th class="text-center">
                            Promedio<br />Plantas<br />x Ha
                        </x-th>
                        <x-th class="text-center">
                            Acciones
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($poblacionPlantas as $indicePoblacion => $poblacion)
                        <x-tr>
                            <x-td class="text-center">
                                {{ $indicePoblacion + 1 }}
                            </x-td>
                            @if (!$campaniaUnica)
                                <x-td class="text-center">
                                    {{ $poblacion->campania->campo }}
                                </x-td>
                                <x-td class="text-center">
                                    {{ $poblacion->campania->nombre_campania }}
                                </x-td>
                            @endif
                            <x-td class="text-center">
                                {{ $poblacion->area_lote }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->campania->fecha_siembra }}
                            </x-td>
                            <x-td>
                                {{ $poblacion->evaluador }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->metros_cama }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->tipo_evaluacion_legible }}
                            </x-td>
                            <x-td class="text-center">
                                {{ formatear_fecha($poblacion->fecha) }}
                            </x-td>
                            <x-th class="text-center">
                                {{ number_format($poblacion->promedio_plantas_x_cama,0) }}
                            </x-th>
                            <x-th class="text-center">
                                {{ number_format($poblacion->promedio_plantas_x_metro,0) }}
                            </x-th>
                            <x-th class="text-center">
                                {{ number_format($poblacion->promedio_plantas_ha,0) }}
                            </x-th>
                            <x-td class="text-center">
                                <x-flex class="justify-center">

                                    <x-secondary-button type="button"
                                        @click="$wire.dispatch('editarPoblacionPlanta',{poblacionId:{{ $poblacion->id }}})">
                                        <i class="fa fa-edit"></i> Editar
                                    </x-secondary-button>
                                    <x-danger-button type="button"
                                        wire:click="eliminarPoblacionPlanta({{ $poblacion->id }})">
                                        <i class="fa fa-trash"></i>
                                    </x-danger-button>

                                </x-flex>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="mt-5">
                {{ $poblacionPlantas->links() }}
            </div>
        </x-spacing>
    </x-card>

</div>
