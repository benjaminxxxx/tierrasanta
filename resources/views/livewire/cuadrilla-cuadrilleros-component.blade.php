<div>
    <x-flex>
        <x-h3>
            Cuadrilleros
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('registrarCuadrillero')">
            <i class="fa fa-plus"></i> Registrar cuadrillero
        </x-button>
    </x-flex>
    <x-card class="mt-3">
        <x-spacing>
            <x-flex class="justify-end">
                <x-toggle-switch :checked="$verEliminados" label="Ver eliminados" wire:model.live="verEliminados" />
            </x-flex>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <x-tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Nombres" />
                        <x-th value="Dni" class="text-center" />
                        <x-th value="Acciones" class="text-center" />
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($cuadrilleros && $cuadrilleros->count() > 0)
                        @foreach ($cuadrilleros as $indice => $cuadrillero)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td value="{{ $cuadrillero->nombres }}" />
                                <x-td value="{{ $cuadrillero->dni }}" class="text-center" />
                                <x-td class="text-center">
                                    <x-flex class="justify-center">
                                        @if ($cuadrillero->estado=='1')
                                            <x-button
                                                @click="$wire.dispatch('editarCuadrillero',{cuadrilleroId:{{ $cuadrillero->id }}})">
                                                <i class="fa fa-edit"></i>
                                            </x-button>
                                            <x-danger-button
                                                wire:click="confirmarEliminarCuadrillero({{ $cuadrillero->id }})">
                                                <i class="fa fa-trash"></i>
                                            </x-danger-button>
                                        @else
                                            <x-secondary-button wire:click="restaurar({{ $cuadrillero->id }})">
                                                Restaurar
                                            </x-secondary-button>
                                        @endif

                                    </x-flex>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Cuadrilleros registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
