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
    <x-card class="mt-3">
        <x-spacing>
            <x-flex>
                <div>
                    <x-select-campo label="Filtrar por Campo" wire:model.live="campoSeleccionado"/>
                </div>
                <div>
                    <x-select-campanias label="Filtrar por Campaña" wire:model.live="campaniaSeleccionado"/>
                </div>
            </x-flex>
        </x-spacing>
    </x-card>
    <x-card class="mt-3">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th class="text-center">
                            Campaña
                        </x-th>                        
                        <x-th class="text-center">
                            Campo
                        </x-th>
                        <x-th class="text-center">
                            Fecha de inicio
                        </x-th>
                        <x-th class="text-center">
                            Fecha de cierre
                        </x-th>
                        <x-th class="text-center">
                            Variedad
                        </x-th>
                        <x-th class="text-center">
                            Sistema de cultivo
                        </x-th>
                        <x-th class="text-center">
                            Pencas x Ha
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($campanias as $indice => $campania)
                        <x-tr>
                            <x-td class="text-center">
                                {{$indice+1}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->nombre_campania}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->campo}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->fecha_inicio}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->fecha_fin}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->variedad_tuna}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->sistema_cultivo}}
                            </x-td>
                            <x-td class="text-center">
                                {{$campania->pencas_x_hectarea}}
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="my-4">
                {{$campanias->links()}}
            </div>
        </x-spacing>
    </x-card>
</div>
