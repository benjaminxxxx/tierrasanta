<div>
    <x-dialog-modal wire:model="mostrarFormularioEmpleadoSueldo" maxWidth="full">
        <x-slot name="title">
            Historial de Sueldos del Empleado
        </x-slot>

        <x-slot name="content">

            {{-- 🔹 Tabla de sueldos existentes --}}
            <div class="overflow-x-auto mb-6">
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th class="text-center">Inicio</x-th>
                            <x-th class="text-center">Fin</x-th>
                            <x-th class="text-center">Sueldo</x-th>
                            <x-th class="text-center">Registrado por</x-th>
                            <x-th class="text-center">Acciones</x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @forelse ($sueldos as $sueldo)
                            <x-tr class="{{ $loop->last ? '' : 'border-b' }}">
                                <x-td class="text-center">{{ $sueldo->fecha_inicio }}</x-td>
                                <x-td class="text-center">{{ $sueldo->fecha_fin ?? '—' }}</x-td>
                                <x-td class="text-center font-semibold">S/ {{ number_format($sueldo->sueldo, 2) }}
                                </x-td>
                                <x-td class="text-center">{{ $sueldo->creador?->name ?? '—' }}</x-td>
                                <x-td class="text-center">
                                    <x-button variant="danger" size="xs"
                                        wire:click="eliminarSueldo({{ $sueldo->id }})"
                                        wire:confirm="¿Desea eliminar este registro de sueldo?">
                                        <i class="fa fa-trash"></i>
                                    </x-button>
                                </x-td>
                            </x-tr>
                        @empty
                            <x-tr>
                                <x-td colspan="5" class="text-center text-gray-500">
                                    No hay sueldos registrados
                                </x-td>
                            </x-tr>
                        @endforelse
                    </x-slot>
                </x-table>
            </div>

            {{-- 🔹 Formulario para agregar nuevo sueldo --}}
            <div class="p-4">
                <x-h4 class="mb-3">Registrar nuevo sueldo</x-h4>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                    <div>
                        <x-selector-dia type="date" label="Fecha de inicio" wire:model="fechaInicio"
                            id="fechaInicio" />
                        <x-input-error for="fechaInicio" />
                    </div>

                    <x-selector-dia type="date" label="Fecha de fin (opcional)" wire:model="fechaFin" id="fechaFin"
                        error="fechaFin" />

                    <x-input type="number" label="Monto del Sueldo" step="0.01" wire:model="sueldo" id="sueldo" error="sueldo" />
                </div>
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-button type="button" variant="secondary"
                    @click="$wire.set('mostrarFormularioEmpleadoSueldo', false)">Cerrar</x-button>

                <x-button type="submit" wire:click="guardarSueldo">
                    <i class="fa fa-save"></i> Guardar nuevo sueldo
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
