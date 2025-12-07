<div class="my-5">
    <x-table>
        <x-slot name="thead">
            <x-th>Producto</x-th>
            <x-th class="text-center">Código</x-th>
            <x-th class="text-center">Año</x-th>
            <x-th class="text-center">Tipo</x-th>

            <x-th class="text-center">Stock Inicial</x-th>
            <x-th class="text-center">Costo Unitario</x-th>
            <x-th class="text-center">Costo Total</x-th>

            <x-th class="text-center">Stock Final</x-th>
            <x-th class="text-center">Costo Final</x-th>

            <x-th class="text-center">Método</x-th>
            <x-th class="text-center">Estado</x-th>
            <x-th class="text-center">Archivo</x-th>
            <x-th class="text-center">Acciones</x-th>
        </x-slot>

        <x-slot name="tbody">
            @forelse($kardexes as $kardex)
                <x-tr>
                    {{-- Producto --}}
                    <x-td>{{ $kardex->descripcion }}</x-td>

                    {{-- Código existencia --}}
                    <x-td class="text-center">{{ $kardex->codigo_existencia ?? '—' }}</x-td>

                    {{-- Año --}}
                    <x-td class="text-center">{{ $kardex->anio }}</x-td>

                    {{-- Tipo --}}
                    <x-td class="text-center">{{ ucfirst($kardex->tipo) }}</x-td>

                    {{-- Stock inicial --}}
                    <x-td class="text-center">
                        {{ number_format($kardex->stock_inicial, 3) }}
                    </x-td>

                    {{-- Costo unitario --}}
                    <x-td class="text-center">
                        {{ number_format($kardex->costo_unitario, 6) }}
                    </x-td>

                    {{-- Costo total --}}
                    <x-td class="text-center">
                        {{ number_format($kardex->costo_total, 3) }}
                    </x-td>

                    {{-- Stock final --}}
                    <x-td class="text-center">
                        {{ $kardex->stock_final !== null ? number_format($kardex->stock_final, 3) : '—' }}
                    </x-td>

                    {{-- Costo final --}}
                    <x-td class="text-center">
                        {{ $kardex->costo_final !== null ? number_format($kardex->costo_final, 3) : '—' }}
                    </x-td>

                    {{-- Método de valuación --}}
                    <x-td class="text-center">
                        {{ strtoupper($kardex->metodo_valuacion) }}
                    </x-td>

                    {{-- Estado --}}
                    <x-td class="text-center">
                        <span class="{{ $kardex->estado === 'activo' ? 'text-green-600' : 'text-red-600' }}">
                            {{ ucfirst($kardex->estado) }}
                        </span>
                    </x-td>

                    {{-- Archivo --}}
                    <x-td class="text-center">
                        @if ($kardex->file)
                            <a href="{{ asset('storage/' . $kardex->file) }}" class="text-blue-600 underline" target="_blank">
                                Ver
                            </a>
                        @else
                            —
                        @endif
                    </x-td>

                    {{-- Acciones --}}
                    <x-td class="text-center space-x-2">
                        <x-flex class="min-w-[200px]">
                            <x-button href="{{ route('gestion_insumos.kardex.detalle', $kardex->id) }}">
                                <i class="fa fa-link"></i> Ver Kardex
                            </x-button>
                            <x-button variant="danger" wire:click="eliminarInsumoKardex({{ $kardex->id }})" size="xs">
                                <i class="fa fa-trash"></i>
                            </x-button>
                        </x-flex>
                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="14" class="text-center py-4">
                        No hay registros de kardex disponibles.
                    </x-td>
                </x-tr>
            @endforelse
        </x-slot>
    </x-table>

    <div class="mt-4">
        {{ $kardexes->links() }}
    </div>
</div>