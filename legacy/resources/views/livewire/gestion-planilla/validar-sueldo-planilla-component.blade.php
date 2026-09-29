<x-dialog-modal wire:model.live="mostrarValidarSueldosEmpleados">
    <x-slot name="title">
        Registrar sueldos - Empleados sin sueldo
    </x-slot>
    <x-slot name="content">
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th>Empleado</x-th>
                    <x-th>Sueldo</x-th>
                    <x-th>Inicio Vigencia</x-th>
                </x-tr>
            </x-slot>

            <x-slot name="tbody">
                @forelse($empleadosSinSueldo as $empleado)
                    <x-tr>
                        <x-td>
                            {{ $empleado->nombres ?? '' }} {{ $empleado->apellido_paterno ?? '' }}
                            {{ $empleado->apellido_materno ?? '' }}
                        </x-td>
                        <x-td>
                            <x-label>Sueldo</x-label>
                            <x-input type="number" step="0.01" min="0" wire:model="sueldos.{{ $empleado->id }}" />
                        </x-td>
                        <x-td>
                            <x-label>Inicio vigencia</x-label>
                            <x-input type="date" wire:model="fechas.{{ $empleado->id }}" />
                        </x-td>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="3">
                            No hay empleados sin sueldo.
                        </x-td>
                    </x-tr>
                @endforelse
            </x-slot>
        </x-table>
    </x-slot>
    <x-slot name="footer">
        <x-button type="button" variant="secondary" wire:click="$set('mostrarValidarSueldosEmpleados', false)">
            Cancelar
        </x-button>
        <x-button wire:click="registrarSueldos">
            Registrar sueldos
        </x-button>
    </x-slot>
</x-dialog-modal>