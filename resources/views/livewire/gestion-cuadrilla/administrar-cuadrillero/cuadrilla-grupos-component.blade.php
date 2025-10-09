<div>
    <div class="flex items-center gap-3">
        <x-h3>Grupos de Cuadrillas</x-h3>
        <x-button type="button" @click="$wire.dispatch('registrarGrupo')">
            <i class="fa fa-plus"></i> Registrar grupo
        </x-button>
    </div>
    <x-card2 class="mt-5">
        <x-flex class="justify-end">
            <x-toggle-switch :checked="$verEliminados" label="Ver eliminados" wire:model.live="verEliminados" />
        </x-flex>
        <x-table class="mt-5">
            <x-slot name="thead">
                <x-tr>
                    <x-th value="N°" class="text-center" />
                    <x-th value="Código" class="text-center" />
                    <x-th value="Descripción" />
                    <x-th value="Cuadrilleros activos" class="text-center" />
                    <x-th value="Fechas trabajadas" class="text-center" />
                    <x-th value="Costo Día" class="text-center" />
                    <x-th value="Color" class="text-center" />
                    <x-th value="Creado en" class="text-center" />
                    <x-th value="Acciones" class="text-center" />
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @foreach ($grupos as $indice => $grupo)
                    <x-tr>
                        <x-th value="{{ $indice + 1 }}" class="text-center" />
                        <x-td>
                            <div class="rounded-lg p-2 text-center text-black text-lg font-bold"
                                style="background-color:{{ $grupo->color ? $grupo->color : '#ffffff' }}">
                                {{ $grupo->codigo }}
                            </div>
                        </x-td>
                        <x-td value="{{ $grupo->nombre }}" />
                        <x-td value="{{ $grupo->cuadrilleros->count() }}" class="text-center" />
                        <x-td value="0" class="text-center" />
                        <x-td value="{{ $grupo->costo_dia_sugerido }}" class="text-center text-black  text-lg font-bold" />
                        <x-td>
                            <div class="rounded-lg p-2 text-center text-black text-lg font-bold"
                                style="background-color:{{ $grupo->color ? $grupo->color : '#ffffff' }}">
                                {{ $grupo->color }}
                            </div>
                        </x-td>
                        <x-td class="text-center">
                            {{ formatear_fecha($grupo->created_at) }}
                        </x-td>
                        <x-td class="text-center">
                            <x-flex class="justify-center">
                                @if (!$grupo->trashed())
                                    <x-button type="button"
                                        @click="$wire.dispatch('editarGrupo', { codigo: '{{ $grupo->codigo }}' })">
                                        <i class="fa fa-edit"></i>
                                    </x-button>

                                    <x-danger-button type="button" wire:click="eliminarGrupoCuadrilla('{{ $grupo->codigo }}')">
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
            </x-slot>
        </x-table>
        <div class="mt-2">
            {{ $grupos->links() }}
        </div>
    </x-card2>

    <livewire:gestion-cuadrilla.administrar-cuadrillero.cuadrilla-grupo-form-component />

    <x-loading wire:loading />
</div>