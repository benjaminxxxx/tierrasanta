<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Registro de Factor para el Avance de Productividad
        </x-slot>

        <x-slot name="content">
            <div>
                <x-input wire:model="nombre" class="mb-4" placeholder="Ejemplo: factor estandar" />
                <x-input-error for="nombre" />
            </div>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            Labor
                        </x-th>
                        <x-th class="text-center">
                            KG.(8 hrs.)
                        </x-th>
                        <x-th class="text-center">
                            KG/H
                        </x-th>
                        <x-th class="text-center">
                            Valor /KG Adicional
                        </x-th>
                        <x-th class="text-center">

                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($factores as $indice => $factor)
                        <x-tr>
                            <x-td class="text-center">
                                {{$factor['nombreLabor']}}
                            </x-td>
                            <x-td class="text-center">
                                {{$factor['kg_8']}}
                            </x-td>
                            <x-td class="text-center">
                                {{$factor['kg_8']/8}}
                            </x-td>
                            <x-td class="text-center">
                                {{$factor['valor_adicional']}}
                            </x-td>
                            <x-td class="text-center">
                                <x-danger-button type="button" wire:click="quitarFactorDetalle({{ $indice }})">
                                    <i class="fa fa-trash"></i>
                                </x-danger-button>
                            </x-td>
                        </x-tr>
                    @endforeach
                    <x-tr>
                        <x-td class="text-center">
                            <x-select type="number" wire:model="laborSeleccionada">
                                @foreach ($labores as $labor)
                                    <option value="{{ $labor->id }}">{{ mb_strtoupper($labor->nombre_labor) }}
                                    </option>
                                @endforeach
                            </x-select>
                            <x-input-error for="laborSeleccionada" />
                        </x-td>
                        <x-td class="text-center">
                            <x-input type="number" wire:model="kg_8" />
                            <x-input-error for="kg_8" />
                        </x-td>
                        <x-td class="text-center">
                            -
                        </x-td>
                        <x-td class="text-center">
                            <x-input type="number" wire:model="valor_adicional" />
                            <x-input-error for="valor_adicional" />
                        </x-td>
                        <x-td class="text-center">
                            <x-secondary-button type="button" wire:click="agregarFactorDetalle">
                                <i class="fa fa-plus"></i> Agregar
                            </x-secondary-button>
                        </x-td>
                    </x-tr>
                </x-slot>
            </x-table>
        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="confirmarRegistro" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Confirmar Registro
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
</div>
