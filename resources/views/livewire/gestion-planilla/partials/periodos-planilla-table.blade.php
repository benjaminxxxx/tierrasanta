<x-card>
    {{-- Tabla --}}
    <x-table>
        {{-- THEAD --}}
        <x-slot name="thead">
            <x-tr>
                <x-th>Empleado</x-th>
                <x-th>Tipo</x-th>
                <x-th>Período</x-th>
                <x-th>Días</x-th>
                <x-th>Observaciones</x-th>
                <x-th class="text-right">Acciones</x-th>
            </x-tr>
        </x-slot>

        {{-- TBODY --}}
        <x-slot name="tbody">
            @forelse ($periodos as $periodo)
                <x-tr>
                    {{-- Empleado --}}
                    <x-td>
                        <span class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $periodo->empleado->nombres }}
                        </span>
                    </x-td>

                    {{-- Tipo --}}
                    <x-td>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full border border-black/10"
                                style="background-color: {{ $periodo->tipo_color }}"></span>

                            <span class="text-sm font-medium">
                                {{ $periodo->tipo_label }}
                            </span>
                        </div>
                    </x-td>


                    {{-- Período --}}
                    <x-td>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fa fa-calendar"></i>
                            <span>
                                {{ formatear_fecha($periodo->fecha_inicio) }}
                                -
                                {{ formatear_fecha($periodo->fecha_fin) }}
                            </span>
                        </div>
                    </x-td>

                    {{-- Días --}}
                    <x-td>
                        {{ $periodo->total_dias }}
                    </x-td>

                    {{-- Observaciones --}}
                    <x-td>
                        {{ $periodo->observaciones ?: '—' }}
                    </x-td>

                    {{-- Acciones --}}
                    <x-td class="text-right">
                        <div class="flex justify-end gap-2">
                            <x-button wire:click="editarPeriodo({{ $periodo->id }})" title="Editar período">
                                <i class="fa fa-edit"></i>
                            </x-button>

                            <x-button variant="danger" title="Eliminar período"
                                @click="confirmarEliminacion({{ $periodo->id }})">
                                <i class="fa fa-trash"></i>
                            </x-button>

                        </div>
                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="6" class="py-12 text-center text-gray-500">
                        No hay períodos registrados aún
                    </x-td>
                </x-tr>
            @endforelse
        </x-slot>
    </x-table>
    <div class="mt-5">
        {{ $periodos->links() }}
    </div>
</x-card>
