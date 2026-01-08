<div class="my-5 overflow-x-auto">
    <table class="min-w-full border border-gray-400 dark:border-gray-600 text-xs dark:text-white">
        <thead>
            {{-- FILA 1 --}}
            <tr class="bg-gray-200 dark:bg-gray-700 font-semibold">
                <th colspan="4" class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">
                    DOCUMENTO DE TRASLADO, COMPROBANTE DE PAGO, DOCUMENTO INTERNO O SIMILAR
                </th>

                <th rowspan="2" class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center w-32">
                    TIPO DE OPERACIÓN (TABLA 12)
                </th>

                <th colspan="3" class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">
                    ENTRADAS
                </th>

                <th colspan="4" class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">
                    SALIDAS
                </th>

                <th colspan="3" class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">
                    SALDO FINAL
                </th>
            </tr>

            {{-- FILA 2 --}}
            <tr class="bg-gray-200 dark:bg-gray-700 font-semibold">
                {{-- DOCUMENTO --}}
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">FECHA</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">TIPO (TABLA 10)</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">SERIE</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">NÚMERO</th>

                {{-- ENTRADAS --}}
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">CANTIDAD</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">COSTO UNITARIO</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">COSTO TOTAL</th>

                {{-- SALIDAS --}}
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">CANTIDAD</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">LOTE</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">COSTO UNITARIO</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">COSTO TOTAL</th>

                {{-- SALDO FINAL --}}
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">CANTIDAD</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">COSTO UNITARIO</th>
                <th class="border border-gray-400 dark:border-gray-600 px-2 py-1 text-center">COSTO TOTAL</th>
            </tr>
        </thead>

        <tbody>
            @forelse($movimientos as $mov)
                <tr>
                    {{-- Fecha / Documento --}}
                    <td class="border border-gray-400 px-2 py-1 text-center">{{ $mov->fecha }}</td>
                    <td class="border border-gray-400 px-2 py-1 text-center">{{ $mov->tipo_documento }}</td>
                    <td class="border border-gray-400 px-2 py-1 text-center">{{ $mov->serie }}</td>
                    <td class="border border-gray-400 px-2 py-1 text-center">{{ $mov->numero }}</td>

                    {{-- Tipo operación --}}
                    <td class="border border-gray-400 px-2 py-1 text-center">{{ $mov->tipo_operacion }}</td>

                    {{-- ENTRADAS --}}
                    <td class="border border-gray-400 px-2 py-1 text-right">
                        {{ $mov->tipo_mov == 'entrada' ? number_format($mov->entrada_cantidad, 3) : '-' }}
                    </td>
                    <td class="border border-gray-400 px-2 py-1 text-right">
                        {{ $mov->tipo_mov == 'entrada' ? number_format($mov->entrada_costo_unitario, 2) : '-' }}
                    </td>
                    <td class="border border-gray-400 px-2 py-1 text-right">
                        {{ $mov->tipo_mov == 'entrada' ? number_format($mov->entrada_costo_total, 2) : '-' }}
                    </td>

                    {{-- SALIDAS --}}
                    <td class="border border-gray-400 px-2 py-1 text-right">
                        {{ $mov->tipo_mov == 'salida' ? number_format($mov->salida_cantidad, 3) : '-' }}
                    </td>
                    <td class="border border-gray-400 px-2 py-1 text-center">
                        {{ $mov->tipo_mov == 'salida' ? $mov->salida_lote ?? ($mov->salida_maquinaria ?? '-') : '-' }}
                    </td>

                    <td class="border border-gray-400 px-2 py-1 text-right">
                        {{ $mov->tipo_mov == 'salida' ? number_format($mov->salida_costo_unitario, 2) : '-' }}
                    </td>
                    <td class="border border-gray-400 px-2 py-1 text-right">
                        {{ $mov->tipo_mov == 'salida' ? number_format($mov->salida_costo_total, 2) : '-' }}
                    </td>


                    {{-- SALDO --}}
                    <td class="border border-gray-400 px-2 py-1 text-right">
                        {{ number_format($mov->saldo_cantidad, 3) }}
                    </td>

                    <td class="border border-gray-400 px-2 py-1 text-right">
                        {{ number_format($mov->saldo_costo_unitario, 2) }}
                    </td>

                    <td class="border border-gray-400 px-2 py-1 text-right">
                        {{ number_format($mov->saldo_costo_total, 2) }}
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="15" class="border border-gray-400 text-center text-gray-500 py-2">
                        (Sin datos)
                    </td>
                </tr>
            @endforelse
        </tbody>

    </table>
</div>
