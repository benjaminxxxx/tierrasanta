<x-dialog-modal wire:model.live="mostrarValidarFechasNacimiento">
    <x-slot name="title">
        Registrar Fechas de Nacimiento - Empleados
    </x-slot>

    <x-slot name="content">
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th>Empleado</x-th>
                    <x-th>Documento</x-th>
                    <x-th>Fecha de Nacimiento</x-th>
                </x-tr>
            </x-slot>

            <x-slot name="tbody">
                @forelse($empleadosSinFechaNacimiento as $empleado)
                    <x-tr>
                        <x-td>
                            {{ $empleado->nombres ?? '' }} {{ $empleado->apellido_paterno ?? '' }} {{ $empleado->apellido_materno ?? '' }}
                        </x-td>
                        <x-td>
                            {{ $empleado->documento ?? 'S/N' }}
                        </x-td>
                        <x-td>
                            <x-label>Fecha Nacimiento</x-label>
                            <x-input type="date" wire:model="fechasNacimiento.{{ $empleado->id }}" wire:key="fecha{{ $empleado->id }}_{{ $registro }}" />
                        </x-td>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="3">
                            Todos los empleados tienen su fecha de nacimiento registrada.
                        </x-td>
                    </x-tr>
                @endforelse
            </x-slot>
        </x-table>
    </x-slot>

    <x-slot name="footer">
        <x-button type="button" variant="secondary" wire:click="$set('mostrarValidarFechasNacimiento', false)">
            Cancelar
        </x-button>
        <x-button wire:click="registrarFechasNacimiento">
            Guardar Cambios
        </x-button>
    </x-slot>
</x-dialog-modal>