<div>
    <x-card>
        <x-flex class="justify-between">
            <x-flex>
                <x-title>
                    <a href="{{ route('gestion_insumos.kardex.reportes') }}"
                        class="underline text-blue-600 dark:text-blue-300">REPORTES DE KARDEX</a> /
                    {{ mb_strtoupper($insumoKardexReporte->nombre) }}
                </x-title>
                <div>
                    <x-button wire:click="procesarKardexConsolidado">
                        <i class="fa fa-check"></i> Generar Resumen
                    </x-button>
                </div>
            </x-flex>
            <x-flex class="uppercase">
                <x-badge color="blue">
                    KARDEX {{ $insumoKardexReporte->tipo_kardex }}
                </x-badge>

                <x-badge color="gray">
                    {{ $insumoKardexReporte->anio }}
                </x-badge>

                @foreach ($insumoKardexReporte->categorias as $categoria)
                    <x-badge color="purple">
                        CAT: {{ $categoria->categoria_codigo }}
                    </x-badge>
                @endforeach
            </x-flex>


        </x-flex>
        <x-table class="my-4">
            <x-slot name="thead">
                <x-tr>
                    <x-th colspan="2" style="background-color:#31869B" class="px-4 py-2 text-white font-bold text-lg">

                        ÍNDICE DE

                        @foreach ($insumoKardexReporte->categorias as $categoria)
                            @php
                                // Buscar categoría por código
                                $cat = \App\Models\InsCategoria::where('codigo', $categoria->categoria_codigo)->first();
                                $texto = $cat ? $cat->descripcion : strtoupper($categoria->categoria_codigo);
                            @endphp

                            {{ $texto }}@if(!$loop->last), @endif
                        @endforeach

                    </x-th>

                    <x-th class="text-center">CONDICIÓN</x-th>
                    <x-th class="text-center">UNIDAD DE MEDIDA (TABLA 6)</x-th>
                    <x-th class="text-center">TOTAL ENTRADAS UNIDADES</x-th>
                    <x-th class="text-center">TOTAL ENTRADAS IMPORTE</x-th>
                    <x-th class="text-center">TOTAL SALIDAS UNIDADES</x-th>
                    <x-th class="text-center">TOTAL SALIDAS IMPORTE</x-th>
                    <x-th class="text-center">SALDO UNIDADES</x-th>
                    <x-th class="text-center">SALDO IMPORTE</x-th>
                </x-tr>
            </x-slot>

            <x-slot name="tbody">
                @foreach ($insumoKardexReporte->detalles as $insumoKardexReporteDetalle)
                    <x-tr>
                        <x-th
                            class="{{ $insumoKardexReporteDetalle->categoria_producto === 'fertilizante' ? 'bg-fertilizante' : ($insumoKardexReporteDetalle->categoria_producto === 'pesticida' ? 'bg-pesticida' : '') }}">
                            {{ $insumoKardexReporteDetalle->codigo_existencia }}
                        </x-th>
                        <x-th
                            class="{{ $insumoKardexReporteDetalle->categoria_producto === 'fertilizante' ? 'bg-fertilizante' : ($insumoKardexReporteDetalle->categoria_producto === 'pesticida' ? 'bg-pesticida' : '') }}"
                            x-data="{ loading: false }">
                            <a class="cursor-pointer hover:text-red-700 underline text-blue-600 flex insumoKardexReporteDetalles-center gap-2"
                                href="{{ route('gestion_insumos.kardex.detalle', $insumoKardexReporteDetalle->ins_kardex_id) }}"
                                target="_blank" :class="{ 'pointer-events-none opacity-50': loading }">
                                {{ $insumoKardexReporteDetalle->nombre_producto }}

                                <!-- Spinner solo visible mientras loading sea true -->
                                <svg x-show="loading" class="animate-spin h-4 w-4 text-gray-700"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                    </path>
                                </svg>
                            </a>
                        </x-th>

                        <x-td class="text-center">{{ $insumoKardexReporteDetalle->condicion ?? '-' }}</x-td>
                        <x-th class="text-center">{{ $insumoKardexReporteDetalle->unidad_medida }}</x-th>
                        <x-td
                            class="text-right">{{ formatear_numero($insumoKardexReporteDetalle->total_entradas_unidades) }}</x-td>
                        <x-td
                            class="text-right">{{ formatear_numero($insumoKardexReporteDetalle->total_entradas_importe) }}</x-td>
                        <x-td
                            class="text-right">{{ formatear_numero($insumoKardexReporteDetalle->total_salidas_unidades) }}</x-td>
                        <x-td
                            class="text-right">{{ formatear_numero($insumoKardexReporteDetalle->total_salidas_importe) }}</x-td>
                        <x-th style="background-color:#DAEEF3"
                            class="text-right">{{ formatear_numero($insumoKardexReporteDetalle->saldo_unidades) }}</x-th>
                        <x-th style="background-color:#DAEEF3"
                            class="text-right">{{ formatear_numero($insumoKardexReporteDetalle->saldo_importe) }}</x-th>
                    </x-tr>
                @endforeach
                @php
                    $totales = [
                        'entradas_unidades' => $insumoKardexReporte->detalles->sum('total_entradas_unidades'),
                        'entradas_importe' => $insumoKardexReporte->detalles->sum('total_entradas_importe'),
                        'salidas_unidades' => $insumoKardexReporte->detalles->sum('total_salidas_unidades'),
                        'salidas_importe' => $insumoKardexReporte->detalles->sum('total_salidas_importe'),
                        'saldo_unidades' => $insumoKardexReporte->detalles->sum('saldo_unidades'),
                        'saldo_importe' => $insumoKardexReporte->detalles->sum('saldo_importe'),
                    ];
                @endphp


                <x-tr>
                    <x-th colspan="4" class="text-right">TOTAL</x-th>
                    <x-th class="text-right">{{ formatear_numero($totales['entradas_unidades']) }}</x-th>
                    <x-th class="text-right">{{ formatear_numero($totales['entradas_importe']) }}</x-th>
                    <x-th class="text-right">{{ formatear_numero($totales['salidas_unidades']) }}</x-th>
                    <x-th class="text-right">{{ formatear_numero($totales['salidas_importe']) }}</x-th>
                    <x-th class="text-right"
                        style="background-color:#DAEEF3">{{ formatear_numero($totales['saldo_unidades']) }}</x-th>
                    <x-th class="text-right"
                        style="background-color:#DAEEF3">{{ formatear_numero($totales['saldo_importe']) }}</x-th>
                </x-tr>

            </x-slot>
        </x-table>
    </x-card>
    <style>
        .bg-fertilizante {
            background-color: #EBF1DE;
        }

        .bg-pesticida {
            background-color: #E4DFEC;
        }
    </style>
    <x-loading wire:loading />
</div>