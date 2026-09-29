<div class="my-5">
    <x-table>
        <x-slot name="thead">
            <x-th value="Producto" sortable="codigo_existencia" :active="$sortField === 'codigo_existencia'"
                :direction="$sortDirection" />

            <x-th class="text-center">Año</x-th>
            <x-th value="Tipo" sortable="tipo" :active="$sortField === 'tipo'" :direction="$sortDirection" />


            <x-th class="text-center">Stock Inicial</x-th>
            <x-th class="text-center">Costo Unitario</x-th>
            <x-th class="text-center">Costo Total</x-th>
            <x-th class="text-center">Stock Actual</x-th>
            <x-th class="text-center">Stock Final</x-th>
            <x-th class="text-center">Costo Final</x-th>

            <x-th class="text-center">Método</x-th>
            <x-th class="text-center">Estado</x-th>
            <x-th class="text-center">Archivo</x-th>
            <x-th class="text-center">Fecha de corte</x-th>
            <x-th class="text-center">Acciones</x-th>
        </x-slot>

        <x-slot name="tbody">
            @php
                $disk = Storage::disk('public');
            @endphp
            @forelse($kardexes as $kardex)
                <x-tr>
                    {{-- Producto --}}
                    <x-td class="!text-left">
                        <a href="{{ route('almacen.kardex.detalle', $kardex->id) }}">
                            {{ $kardex->codigo_existencia ?? '—' }} - {{ $kardex->descripcion }}
                        </a>
                    </x-td>

                    {{-- Año --}}
                    <x-td class="text-center">{{ $kardex->anio }}</x-td>

                    {{-- Tipo --}}
                    <x-td class="text-center">{{ ucfirst($kardex->tipo) }}</x-td>

                    {{-- Stock inicial --}}
                    <x-td class="text-center">
                        <div>
                            {{ number_format($kardex->stock_inicial, 3) }}
                        </div>
                        @if ($kardex->comprobante_texto)
                            <div class="text-xs text-gray-500">
                                {{ $kardex->comprobante_texto }}
                            </div>
                        @endif
                    </x-td>

                    {{-- Costo unitario --}}
                    <x-td class="text-center">
                        {{ number_format($kardex->costo_unitario, 6) }}
                    </x-td>

                    {{-- Costo total --}}
                    <x-td class="text-center">
                        {{ number_format($kardex->costo_total, 3) }}
                    </x-td>

                    {{-- Stock actual (stocks_productos): solo para el año vigente --}}
                    <x-td class="text-center">
                        @if ((int) $kardex->anio === $anioVigente)
                            @php $actual = $stockActual["{$kardex->producto_id}|{$kardex->tipo}"] ?? 0; @endphp
                            <span @class([
                                'text-amber-600 dark:text-amber-400 font-semibold' =>
                                    $kardex->stock_final !== null && abs($actual - (float) $kardex->stock_final) > 0.001,
                            ]) title="Si difiere del stock final, genera los movimientos del kardex para recalibrar">
                                {{ number_format($actual, 3) }}
                            </span>
                        @else
                            <span class="text-muted-foreground" title="El stock actual solo aplica al año vigente">—</span>
                        @endif
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
                        @if ($kardex->file && $disk->exists($kardex->file))
                            <a href="{{ $disk->url($kardex->file) }}" class="text-blue-600 underline" target="_blank">
                                Ver
                            </a>
                        @else
                            —
                        @endif
                    </x-td>

                    {{-- Fecha de corte: hasta cuándo el costo de sus salidas es confiable --}}
                    @php $motivo = $motivosDesactualizado[$kardex->id] ?? null; @endphp
                    <x-td class="text-center text-xs whitespace-nowrap">
                        @if ($kardex->movimientos_actualizados_at)
                            <div>{{ $kardex->movimientos_actualizados_at->format('d/m/Y H:i') }}</div>
                        @endif
                        @if ($motivo === null)
                            <span class="text-green-600 font-semibold"><i class="fa fa-check-circle"></i> Al día</span>
                        @else
                            <span class="text-amber-600 dark:text-amber-400 font-semibold" title="{{ ucfirst($motivo) }}">
                                <i class="fa fa-exclamation-triangle"></i> Desactualizado
                            </span>
                            <div class="text-muted-foreground whitespace-normal max-w-[14rem] mx-auto">{{ ucfirst($motivo) }}</div>
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

                                            <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                            </svg>
                                        </button>
                                    </span>
                                </x-slot>

                                <x-slot name="content">
                                    <div class="w-60">

                                        <!-- Team Settings -->
                                        <x-dropdown-link href="{{ route('almacen.kardex.detalle', $kardex->id) }}">
                                            Ver Kardex
                                        </x-dropdown-link>
                                        @if ($kardex->estado !== 'cerrado')
                                            @can(\App\Constants\Permisos::INSUMO_KARDEX_CREAR)
                                                <x-dropdown-link class="cursor-pointer"
                                                    @click="$wire.dispatch('editarInsumoKardex', { kardexId: {{ $kardex->id }} })">
                                                    Editar Kardex
                                                </x-dropdown-link>
                                            @endcan
                                        @endif
                                        {{-- LEGACY: "Asignar Entradas y Salidas (paso 2)" movido a legacy/ (usaba compra_productos) --}}
                                        @can(\App\Constants\Permisos::INSUMO_KARDEX_ELIMINAR)
                                            <x-dropdown-link wire:click="eliminarInsumoKardex({{ $kardex->id }})">
                                                Eliminar Kardex
                                            </x-dropdown-link>
                                        @endcan
                                    </div>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="15" class="text-center py-4">
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