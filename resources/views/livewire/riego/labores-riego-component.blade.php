<div class="space-y-4">
    <x-flex class="justify-between items-end flex-wrap gap-3">
        <x-title>
            Labores de Riego
        </x-title>

        @can(\App\Constants\Permisos::CAMPO_RIEGO_LABOR_GESTIONAR)
            <x-button wire:click="abrirCrear" wire:loading.attr="disabled">
                <i class="fa fa-plus"></i> Nueva Labor
            </x-button>
        @endcan
    </x-flex>

    <x-card class="mt-4">
        <x-flex class="gap-4 flex-wrap items-end">
            <x-input type="search" label="Buscar labor" wire:model.live.debounce.400ms="busqueda"
                placeholder="Nombre de la labor..." class="w-auto" />

            <x-select label="Filtrar por tipo" wire:model.live="filtroTipo" class="w-auto">
                <option value="">Todas</option>
                <option value="riego">Solo Riego</option>
                <option value="apoyo">Solo Apoyo en Riego</option>
                <option value="ninguno">Sin clasificar</option>
            </x-select>
        </x-flex>
    </x-card>

    @can(\App\Constants\Permisos::CAMPO_RIEGO_LABOR_VER)
        <x-card class="mt-4">
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="!text-left">Labor</x-th>
                        <x-th class="text-center">¿Es Riego?</x-th>
                        <x-th class="text-center">¿Es Apoyo en Riego?</x-th>
                        <x-th class="text-center">Consumo (m³/hora)</x-th>
                        <x-th class="text-center">Acciones</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @forelse ($labores as $labor)
                        <x-tr wire:key="labor-{{ $labor->id }}">
                            <x-td class="!text-left font-medium">{{ $labor->nombre_labor }}</x-td>
                            <x-td class="text-center">
                                @if ($labor->es_riego)
                                    <span class="text-green-600 dark:text-green-400"><i class="fa fa-check-circle"></i></span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($labor->es_apoyo_riego)
                                    <span class="text-blue-600 dark:text-blue-400"><i class="fa fa-check-circle"></i></span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </x-td>
                            <x-td class="text-center">{{ $labor->consumo_m3_hora ?? '—' }}</x-td>
                            <x-td class="text-center">
                                @can(\App\Constants\Permisos::CAMPO_RIEGO_LABOR_GESTIONAR)
                                    <div class="flex items-center justify-center gap-2">
                                        <x-button variant="secondary" wire:click="abrirEditar({{ $labor->id }})" wire:loading.attr="disabled">
                                            <i class="fa fa-edit"></i>
                                        </x-button>
                                        <x-button variant="danger"
                                            wire:confirm="¿Estás seguro que deseas eliminar la labor '{{ $labor->nombre_labor }}'?"
                                            wire:click="eliminarLabor({{ $labor->id }})" wire:loading.attr="disabled">
                                            <i class="fa fa-trash"></i>
                                        </x-button>
                                    </div>
                                @endcan
                            </x-td>
                        </x-tr>
                    @empty
                        <x-tr>
                            <x-td colspan="5" class="text-center text-muted-foreground py-6">
                                No se encontraron labores con esos filtros.
                            </x-td>
                        </x-tr>
                    @endforelse
                </x-slot>
            </x-table>

            <div class="mt-4">
                {{ $labores->links() }}
            </div>
        </x-card>
    @else
        <x-danger>
            No tienes permisos para ver las labores de riego. Por favor, contacta al administrador.
        </x-danger>
    @endcan

    {{-- Modal único: sirve para CREAR y EDITAR --}}
    <x-dialog-modal wire:model.live="mostrarModal">
        <x-slot name="title">
            {{ $laborEditandoId ? 'Editar Labor' : 'Nueva Labor' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="nombreLabor" value="Nombre de la labor" />
                    <x-input id="nombreLabor" type="text" wire:model="nombreLabor" class="w-full mt-1" autofocus />
                    <x-input-error for="nombreLabor" class="mt-1" />
                </div>

                <div>
                    <x-label class="block mb-2">Clasificación</x-label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="tipoLabor" value="" wire:model.live="tipoLabor" class="h-4 w-4" />
                            <span>Ninguno (labor normal, sin restricciones de cruce)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="tipoLabor" value="riego" wire:model.live="tipoLabor" class="h-4 w-4" />
                            <span>Riego (consume agua, no puede cruzarse con otro riego en el mismo campo)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="tipoLabor" value="apoyo" wire:model.live="tipoLabor" class="h-4 w-4" />
                            <span>Apoyo en Riego (debe coincidir con un riego real en ese horario)</span>
                        </label>
                    </div>
                    <x-input-error for="tipoLabor" class="mt-1" />
                </div>

                @if ($tipoLabor === 'riego')
                    <div>
                        <x-label for="consumoM3Hora" value="Consumo de agua (m³ por hora)" />
                        <x-input id="consumoM3Hora" type="number" step="0.01" wire:model="consumoM3Hora" class="w-full mt-1" />
                        <x-input-error for="consumoM3Hora" class="mt-1" />
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarModal', false)" wire:loading.attr="disabled">
                Cerrar
            </x-button>
            <x-button wire:click="guardar" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> {{ $laborEditandoId ? 'Guardar Cambios' : 'Crear Labor' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>