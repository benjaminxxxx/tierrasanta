<div>
    <x-loading wire:loading />
    <x-flex class="w-full justify-between my-5">
        <x-h3>Población Plantas</x-h3>
        <x-flex>
            <x-secondary-button type="button">
                <i class="fa fa-file-excel"></i> Exportar Excel
            </x-secondary-button>
            <x-button type="button" @click="$wire.dispatch('agregarEvaluacion',{campaniaId:{{ $campaniaId }}})">
                <i class="fa fa-plus"></i> Agregar Evaluación
            </x-button>
        </x-flex>
    </x-flex>

    <x-card>
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
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
                            Metros de<br/>Cama/Ha
                        </x-th>
                        <x-th class="text-center">
                            Tipo de<br/>Evaluación
                        </x-th>
                        <x-th class="text-center">
                            Fecha de<br/>Evaluación
                        </x-th>
                        <x-th class="text-center">
                            Promedio<br/>Plantas<br/>x Cama
                        </x-th>
                        <x-th class="text-center">
                            Promedio<br/>Plantas<br/>x Metro
                        </x-th>
                        <x-th class="text-center">
                            Promedio<br/>Plantas<br/>x Ha
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
                                {{ $poblacion->fecha }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->promedio_plantas_x_cama }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->promedio_plantas_x_metro }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->promedio_plantas_ha }}
                            </x-td>
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
        </x-spacing>
    </x-card>
</div>
