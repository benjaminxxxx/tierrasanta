<div>
    <x-dialog-modal wire:model.live="mostrarHistorial" maxWidth="full">
        <x-slot name="title">
            Historiales de Salida por Compra
        </x-slot>

        <x-slot name="content">

            @foreach ($historiales as $compras)
                @php
                    $entrada = $compras['entrada'];
                    $historial = $compras['historial'];
                @endphp
                <div class="my-3">
                    <p>Salidas realizadas de una misma compra</p>
                    @if ($entrada && $entrada->fecha_termino)
                        <p>La compra ha sido utilizada en su totalidad hasta la fecha: {{ $entrada->fecha_termino }}</p>
                    @endif
                </div>

                <x-flex>
                    <div class="flex-1">
                        <x-table>
                            <x-slot name="thead">
                                <x-tr class="dark:bg-boxdarkbase mt-2">
                                    <x-th colspan="100%" class="text-center py-2">Entrada</x-th>
                                </x-tr>
                                <x-tr>
                                    <x-th class="text-center">-</x-th>
                                    <x-th class="text-center">Cantidad</x-th>
                                    <x-th class="text-center">Fecha Compra</x-th>
                                    <x-th class="text-right">Costo Unitario</x-th>
                                    <x-th class="text-right">Costo Total</x-th>
                                </x-tr>
                                @if ($entrada)
                                    <x-tr>
                                        <x-td></x-td>
                                        <x-td class="text-center">{{ number_format($entrada->stock, 3) }}</x-td>
                                        <x-td class="text-center">{{ $entrada->fecha_compra }}</x-td>
                                        <x-td class="text-right">{{ number_format($entrada->costo_por_kg, 2) }}</x-td>
                                        <x-td class="text-right">{{ number_format($entrada->total, 2) }}</x-td>
                                    </x-tr>
                                @endif
                                <x-tr class="dark:bg-boxdarkbase mt-2">
                                    <x-th colspan="100%" class="text-center py-2">Salida</x-th>
                                </x-tr>
                                <x-tr>
                                    <x-th class="text-center">Lote</x-th>
                                    <x-th class="text-center">Cantidad</x-th>
                                    <x-th class="text-center">Fecha de salida</x-th>
                                    <x-th class="text-right">Costo Unitario</x-th>
                                    <x-th class="text-right">Costo Total</x-th>
                                </x-tr>
                            </x-slot>
                            <x-slot name="tbody">
                                @php
                                    $historiaCantidad = 0;
                                    $historiaCostoPorUnidad = 0;
                                    $historiaTotalCosto = 0;
                                @endphp
                                @foreach ($historial as $historia)
                                    @php
                                        $historiaCantidad += $historia->stock;
                                        $historiaCostoPorUnidad += $historia->salida->costo_por_kg;
                                        $historiaTotalCosto += $historia->salida->total_costo;
                                    @endphp
                                    <x-tr class="{{ $historia->id == $salidaId ? '!bg-lime-600' : '' }}">
                                        <x-td class="text-center">{{ $historia->salida->campo_nombre }}</x-td>
                                        <x-td class="text-center">{{ $historia->stock }}</x-td>
                                        <x-td class="text-center">{{ $historia->salida->fecha_reporte }}</x-td>
                                        <x-td class="text-right">{{ $historia->salida->costo_por_kg }}</x-td>
                                        <x-td class="text-right">{{ $historia->salida->total_costo }}</x-td>
                                    </x-tr>
                                @endforeach
                                <x-tr>
                                    <x-th></x-th>
                                    <x-th class="text-center">{{ $historiaCantidad }}</x-th>
                                    <x-th class="text-center">-</x-th>
                                    <x-th class="text-right">{{ $historiaCostoPorUnidad }}</x-th>
                                    <x-th class="text-right">{{ $historiaTotalCosto }}</x-th>
                                </x-tr>
                            </x-slot>
                        </x-table>
                    </div>
                    @if ($entrada)
                        <div class="w-[14rem]">
                            @php
                                // Asegurarse de que ambos valores sean numéricos
                                $stockInicial = is_numeric($entrada->stock) ? (float) $entrada->stock : 0;
                                $cantidadUsada = is_numeric(str_replace(',', '', $historiaCantidad))
                                    ? (float) str_replace(',', '', $historiaCantidad)
                                    : 0;

                                // Calcular el porcentaje de llenado restante
                                $porcentajeRestante =
                                    $stockInicial > 0 ? (($stockInicial - $cantidadUsada) / $stockInicial) * 100 : 0;

                                // Limitar el porcentaje entre 0 y 100
                                $porcentajeRestante = max(0, min(100, $porcentajeRestante));
                                $restnteUnidad = $stockInicial - $cantidadUsada;
                            @endphp

                            <div
                                class="barril-container w-full h-[300px] bg-gray-200 rounded-lg relative overflow-hidden shadow">
                                <div class="barril-fill w-full absolute bottom-0 bg-green-500 transition-all duration-500"
                                    style="height: {{ $porcentajeRestante }}%;"></div>
                            </div>
                            <p class="text-center mt-2">{{ number_format($porcentajeRestante, 2) }}% restante
                                ({{ $restnteUnidad . $entrada->producto->unidad_medida }})</p>
                        </div>
                    @endif
                </x-flex>
            @endforeach

        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarHistorial', false)" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
    <style>
        /* Contenedor del barril */


        /* Barra de llenado */
        .barril-fill {
            background-color: #4caf50;
            /* Color de llenado (verde) */
            transition: height 0.5s ease;
            /* Transición suave para el llenado */
        }
    </style>
</div>
