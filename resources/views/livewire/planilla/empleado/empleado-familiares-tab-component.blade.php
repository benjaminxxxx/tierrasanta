<div class="space-y-4">
    <div class="flex items-center justify-between">
        <x-subtitle>Derecho habientes</x-subtitle>
        <x-button size="sm" @click="$wire.dispatch('abrirDerechoHabienteWizard', { empleadoId: {{ $empleadoId }} })">
            <i class="fa-solid fa-people-roof"></i> Gestionar derecho habientes
        </x-button>
    </div>

    <x-table>
        <x-slot name="thead">
            <x-tr>
                <x-th>Nombre</x-th>
                <x-th>Documento</x-th>
                <x-th>Nacimiento</x-th>
                <x-th>Rol</x-th>
                <x-th>Vigencia desde</x-th>
                <x-th>Estado</x-th>
            </x-tr>
        </x-slot>
        <x-slot name="tbody">
            @forelse ($vinculos as $v)
                <x-tr wire:key="vinculo-{{ $v->id }}">
                    <x-td class="font-medium">{{ $v->derechoHabiente?->nombres ?? '—' }}</x-td>
                    <x-td>{{ $v->derechoHabiente?->documento ?? '—' }}</x-td>
                    <x-td>{{ $v->derechoHabiente?->fecha_nacimiento ? formatear_fecha($v->derechoHabiente->fecha_nacimiento) : '—' }}</x-td>
                    <x-td>{{ ucfirst((string) $v->rol) }}</x-td>
                    <x-td>{{ $v->mes_vigencia && $v->anio_vigencia ? sprintf('%02d/%04d', $v->mes_vigencia, $v->anio_vigencia) : '—' }}</x-td>
                    <x-td>
                        @if ($v->activo)
                            <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Activo</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs bg-muted text-muted-foreground" title="{{ $v->motivo_inactivacion }}">Inactivo</span>
                        @endif
                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="6" class="py-6 text-center text-muted-foreground">Sin derecho habientes registrados.</x-td>
                </x-tr>
            @endforelse
        </x-slot>
    </x-table>
</div>
