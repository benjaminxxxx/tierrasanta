<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarVista" maxWidth="full">
        <x-slot name="title">
            Stock disponible de cada producto
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                @foreach ($productos as $producto)
                    <div>
                        <x-h3 class="w-full text-center !text-md my-2">{{ $producto->nombre_comercial }}
                            {{ $producto->ingrediente_activo ? '(' . $producto->ingrediente_activo . ')' : '' }}</x-h3>
                        @if ($producto->compras->count() > 0)
                            @if ($producto->datos_uso['agotado'] == true)
                                <div
                                    class="barril-container w-full h-[100px] bg-gray-200 rounded-lg relative overflow-hidden shadow">
                                    <div class="barril-fill w-full absolute bottom-0 bg-green-500 transition-all duration-500"
                                        style="height: 0%;"></div>
                                </div>
                                <p>Producto Agotado en la fecha: {{ $producto->datos_uso['fecha'] }}.</p>
                            @else
                                @php
                                    // Asegurarse de que ambos valores sean numéricos
                                    $stockInicial = is_numeric($producto->datos_uso['capacidad'])
                                        ? (float) $producto->datos_uso['capacidad']
                                        : 0;
                                    $cantidadUsada = is_numeric(
                                        str_replace(',', '', $producto->datos_uso['stockUsado']),
                                    )
                                        ? (float) str_replace(',', '', $producto->datos_uso['stockUsado'])
                                        : 0;

                                    // Calcular el porcentaje de llenado restante
                                    $porcentajeRestante =
                                        $stockInicial > 0
                                            ? (($stockInicial - $cantidadUsada) / $stockInicial) * 100
                                            : 0;

                                    // Limitar el porcentaje entre 0 y 100
                                    $porcentajeRestante = max(0, min(100, $porcentajeRestante));
                                @endphp
                                <div
                                    class="barril-container w-full h-[100px] bg-gray-200 rounded-lg relative overflow-hidden shadow">
                                    <div class="barril-fill w-full absolute bottom-0 bg-green-500 transition-all duration-500"
                                        style="height: {{ $porcentajeRestante }}%;"></div>
                                </div>
                                <ul class="text-center">
                                    <li>
                                        <p><b>Capacidad actual:</b>
                                            {{ $producto->datos_uso['capacidad'] . $producto->unidad_medida }}</p>
                                    </li>
                                    <li>
                                        <p><b>Stock Usado:</b>
                                            {{ $producto->datos_uso['stockUsado'] . $producto->unidad_medida }}</p>
                                    </li>
                                    <li>
                                        <p><b>Stock restante:</b>
                                            {{ $producto->datos_uso['restante'] . $producto->unidad_medida }}</p>
                                    </li>
                                </ul>
                            @endif
                        @else
                            <div class="w-full text-center my-5">
                                <i class="fa fa-warning text-4xl text-yellow-600"></i>
                                <p>Sin ninguna compra realizada.</p>
                            </div>
                        @endif
                        <div class="text-center">
                            <x-button type="button" @click="$wire.dispatch('VerComprasProducto',{id:{{$producto->id}}})" class="mt-3">
                                <i class="fa fa-money-bill"></i> Ver compras
                            </x-button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-5">
                <x-secondary-button wire:click="$set('mostrarVista', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
