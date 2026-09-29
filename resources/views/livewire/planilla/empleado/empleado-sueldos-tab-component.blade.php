<div class="space-y-6">
    <x-table>
        <x-slot name="thead">
            <x-tr>
                <x-th class="text-center">Inicio</x-th>
                <x-th class="text-center">Fin</x-th>
                <x-th class="text-center">Sueldo</x-th>
                <x-th class="text-center">Variación</x-th>
                <x-th class="text-center">Registrado por</x-th>
                <x-th class="text-center">Acciones</x-th>
            </x-tr>
        </x-slot>
        <x-slot name="tbody">
            @forelse ($sueldos as $i => $s)
                @php $anterior = $sueldos[$i + 1] ?? null; $dif = $anterior ? $s->sueldo - $anterior->sueldo : null; @endphp
                <x-tr wire:key="sueldo-{{ $s->id }}">
                    <x-td class="text-center">{{ formatear_fecha($s->fecha_inicio) }}</x-td>
                    <x-td class="text-center">{{ $s->fecha_fin ? formatear_fecha($s->fecha_fin) : '—' }}</x-td>
                    <x-td class="text-center font-semibold">S/ {{ number_format($s->sueldo, 2) }}</x-td>
                    <x-td class="text-center text-xs">
                        @if ($dif !== null)
                            <span class="{{ $dif >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                {{ $dif >= 0 ? '+' : '' }}{{ number_format($dif, 2) }}
                            </span>
                        @else
                            —
                        @endif
                    </x-td>
                    <x-td class="text-center">{{ $s->creador?->name ?? '—' }}</x-td>
                    <x-td class="text-center">
                        <x-button variant="danger" size="xs" wire:click="eliminarSueldo({{ $s->id }})"
                            wire:confirm="¿Desea eliminar este registro de sueldo?">
                            <i class="fa fa-trash"></i>
                        </x-button>
                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="6" class="text-center text-muted-foreground">No hay sueldos registrados</x-td>
                </x-tr>
            @endforelse
        </x-slot>
    </x-table>

    <div class="rounded-lg border border-border p-4">
        <x-subtitle class="mb-3">Registrar nuevo sueldo</x-subtitle>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <x-selector-dia type="date" label="Fecha de inicio (día 1)" wire:model="fechaInicio" id="fechaInicio-{{ $empleadoId }}" />
                <x-input-error for="fechaInicio" />
            </div>
            <x-selector-dia type="date" label="Fecha de fin (opcional)" wire:model="fechaFin" id="fechaFin-{{ $empleadoId }}" />
            <x-input type="number" label="Monto del sueldo" step="0.01" wire:model="sueldo" error="sueldo" />
        </div>
        <div class="flex justify-end mt-4">
            <x-button wire:click="guardarSueldo" wire:loading.attr="disabled"><i class="fa fa-save"></i> Guardar nuevo sueldo</x-button>
        </div>
    </div>
</div>
