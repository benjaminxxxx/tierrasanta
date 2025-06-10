<div>
    <x-loading wire:loading />

    <x-card>
        <x-spacing>
            <x-h2 class="w-full text-center">
                TABLA PRINCIPAL <x-button wire:click="procesarKardexConsolidado">Generar resumen</x-button>
            </x-h2>
            <x-table class="my-4">
                <x-slot name="thead">
                    <x-tr>
                        <x-th colspan="2" style="background-color:#31869B"
                            class="px-4 py-2 text-white font-bold text-lg">ÍNDICE DE
                            FERTILIZANTES Y PESTICIDAS</x-th>
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
                    @foreach ($kardexConsolidado as $item)
                        <x-tr>
                            <x-th
                                class="{{ $item->categoria_producto === 'fertilizante' ? 'bg-fertilizante' : ($item->categoria_producto === 'pesticida' ? 'bg-pesticida' : '') }}">
                                {{ $item->codigo_existencia }}
                            </x-th>
                            <x-th
                                class="{{ $item->categoria_producto === 'fertilizante' ? 'bg-fertilizante' : ($item->categoria_producto === 'pesticida' ? 'bg-pesticida' : '') }}"
                                x-data="{ loading: false }">
                                <p class="cursor-pointer hover:text-red-700 underline text-blue-600 flex items-center gap-2"
                                    @click="loading = true; $wire.dispatch('seleccionarProducto', {productoId: {{ $item->producto_id }}})"
                                    :class="{ 'pointer-events-none opacity-50': loading }">
                                    {{ $item->producto_nombre }}

                                    <!-- Spinner solo visible mientras loading sea true -->
                                    <svg x-show="loading" class="animate-spin h-4 w-4 text-gray-700"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                            stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                </p>
                            </x-th>

                            <x-td class="text-center">{{ $item->condicion ?? '-' }}</x-td>
                            <x-th class="text-center">{{ $item->unidad_medida }}</x-th>
                            <x-td class="text-right">{{ formatear_numero($item->total_entradas_unidades) }}</x-td>
                            <x-td class="text-right">{{ formatear_numero($item->total_entradas_importe) }}</x-td>
                            <x-td class="text-right">{{ formatear_numero($item->total_salidas_unidades) }}</x-td>
                            <x-td class="text-right">{{ formatear_numero($item->total_salidas_importe) }}</x-td>
                            <x-th style="background-color:#DAEEF3"
                                class="text-right">{{ formatear_numero($item->saldo_unidades) }}</x-th>
                            <x-th style="background-color:#DAEEF3"
                                class="text-right">{{ formatear_numero($item->saldo_importe) }}</x-th>
                        </x-tr>
                    @endforeach
                    @php
                        $totales = [
                            'entradas_unidades' => collect($kardexConsolidado)->sum('total_entradas_unidades'),
                            'entradas_importe' => collect($kardexConsolidado)->sum('total_entradas_importe'),
                            'salidas_unidades' => collect($kardexConsolidado)->sum('total_salidas_unidades'),
                            'salidas_importe' => collect($kardexConsolidado)->sum('total_salidas_importe'),
                            'saldo_unidades' => collect($kardexConsolidado)->sum('saldo_unidades'),
                            'saldo_importe' => collect($kardexConsolidado)->sum('saldo_importe'),
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

        </x-spacing>
    </x-card>
    <style>
        .bg-fertilizante {
            background-color: #EBF1DE;
        }

        .bg-pesticida {
            background-color: #E4DFEC;
        }
    </style>
</div>
