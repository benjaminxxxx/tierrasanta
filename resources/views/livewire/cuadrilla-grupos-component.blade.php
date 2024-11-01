<div>
    <div class="flex items-center gap-3">
        <x-h3>Grupos de Cuadrillas</x-h3>
        <x-button type="button" @click="$wire.dispatch('registrarGrupo')">
            <i class="fa fa-plus"></i> Registrar grupo
        </x-button>
    </div>
    <x-card class="mt-5">
        <x-spacing>
            <x-flex class="justify-end">
                <x-toggle-switch :checked="$verEliminados" label="Ver eliminados" wire:model.live="verEliminados" />
            </x-flex>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <x-tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Código del Grupo" />
                        <x-th value="Nombre del Grupo" />
                        <x-th value="Precio Sugerido por Jornal" class="text-center" />
                        <x-th value="Color" class="text-center" />
                        <x-th value="Acciones" class="text-center" />
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($grupos && $grupos->count() > 0)
                        @foreach ($grupos as $indice => $grupo)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td>
                                    <div class="rounded-lg p-2 text-center"
                                        style="background-color:{{ $grupo->color ? $grupo->color : '#ffffff' }}">
                                        {{ $grupo->codigo }}
                                    </div>
                                </x-td>
                                <x-td value="{{ $grupo->nombre }}" />
                                <x-td value="{{ $grupo->costo_dia_sugerido }}" class="text-center" />
                                <x-td>
                                    <div class="rounded-lg p-2 text-center"
                                        style="background-color:{{ $grupo->color ? $grupo->color : '#ffffff' }}">
                                        {{ $grupo->color }}
                                    </div>
                                </x-td>
                                <x-td class="text-center">
                                    <x-flex class="justify-center">
                                        @if ($grupo->estado == '1')
                                            <x-button type="button"
                                                @click="$wire.dispatch('editarGrupo',{codigo:'{{ $grupo->codigo }}'})">
                                                <i class="fa fa-edit"></i>
                                            </x-button>
                                            <x-danger-button type="button"
                                                wire:click="confirmarEliminarGrupo('{{ $grupo->codigo }}')">
                                                <i class="fa fa-trash"></i>
                                            </x-danger-button>
                                        @else
                                            <x-secondary-button wire:click="restaurar('{{ $grupo->codigo }}')">
                                                Restaurar
                                            </x-secondary-button>
                                        @endif
                                    </x-flex>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Grupos registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
