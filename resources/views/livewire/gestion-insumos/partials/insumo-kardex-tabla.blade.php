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
                            <a href="{{ asset('storage/' . $kardex->file) }}" class="text-blue-600 underline"
                                target="_blank">
                                Ver
                            </a>
                        @else
                            —
                        @endif
                    </x-td>

                    {{-- Acciones --}}
                    <x-td class="text-center space-x-2">
                        <div class="ms-3 relative">
                            <x-dropdown align="right" width="60">
                                <x-slot name="trigger">
                                    <span class="inline-flex rounded-md">
                                        <button type="button"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                            Opciones

                                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                            </svg>
                                        </button>
                                    </span>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="w-60">

                                        <!-- Team Settings -->
                                        <x-dropdown-link
                                            href="{{ route('gestion_insumos.kardex.detalle', $kardex->id) }}">
                                            Ver Kardex
                                        </x-dropdown-link>
                                        <x-dropdown-link href="{{ route('gestion_insumos.kardex_asignacion',['productoId' => $kardex->producto_id, 'anio' => $kardex->anio]) }}" target="_blank">
                                Asignar Salidas
                            </x-dropdown-link>
                                        <x-dropdown-link wire:click="eliminarInsumoKardex({{ $kardex->id }})">
                                            Eliminar Kardex
                                        </x-dropdown-link>
                                    </div>
                                </x-slot>
                            </x-dropdown>
                        </div>
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
