<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Lista de Valoración de obra a través del tiempo
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="$set('mostrarFormulario',false)" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th class="text-center">
                            Vigencia desde
                        </x-th>
                        <x-th class="text-center">
                            Unidad alcanzada en 8 horas
                        </x-th>
                        <x-th class="text-center">
                            Valor de unidad por hora
                        </x-th>
                        <x-th class="text-center">
                            Valor por hora adicional
                        </x-th>
                        <x-th class="text-center">
                            Acciones
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    <x-tr>
                        <x-td>
                            -
                        </x-td>
                        <x-td>
                            <x-input type="date" class="text-center" wire:model="vigencia_desde" />
                            <x-input-error for="vigencia_desde" />
                        </x-td>
                        <x-td>
                            <x-input type="number" class="text-center" wire:model="kg_8" />
                            <x-input-error for="kg_8" />
                        </x-td>
                        <x-td>
                            -
                        </x-td>
                        <x-td>
                            <x-input type="number" class="text-center" wire:model="valor_kg_adicional" />
                            <x-input-error for="valor_kg_adicional" />
                        </x-td>
                        <x-td>
                            @if ($valoracionId)
                            <x-flex class="">
                                <x-secondary-button type="button" wire:click="agregarValoracion">
                                    <i class="fa fa-edit"></i> Editar
                                </x-secondary-button>
                                <x-danger-button type="button" wire:click="cancelarEdicionValoracion">
                                    <i class="fa fa-remove"></i>Cancelar
                                </x-danger-button>
                            </x-flex>
                            @else
                            <x-secondary-button type="button" wire:click="agregarValoracion">
                                <i class="fa fa-plus"></i> Agregar
                            </x-secondary-button>
                            @endif
                            
                        </x-td>
                    </x-tr>
                    @foreach ($valoraciones as $indice => $valoracion)
                    <x-tr>
                        <x-td class="text-center">
                            {{$indice+1}}
                        </x-td>
                        <x-td class="text-center">
                            {{$valoracion->vigencia_desde}}
                        </x-td>
                        <x-td class="text-center">
                            {{$valoracion->kg_8}}
                        </x-td>
                        <x-td class="text-center">
                            {{$valoracion->kg_8/8}}
                        </x-td>
                        <x-td class="text-center">
                            {{$valoracion->valor_kg_adicional}}
                        </x-td>
                        <x-td>
                            @if (!$valoracionId)
                            <x-flex>
                                <x-button type="button" wire:click="editarValoracion({{$valoracion->id}})">
                                    <i class="fa fa-edit"></i>
                                </x-button>
                                <x-danger-button type="button" wire:click="preguntarEliminar({{$valoracion->id}})">
                                    <i class="fa fa-trash"></i>
                                </x-danger-button>
                            </x-flex>
                            @endif
                        </x-td>
                    </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="$set('mostrarFormulario',false)"  class="mr-2">Cerrar</x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>