<div class="space-y-4">
    <x-flex class="justify-between">
        <div>
            <x-title>Derecho-Habientes</x-title>
            <x-subtitle>Asignación familiar por empleado, según fecha actual</x-subtitle>
        </div>
        <div class="flex gap-2">
            <x-button wire:click="verEstadisticas" variant="secondary">
                <i class="fa fa-chart-bar"></i> Ver estadísticas
            </x-button>
            <x-button @click="$dispatch('abrirDerechoHabienteWizard', { empleadoId: null })" variant="primary">
                <i class="fa fa-plus"></i> Agregar derecho-habiente
            </x-button>
        </div>
    </x-flex>

    <x-card>
        <div class="relative w-full md:w-96 mb-6">
            <x-label value="Buscar empleado" />
            <x-input type="search" wire:model.live.debounce.400ms="search"
                placeholder="Nombres, apellidos o documento" autocomplete="off" />
        </div>

        @can(\App\Constants\Permisos::PLANILLA_FAMILIAR_VER)
            <x-table class="mt-5">
                <x-slot name="thead">
                    <x-tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Empleado" />
                        <x-th value="Cant. Hijos" class="text-center" />
                        <x-th value="Asignación Familiar (hoy)" class="text-center" />
                        <x-th value="Acciones" class="text-center" />
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @forelse ($resumen as $indice => $fila)
                        <x-tr>
                            <x-th value="{{ $resumen->firstItem() + $indice }}" class="text-center" />
                            <x-td value="{{ $fila->empleado->nombreCompleto }}" />
                            <x-td value="{{ $fila->cantidad_hijos }}" class="text-center" />
                            <x-td class="text-center">
                                @if ($fila->tiene_asignacion)
                                    <span class="text-green-600 font-semibold"><i class="fa fa-check-circle"></i> Sí</span>
                                @else
                                    <span class="text-gray-400"><i class="fa fa-times-circle"></i> No</span>
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <x-button size="sm" variant="secondary" wire:click="verDetalle({{ $fila->empleado->id }})" title="Ver detalle">
                                        <i class="fa fa-info-circle"></i> Ver detalle
                                    </x-button>
                                    @can(\App\Constants\Permisos::PLANILLA_FAMILIAR_GESTIONAR)
                                        <x-button size="sm" wire:click="editar({{ $fila->empleado->id }})" title="Editar">
                                            <i class="fa fa-edit"></i> Editar
                                        </x-button>
                                    @endcan
                                </div>
                            </x-td>
                        </x-tr>
                    @empty
                        <x-tr>
                            <x-td colspan="100%" class="text-center">No hay registros.</x-td>
                        </x-tr>
                    @endforelse
                </x-slot>
            </x-table>
            <div class="mt-5">{{ $resumen->links() }}</div>
        @endcan
    </x-card>

    <livewire:gestion-planilla.derecho-habiente.derecho-habiente-wizard-component />
    <livewire:gestion-planilla.derecho-habiente.derecho-habiente-form-component />
    <livewire:gestion-planilla.derecho-habiente.derecho-habiente-detalle-modal-component />
    <livewire:gestion-planilla.derecho-habiente.derecho-habiente-estadisticas-modal-component />
</div>