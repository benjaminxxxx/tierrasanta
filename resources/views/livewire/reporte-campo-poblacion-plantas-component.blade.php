<div>
    <x-loading wire:loading />
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
    <x-card class="my-4">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-td class="text-center">
                            N°
                        </x-td>
                        <x-td class="text-center">
                            LOTE
                        </x-td>
                        <x-td class="text-center">
                            ÁREA LOTE
                        </x-td>
                        <x-td class="text-center">
                            SIEMBRA
                        </x-td>
                        <x-td class="text-center">
                            EVALUADOR
                        </x-td>
                        <x-td class="text-center">
                            METROS DE CAMA/HA
                        </x-td>
                        <x-td class="text-center">
                            FECHA DIA CERO
                        </x-td>
                        <x-td class="text-center">
                            FECHA RESIEMBRA
                        </x-td>
                        <x-td class="text-center">
                            ACCIONES
                        </x-td>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($poblaciones as $indice => $poblacion)
                        <x-tr>
                            <x-td class="text-center">
                                {{ $indice + 1 }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->lote }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->area_lote }}
                            </x-td>
                            <x-td class="text-center">
                                
                            </x-td>
                            <x-td class="text-left">
                                {{ $poblacion->evaluador }}
                            </x-td>
                            <x-td class="text-center">
                                {{ number_format($poblacion->metros_cama, 2) }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->fecha_dia_cero }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $poblacion->fecha_resiembra }}
                            </x-td>
                            <x-td class="text-center">
                                <x-flex>

                                    <x-button type="button" @click="$wire.dispatch('abrirDetallePoblacionPlanta',{poblacionId:{{ $poblacion->id }}})">
                                        <i class="fa fa-list"></i> Detalle
                                    </x-button>
                                    <x-secondary-button type="button"
                                        @click="$wire.dispatch('editarPoblacionPlanta',{poblacionId:{{ $poblacion->id }}})">
                                        <i class="fa fa-edit"></i> Editar
                                    </x-secondary-button>
                                    <x-danger-button type="button"
                                        wire:click="preguntarEliminarPoblacionPlanta({{ $poblacion->id }})">
                                        <i class="fa fa-trash"></i>
                                    </x-danger-button>
                                </x-flex>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="mt-5">
                {{ $poblaciones->links() }}
            </div>
        </x-spacing>
    </x-card>
   
</div>

