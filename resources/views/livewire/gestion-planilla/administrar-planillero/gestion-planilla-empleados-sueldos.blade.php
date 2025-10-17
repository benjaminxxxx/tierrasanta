<div>
    <x-dialog-modal wire:model="mostrarFormularioEmpleadoSueldo" maxWidth="full">
        <x-slot name="title">
            <x-h3>Historial de Sueldos del Empleado</x-h3>
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
                                    <x-button variant="danger" size="xs" wire:click="eliminarSueldo({{ $sueldo->id }})"
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
            <x-card class="bg-gray-50 border border-gray-300 p-4">
                <x-h4 class="mb-3">Registrar nuevo sueldo</x-h4>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <x-group-field>
                        <x-label for="fechaInicio">Fecha de inicio</x-label>
                        <x-input type="date" wire:model="fechaInicio" id="fechaInicio" />
                        <x-input-error for="fechaInicio" />
                    </x-group-field>

                    <x-group-field>
                        <x-label for="fechaFin">Fecha de fin (opcional)</x-label>
                        <x-input type="date" wire:model="fechaFin" id="fechaFin" />
                        <x-input-error for="fechaFin" />
                    </x-group-field>

                    <x-group-field>
                        <x-label for="sueldo">Monto del Sueldo</x-label>
                        <x-input type="number" step="0.01" wire:model="sueldo" id="sueldo" />
                        <x-input-error for="sueldo" />
                    </x-group-field>
                </div>
            </x-card>

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