<div>
    <x-loading wire:loading />
    @if (!$campaniaUnica)
        <x-flex>
            <x-h3>
                Evaluación de Brotes x Piso
            </x-h3>
            <x-button type="button" @click="$wire.dispatch('agregarEvaluacionBrote')">
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
                            Fecha de Evaluación
                        </x-th>
                        <x-th class="text-left">
                            Evaluador
                        </x-th>
                        <x-th>
                            Metros de Cama/Ha
                        </x-th>
                        <x-th class="text-center">
                            N° ACTUAL DE BROTES APTOS 2° PISO POR HECTAREA
                        </x-th>
                        <x-th class="text-center">
                            N° DE BROTES APTOS 2° PISO DESPUES DE 30 DIAS
                        </x-th>
                        <x-th class="text-center">
                            N° ACTUAL DE BROTES APTOS 3° PISO
                        </x-th>
                        <x-th class="text-center">
                            N° DE BROTES APTOS 3° PISO DESPUES DE 30 DIAS
                        </x-th>
                        <x-th class="text-center">
                            TOTAL ACTUAL DE BROTES APTOS 2° Y 3° PISO
                        </x-th>
                        <x-th class="text-center">
                            TOTAL DE BROTES APTOS 2° Y 3° PISO DESPUES DE 30 DIAS
                        </x-th>
                        <x-th class="text-center">
                            Acciones
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($evaluacionesBrotes as $indiceBrotePorPiso => $evaluacionBroteXPiso)
                        <x-tr>
                            <x-td class="text-center">
                                {{ $indiceBrotePorPiso + 1 }}
                            </x-td>
                            @if (!$campaniaUnica)
                                <x-td class="text-center">
                                    {{ $evaluacionBroteXPiso->campania->campo }}
                                </x-td>
                                <x-td class="text-center">
                                    {{ $evaluacionBroteXPiso->campania->nombre_campania }}
                                </x-td>
                            @endif
                            <x-td class="text-center">
                                {{ $evaluacionBroteXPiso->fecha }}
                            </x-td>
                            <x-td>
                                {{ $evaluacionBroteXPiso->evaluador }}
                            </x-td>
                            <x-td>
                                {{ $evaluacionBroteXPiso->metros_cama }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $evaluacionBroteXPiso->promedio_actual_brotes_2piso }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $evaluacionBroteXPiso->promedio_brotes_2piso_n_dias }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $evaluacionBroteXPiso->promedio_actual_brotes_3piso }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $evaluacionBroteXPiso->promedio_brotes_3piso_n_dias }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $evaluacionBroteXPiso->promedio_actual_total_brotes_2y3piso }}
                            </x-td>
                            <x-td class="text-center">
                                {{ $evaluacionBroteXPiso->promedio_total_brotes_2y3piso_n_dias }}
                            </x-td>
                            <x-td class="text-center">
                                <x-flex class="justify-center">
                                    @if ($evaluacionBroteXPiso->reporte_file)
                                        <x-secondary-button-a
                                            href="{{ Storage::disk('public')->url($evaluacionBroteXPiso->reporte_file) }}">
                                            <i class="fa fa-file-excel"></i> Exportar Excel
                                        </x-secondary-button-a>
                                    @endif

                                    <x-secondary-button type="button"
                                        @click="$wire.dispatch('editarEvaluacionBrotesPorPiso',{evaluacionBrotesXPisoId:{{ $evaluacionBroteXPiso->id }}})">
                                        <i class="fa fa-edit"></i> Editar
                                    </x-secondary-button>
                                    <x-secondary-button type="button"
                                        wire:click="duplicar({{ $evaluacionBroteXPiso->id }})">
                                        <i class="fa fa-clone"></i> Duplicar
                                    </x-secondary-button>
                                    <x-danger-button type="button"
                                        wire:click="eliminarBrotesXPiso({{ $evaluacionBroteXPiso->id }})">
                                        <i class="fa fa-trash"></i>
                                    </x-danger-button>

                                </x-flex>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="mt-5">
                {{ $evaluacionesBrotes->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
