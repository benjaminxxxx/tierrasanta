<div>
    <x-flex>
        <x-h3>
            Labores
        </x-h3>
    </x-flex>
    <x-card class="mt-3">
        <x-spacing>
            <!-- Título para agregar nueva labor -->
            <form class="space-y-2" wire:submit="agregarLabor">

                @if ($laborId)
                    <x-label for="nuevaLabor" value="Editar Labor" />
                @else
                    <x-label for="nuevaLabor" value="Agregar Nueva Labor" />
                @endif
                <div class="flex items-center">
                    <x-input id="nuevaLabor" required autocomplete="off" wire:model="nuevaLabor" type="text"
                        class="!w-auto mr-3" placeholder="Nombre de la nueva labor" autofocus />
                    <x-button type="submit" wire:loading.attr="disabled">

                        @if ($laborId)
                            <i class="fa fa-pencil"></i> Editar Labor
                        @else
                            <i class="fa fa-plus"></i> Nueva Labor
                        @endif
                    </x-button>
                </div>
            </form>
        </x-spacing>
    </x-card>
    <x-card class="mt-3">
        <x-spacing>
            
            <x-flex class="justify-between">
                <x-flex>
                    <div>
                        <x-label for="search">Buscar</x-label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                                <i class="fa fa-search"></i>
                            </div>
                            <x-input type="search" wire:model.live="search" id="default-search" class="w-full !pl-10"
                                autocomplete="off" placeholder="Busca por Nombre de la labor aqui." required />
                        </div>
                    </div>
                    <div>
                        <x-label for="conBono">Recibe Bono</x-label>
                        <x-select wire:model.live="conBono">
                            <option value="">Todos</option>
                            <option value="si">Activado para Bono</option>
                            <option value="no">Desactivado para Bono</option>
                        </x-select>
                    </div>
                </x-flex>
                <x-toggle-switch :checked="$verEliminados" label="Ver eliminados" wire:model.live="verEliminados" />
            </x-flex>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <x-tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Nombre de la Labor" />
                        <x-th value="Activado para Recibir Bono" class="text-center" />
                        <x-th value="Valoración actual" class="text-center" />
                        <x-th value="Acciones" class="text-center" />
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($labores && $labores->count() > 0)
                        @foreach ($labores as $indice => $labor)
                            <x-tr>
                                <x-th value="{{ $labor->id }}" class="text-center" />
                                <x-td value="{{ $labor->nombre_labor }}" />
                                <x-td value="{{ $labor->tiene_bono }}" class="text-center" />
                                <x-td value="{{ $labor->valoracion_actual }}" class="text-center" />
                                <x-td class="text-center">
                                    <x-flex class="justify-end">
                                        @if ($labor->estado == '1')
                                            @if ($labor->bono == '1')
                                                <x-success-button wire:click="toggleBono({{ $labor->id }},0)">
                                                    <i class="fa fa-check"></i> Desactivar de Bonos
                                                </x-success-button>
                                            @else
                                                <x-warning-button wire:click="toggleBono({{ $labor->id }},1)">
                                                    Activar en Bonos
                                                </x-warning-button>
                                            @endif
                                            <x-secondary-button
                                                @click="$wire.dispatch('listarValoracionLabor',{laborId:{{ $labor->id }}})">
                                                <i class="fa fa-list"></i> Valoración
                                            </x-secondary-button>
                                            <x-button wire:click="editarLabor({{ $labor->id }})">
                                                <i class="fa fa-edit"></i>
                                            </x-button>
                                            <x-danger-button wire:click="confirmarEliminarLabor({{ $labor->id }})">
                                                <i class="fa fa-trash"></i>
                                            </x-danger-button>
                                        @else
                                            <x-secondary-button wire:click="restaurar({{ $labor->id }})">
                                                Restaurar
                                            </x-secondary-button>
                                        @endif

                                    </x-flex>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Labores registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
            <div class="my-5">
                {{ $labores->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
