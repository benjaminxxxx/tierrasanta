<div x-data="gestionCuadrilla">
    <x-flex>
        <x-h3>
            Cuadrilleros
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('registrarCuadrillero')">
            <i class="fa fa-plus"></i> Agregar Cuadrillero
        </x-button>
    </x-flex>
    <x-card2 class="mt-3">
        <x-flex class="justify-between">
            <form wire:submit="filtrarCuadrilleros">
                <x-flex class="!items-end">
                    <x-group-field>
                        <x-label>
                            Nombre o Documento
                        </x-label>
                        <x-input type="search" wire:model="nombreDocumentoFiltro" />
                    </x-group-field>
                    <div>
                        <x-select wire:model.live="grupoSeleccionado" label="Grupo">
                            <option value="">TODOS</option>
                            @foreach ($grupoCuadrillas as $grupoCuadrilla)
                                <option value="{{ $grupoCuadrilla->codigo }}">{{ $grupoCuadrilla->nombre }}
                                    ({{ $grupoCuadrilla->cuadrilleros->count() }})
                                </option>
                            @endforeach
                        </x-select>
                    </div>
                    <x-button type="submit">
                        <i class="fa fa-search"></i> Buscar
                    </x-button>
                    <template x-if="nombre || grupo">
                        <x-button type="button" variant="danger" @click="limpiarFiltro()">
                            <i class="fa fa-remove"></i> Limpiar Filtro
                        </x-button>
                    </template>
                </x-flex>
            </form>
            <x-toggle-switch :checked="$verEliminados" label="Ver eliminados" wire:model.live="verEliminados" />
        </x-flex>
        <x-table class="mt-5">
            <x-slot name="thead">
                <x-tr>
                    <x-th value="N°" class="text-center" />
                    <x-th value="Nombres" />
                    <x-th value="Dni" class="text-center" />
                    <x-th value="Grupo actual" class="text-center" />
                    <x-th value="Registros diarios" class="text-center" />
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
                            <x-td value="{{ $cuadrillero->grupo_actual }}" class="text-center" />
                            <x-td value="{{ $cuadrillero->registrosDiarios->count() }}" class="text-center" />
                            <x-td class="text-center">
                                <x-flex class="justify-center">
                                    @if (!$cuadrillero->trashed())
                                        <x-button
                                            @click="$wire.dispatch('editarCuadrillero', { cuadrilleroId: {{ $cuadrillero->id }} })">
                                            <i class="fa fa-edit"></i>
                                        </x-button>

                                        <x-danger-button wire:click="eliminarCuadrillero({{ $cuadrillero->id }})">
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
        <div class="mt-2">
            {{ $cuadrilleros->links() }}
        </div>
    </x-card2>

    <livewire:gestion-cuadrilla.administrar-cuadrillero.cuadrilla-form-component />

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('gestionCuadrilla', () => ({
        nombre: @entangle('nombreDocumentoFiltro'),
        grupo: @entangle('grupoSeleccionado'),
        init() {

        },
        limpiarFiltro() {
            this.nombre = '';
            this.grupo = '';
            $wire.$refresh();
        }
    }));
</script>
@endscript