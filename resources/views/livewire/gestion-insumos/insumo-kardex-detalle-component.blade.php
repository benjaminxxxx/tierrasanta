<div>
    <x-card>
        <x-flex class="justify-between">
            <x-title>
                <a href="{{ route('gestion_insumos.kardex') }}"
                    class="underline text-blue-600 dark:text-blue-300">KARDEX</a> /
                KARDEX {{ mb_strtoupper($insumoKardex->tipo) }} {{ mb_strtoupper($insumoKardex->descripcion) }}
                {{ $insumoKardex->anio }}
            </x-title>
            <div class="ms-3 relative">
                <x-dropdown align="right" width="60">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                OPCIONES
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
                            @if ($kardexOpuesto)
                                <x-dropdown-link
                                    href="{{ route('gestion_insumos.kardex.detalle', $kardexOpuesto->id) }}">
                                    Ver Kardex {{ ucfirst($tipoOpuesto) }}
                                </x-dropdown-link>
                            @endif

                            <div x-data="{ openFileDialog() { $refs.fileInputNegro.click() } }">
                                <x-dropdown-link @click="openFileDialog()">
                                    Importar Kardex {{ $insumoKardex->tipo }}
                                </x-dropdown-link>
                                <input type="file"
                                    accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    x-ref="fileInputNegro" style="display: none;"
                                    wire:model.live="archivoExcelKardex" />
                            </div>
                            <x-dropdown-link
                                href="{{ route('gestion_insumos.kardex_asignacion', ['productoId' => $insumoKardex->producto_id, 'anio' => $insumoKardex->anio]) }}"
                                target="_blank">
                                Asignar Salidas
                            </x-dropdown-link>
                            <x-dropdown-link wire:click="generarDetalleKardexInsumo">
                                Generar Resumen
                            </x-dropdown-link>
                            @if ($insumoKardex->file)
                                <x-dropdown-link href="{{ Storage::disk('public')->url($insumoKardex->file) }}">
                                    Descargar Reporte
                                </x-dropdown-link>
                            @endif

                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </x-flex>
        <div class="mt-4">
            {{-- -TABLA --}}
            @include('livewire.gestion-insumos.partials.insumo-kardex-detalle-tabla')
        </div>
    </x-card>
    <x-loading wire:loading />
</div>
