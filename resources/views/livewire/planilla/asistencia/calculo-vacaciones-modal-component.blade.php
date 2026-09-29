<x-dialog-modal wire:model="mostrarModal" maxWidth="2xl">
    <x-slot name="title">Cálculo de vacaciones - {{ $mes }}/{{ $anio }}</x-slot>
    <x-slot name="content">
        <div class="mb-3">
            <x-button wire:click="calcularTodos">
                <i class="fa fa-calculator"></i> Calcular vacaciones
            </x-button>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Días hábiles</th>
                    <th>Vacaciones neto pagadas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $i => $fila)
                    <tr>
                        <td>{{ $fila['nombres'] }}</td>
                        <td>{{ $fila['fecha_inicio'] }}</td>
                        <td>{{ $fila['fecha_fin'] }}</td>
                        <td>{{ $fila['dias_habiles'] }}</td>
                        <td>
                            <input type="number" step="0.01" wire:model="filas.{{ $i }}.vacaciones_neto_pagadas"
                                class="w-24 border rounded px-1">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-slot>
    <x-slot name="footer">
        <x-button wire:click="$set('mostrarModal', false)" variant="secondary">Cancelar</x-button>
        <x-button wire:click="guardar">Guardar</x-button>
    </x-slot>
</x-dialog-modal>