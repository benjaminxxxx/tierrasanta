<div class="space-y-4">
    <div>
        <x-breadcrumb :items="$breadcrumb" />
    </div>

    <x-flex class="justify-between">
        <div>
            <x-title>Administración de Cargos</x-title>
            <x-subtitle>Gestiona los cargos de los empleados</x-subtitle>
        </div>
        <x-button @click="$wire.dispatch('crearCargo')"><i class="fa fa-plus"></i> Nuevo cargo</x-button>
    </x-flex>

    <x-card>
        <x-flex class="justify-between mb-4">
            <x-flex>
                <x-input
                    type="search"
                    placeholder="Buscar cargo..."
                    wire:model="busqueda"
                    class="w-full md:w-72"
                />
                <x-button wire:click="buscar">
                    <i class="fa fa-search"></i> Buscar
                </x-button>
            </x-flex>

            <x-toggle-switch
                :checked="$verEliminados"
                label="Ver eliminados"
                wire:model.live="verEliminados"
            />
        </x-flex>

        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th sortable="nombre">Nombre</x-th>
                    <x-th>Cupo máximo</x-th>
                    <x-th>Trabajadores actuales</x-th>
                    <x-th>Estado</x-th>
                    <x-th sortable="created_at">Creado</x-th>
                    <x-th class="text-right">Acciones</x-th>
                </x-tr>
            </x-slot>

            <x-slot name="tbody">
                @forelse ($cargos as $cargo)
                    <x-tr wire:key="cargo-{{ $cargo->id }}">
                        <x-th>{{ $cargo->nombre }}</x-th>

                        <x-th>{{ $cargo->cupo_maximo ?? 'Sin límite' }}</x-th>

                        <x-th>
                            <span class="font-medium">{{ $cargo->trabajadores_actuales }}</span>
                            @if ($cargo->cupo_maximo)
                                <span class="text-gray-400">/ {{ $cargo->cupo_maximo }}</span>
                            @endif
                        </x-th>

                        <x-th>
                            @if ($cargo->trashed())
                                <span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Eliminado</span>
                            @elseif ($cargo->activo)
                                <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Activo</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">Inactivo</span>
                            @endif
                        </x-th>

                        <x-th>{{  formatear_fecha($cargo->created_at) }}</x-th>

                        <x-th class="text-right">
                            <div class="flex justify-end gap-2">
                                @if ($cargo->trashed())
                                    <x-button variant="secondary" wire:click="restaurar({{ $cargo->id }})">
                                        <i class="fa fa-refresh"></i> Restaurar
                                    </x-button>

                                    <x-button
                                        variant="danger"
                                        wire:click="eliminarDefinitivo({{ $cargo->id }})"
                                        wire:confirm="¿Eliminar definitivamente este cargo? No se puede deshacer."
                                        :disabled="$cargo->total_relaciones > 0"
                                        :title="$cargo->total_relaciones > 0 ? 'Tiene historial asociado' : null"
                                    >
                                        <i class="fa fa-remove"></i> Eliminar definitivo
                                    </x-button>
                                @else
                                    <x-button variant="secondary" wire:click="toggleActivo({{ $cargo->id }})">
                                        {{ $cargo->activo ? 'Desactivar' : 'Activar' }}
                                    </x-button>

                                    <x-button variant="secondary" title="Editar registro" wire:click="editar({{ $cargo->id }})">
                                        <i class="fa fa-edit"></i>
                                    </x-button>

                                    <x-button
                                        variant="danger"
                                        wire:click="eliminar({{ $cargo->id }})"
                                        wire:confirm="¿Eliminar este cargo?"
                                        :disabled="$cargo->total_relaciones > 0"
                                        :title="$cargo->total_relaciones > 0 ? 'Tiene historial asociado' : null"
                                    >
                                        <i class="fa fa-trash"></i>
                                    </x-button>
                                @endif
                            </div>
                        </x-th>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-th colspan="5" class="py-6 text-center text-gray-400">
                            {{ $verEliminados ? 'No hay cargos eliminados.' : 'No hay cargos registrados.' }}
                        </x-th>
                    </x-tr>
                @endforelse
            </x-slot>
        </x-table>

        <div class="mt-4">
            {{ $cargos->links() }}
        </div>
    </x-card>

    <livewire:gestion-planilla.cargo-form-component />
</div>