<div>
    <x-loading wire:loading />
    <x-h3>
        Tipo de costos
    </x-h3>
    <x-card class="mt-2">
        <x-spacing>
            <form wire:submit="agregarNuevaDescripcion">
                <div class="grid grid-cols-1 gap-4">
                    {{-- Tipo de Costo --}}
                    <div>
                        <x-label for="tipoCosto" value="Tipo de Costo" />
                        <x-select wire:model="tipoCosto" class="uppercase">
                            <option value="operativo">OPERATIVO</option>
                            <option value="fijo">FIJO</option>
                        </x-select>
                        <x-input-error for="tipoCosto" />
                    </div>

                    {{-- Descripción --}}
                    <div>
                        <x-label for="nombreCosto" value="Descripción" />
                        <x-input type="text" class="uppercase" wire:model="nombreCosto" />
                        <x-input-error for="nombreCosto" />
                    </div>
                </div>

                {{-- Botón --}}
                <x-flex class="mt-4 justify-end w-full">
                    @if ($contabilidadCostoTipoId)
                        <x-secondary-button wire:click="resetForm" class="mr-2">
                            <i class="fa fa-times"></i> Cancelar
                        </x-secondary-button>
                        <x-button type="submit">
                            <i class="fa fa-save"></i> Actualizar Tipo de Costo
                        </x-button>
                    @else
                        <x-button type="submit">
                            <i class="fa fa-plus"></i> Agregar Tipo de Costo
                        </x-button>
                    @endif
                </x-flex>

            </form>
        </x-spacing>
    </x-card>
    <x-h3 class="mt-4">
        Lista de tipos de costos
    </x-h3>
    <x-card class="mt-3">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th class="text-center">
                            Tipo de costo
                        </x-th>
                        <x-th>
                            Descripción
                        </x-th>
                        <x-th class="text-center">
                            Registros
                        </x-th>
                        <x-th class="text-center">

                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($contabilidadCostoTipos)
                        @foreach ($contabilidadCostoTipos as $indice => $contabilidadCostoTipo)
                            <x-tr>
                                <x-td class="text-center">
                                    {{ $indice + 1 }}
                                </x-td>
                                <x-td class="text-center">
                                    {{ mb_strtoupper($contabilidadCostoTipo->tipo_costo) }}
                                </x-td>
                                <x-td>
                                    {{ $contabilidadCostoTipo->nombre_costo }}
                                </x-td>
                                <x-td class="text-center">
                                    {{ $contabilidadCostoTipo->registros->count() }}
                                </x-td>
                                <x-td class="text-center">
                                    @if (!$contabilidadCostoTipoId)
                                        <x-flex class="justify-end w-full">
                                            <x-button type="button"
                                                wire:click="editarTipoCosto({{ $contabilidadCostoTipo->id }})">
                                                <i class="fa fa-edit"></i> Editar
                                            </x-button>
                                            @if ($contabilidadCostoTipo->registros->count() == 0)
                                                <x-danger-button type="button"
                                                    wire:click="preguntarEliminarContabilidadCostoTipo({{ $contabilidadCostoTipo->id }})">
                                                    <i class="fa fa-trash"></i> Eliminar
                                                </x-danger-button>
                                            @endif
                                        </x-flex>
                                    @endif
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <p>No hay resultados aún.</p>
                    @endif

                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
