<div>
    <x-loading wire:loading wire:target="cambiarSalidaA" />
    <x-loading wire:loading wire:target="cambiarCompraA" />
    <x-loading wire:loading wire:target="actualizarInformacionNegro" />
    <x-loading wire:loading wire:target="actualizarInformacionBlanco" />
    <x-loading wire:loading wire:target="generarKardex" />

    <x-card2 class="mt-4">
        <div class="overflow-x-auto ">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <x-th class="text-left">Tipo</x-th>
                        <x-th class="text-left">Código de existencia</x-th>
                        <x-th class="text-left">Stock inicial</x-th>
                        <x-th class="text-left">Costo total inicial</x-th>
                        <x-th class="text-center">Actualizar</x-th>
                        <x-th class="text-center">Descargar</x-th>
                        <x-th class="text-center">Stock disponible</x-th>
                    </tr>
                </thead>

                <tbody>

                    {{-- Fila Kardex Negro --}}
                    <tr>
                        <td class="p-2">
                            <x-h3>Negro</x-h3>
                        </td>

                        <td class="p-2">
                            <x-input type="text" wire:model="codigoExistenciaNegro" />
                            <x-input-error for="codigoExistenciaNegro" />
                        </td>

                        <td class="p-2">
                            <x-input type="number" wire:model="stockInicialNegro" />
                            <x-input-error for="stockInicialNegro" />
                        </td>

                        <td class="p-2">
                            <x-input type="number" wire:model="costoInicialNegro" />
                            <x-input-error for="costoInicialNegro" />
                        </td>

                        <x-td class="text-center">
                            <x-button type="button" wire:click="actualizarInformacionNegro">
                                <i class="fa fa-refresh"></i> Guardar
                            </x-button>
                        </x-td>

                        <td class="p-2 text-center">
                            @if ($kardexProductoNegro?->file)
                                <x-button href="{{ Storage::disk('public')->url($kardexProductoNegro->file) }}">
                                    <i class="fa fa-file-excel"></i> Descargar
                                </x-button>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>

                        <td class="p-2 text-center">
                            @if ($kardexProductoNegro)
                                <div class="text-center w-full">
                                    <div
                                        class="h-[15px] w-full relative bg-gray-100 border border-1 rounded-md overflow-hidden">
                                        <div style="width:{{$kardexProductoNegro->stockDisponible['stock_disponible_porcentaje']}}%"
                                            class="bg-green-600 absolute top-0 left-0 bottom-0">

                                        </div>
                                    </div>
                                    <small>{{$kardexProductoNegro->stockDisponible['stock_disponible']}}{{$kardexProductoNegro->producto->unidad_medida}}
                                        disponibles</small>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Fila Kardex Blanco --}}
                    <tr>
                        <td class="p-2">
                            <x-h3>Blanco</x-h3>
                        </td>

                        <td class="p-2">
                            <x-input type="text" wire:model="codigoExistenciaBlanco" />
                            <x-input-error for="codigoExistenciaBlanco" />
                        </td>

                        <td class="p-2">
                            <x-input type="number" wire:model="stockInicialBlanco" />
                            <x-input-error for="stockInicialBlanco" />
                        </td>

                        <td class="p-2">
                            <x-input type="number" wire:model="costoInicialBlanco" />
                            <x-input-error for="costoInicialBlanco" />
                        </td>

                        <td class="p-2 text-center">
                            <x-button type="button" wire:click="actualizarInformacionBlanco">
                                <i class="fa fa-refresh"></i> Guardar
                            </x-button>
                        </td>

                        <td class="p-2 text-center">
                            @if ($kardexProductoBlanco?->file)
                                <x-button href="{{ Storage::disk('public')->url($kardexProductoBlanco->file) }}">
                                    <i class="fa fa-file-excel"></i> Descargar
                                </x-button>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>

                        <td class="p-2 text-center">
                            @if ($kardexProductoBlanco)
                                <div class="text-center w-full">
                                    <div
                                        class="h-[15px] w-full relative bg-gray-100 border border-1 rounded-md overflow-hidden">
                                        <div style="width:{{$kardexProductoBlanco->stockDisponible['stock_disponible_porcentaje']}}%"
                                            class="bg-green-600 absolute top-0 left-0 bottom-0">

                                        </div>
                                    </div>
                                    <small>{{$kardexProductoBlanco->stockDisponible['stock_disponible']}}{{$kardexProductoBlanco->producto->unidad_medida}}
                                        disponibles</small>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </x-card2>
    <x-card2 class="mt-4">
        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th class="text-center">Fecha</x-th>
                    <x-th class="text-center" colspan="5">Kardex Negro</x-th>
                    <x-th class="text-center" colspan="5">Registros libres</x-th>
                    <x-th class="text-center" colspan="5">Kardex Blanco</x-th>
                </x-tr>
                <x-tr>
                    <x-th class="text-center"></x-th>
                    <x-th class="text-center" colspan="5">
                        <div>
                            <x-button class="w-full" type="button" wire:click="corroborarNegro">
                                <i class="fa fa-check" wire:loading.remove wire:target="corroborarNegro"></i>
                                <i class="fa fa-spinner fa-spin" wire:loading wire:target="corroborarNegro"></i>
                                Corroborar Negro
                            </x-button>
                        </div>
                        <div class="mt-2">
                            <x-button class="w-full" type="button" wire:click="generarKardex('negro')">
                                <i class="fa fa-file-excel"></i> Generar Kardex Negro
                            </x-button>
                        </div>
                    </x-th>
                    <x-th class="text-center" colspan="5"></x-th>
                    <x-th class="text-center" colspan="5">
                        <div>
                            <x-button class="w-full" type="button" wire:click="corroborarBlanco">
                                <i class="fa fa-check" wire:loading.remove wire:target="corroborarBlanco"></i>
                                <i class="fa fa-spinner fa-spin" wire:loading wire:target="corroborarBlanco"></i>
                                Corroborar Blanco
                            </x-button>
                        </div>
                        <div class="mt-2">
                            <x-button class="w-full" type="button" wire:click="generarKardex('blanco')">
                                <i class="fa fa-file-excel"></i> Generar Kardex Blanco
                            </x-button>
                        </div>
                    </x-th>
                </x-tr>
                <x-tr>
                    <x-th class="text-center"></x-th>
                    <x-th class="text-center">Descripción</x-th>
                    <x-th class="text-center">Cant</x-th>
                    <x-th class="text-center">Costo x Unid.</x-th>
                    <x-th class="text-center">Costo Total</x-th>
                    <x-th class="text-center"></x-th>
                    <x-th class="text-center">Descripción</x-th>
                    <x-th class="text-center">Cant</x-th>
                    <x-th class="text-center">Costo x Unid.</x-th>
                    <x-th class="text-center">Costo Total</x-th>
                    <x-th class="text-center"></x-th>
                    <x-th class="text-center">Descripción</x-th>
                    <x-th class="text-center">Cant</x-th>
                    <x-th class="text-center">Costo x Unid.</x-th>
                    <x-th class="text-center">Costo Total</x-th>
                    <x-th class="text-center"></x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @if ($movimientos)

                    @foreach ($movimientos as $movimiento)
                        <x-tr>
                            <x-td>{{ $movimiento['fecha'] }}</x-td>
                            <x-td>
                                @if ($movimiento['negro'])
                                    @if ($movimiento['negro']['tipo'] == 'compra')
                                        Compra
                                    @else
                                        Salida a {{ json_decode($movimiento['negro']['data'])->campo_nombre }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['negro'])
                                    @if ($movimiento['negro']['tipo'] == 'compra')
                                        {{ number_format(json_decode($movimiento['negro']['data'])->stock, 3) }}
                                    @else
                                        {{ number_format(json_decode($movimiento['negro']['data'])->cantidad, 3) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['negro'])
                                    @if ($movimiento['negro']['tipo'] == 'compra')
                                        {{ number_format(json_decode($movimiento['negro']['data'])->total / json_decode($movimiento['negro']['data'])->stock, 2) }}
                                    @else
                                        {{ number_format(json_decode($movimiento['negro']['data'])->costo_por_kg, 3) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['negro'])
                                    @if ($movimiento['negro']['tipo'] == 'compra')
                                        {{ number_format(json_decode($movimiento['negro']['data'])->total, 2) }}
                                    @else
                                        {{ number_format(json_decode($movimiento['negro']['data'])->total_costo, 3) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['negro'])
                                    @if ($movimiento['negro']['tipo'] == 'compra')
                                        <x-button
                                            wire:click="cambiarCompraA('blanco', {{ json_decode($movimiento['negro']['data'])->compra_id }})">
                                            <i class="fa fa-arrow-right"></i>
                                        </x-button>
                                    @else
                                        <x-button
                                            wire:click="cambiarSalidaA('blanco', {{ json_decode($movimiento['negro']['data'])->salida_id }})">
                                            <i class="fa fa-arrow-right"></i>
                                        </x-button>
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td>
                                @if ($movimiento['libre'])
                                    @if ($movimiento['libre']['tipo'] == 'compra')
                                        Compra
                                    @else
                                        Salida a {{ json_decode($movimiento['libre']['data'])->campo_nombre }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['libre'])
                                    @if ($movimiento['libre']['tipo'] == 'compra')
                                        {{ number_format(json_decode($movimiento['libre']['data'])->stock, 3) }}
                                    @else
                                        {{ number_format(json_decode($movimiento['libre']['data'])->cantidad, 3) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['libre'])
                                    @if ($movimiento['libre']['tipo'] == 'compra')
                                        {{ number_format(json_decode($movimiento['libre']['data'])->total / json_decode($movimiento['libre']['data'])->stock, 2) }}
                                    @else
                                        {{ number_format(json_decode($movimiento['libre']['data'])->costo_por_kg, 3) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['libre'])
                                    @if ($movimiento['libre']['tipo'] == 'compra')
                                        {{ number_format(json_decode($movimiento['libre']['data'])->total, 2) }}
                                    @else
                                        {{ number_format(json_decode($movimiento['libre']['data'])->total_costo, 3) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['libre'])
                                    @if ($movimiento['libre']['tipo'] == 'compra')
                                        <x-button
                                            wire:click="cambiarCompraA('negro', {{ json_decode($movimiento['libre']['data'])->compra_id ?? json_decode($movimiento['libre']['data'])->compra_id }})"
                                            class="btn btn-sm btn-dark">
                                            <i class="fa fa-arrow-left"></i>
                                        </x-button>
                                        <x-button
                                            wire:click="cambiarCompraA('blanco', {{ json_decode($movimiento['libre']['data'])->compra_id ?? json_decode($movimiento['libre']['data'])->compra_id }})">
                                            <i class="fa fa-arrow-right"></i>
                                        </x-button>
                                    @else
                                        <x-button
                                            wire:click="cambiarSalidaA('negro', {{ json_decode($movimiento['libre']['data'])->salida_id ?? json_decode($movimiento['libre']['data'])->salida_id }})"
                                            class="btn btn-sm btn-dark">
                                            <i class="fa fa-arrow-left"></i>
                                        </x-button>
                                        <x-button
                                            wire:click="cambiarSalidaA('blanco', {{ json_decode($movimiento['libre']['data'])->salida_id ?? json_decode($movimiento['libre']['data'])->salida_id }})">
                                            <i class="fa fa-arrow-right"></i>
                                        </x-button>
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td>
                                @if ($movimiento['blanco'])
                                    @if ($movimiento['blanco']['tipo'] == 'compra')
                                        Compra
                                    @else
                                        Salida a {{ json_decode($movimiento['blanco']['data'])->campo_nombre }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['blanco'])
                                    @if ($movimiento['blanco']['tipo'] == 'compra')
                                        {{ number_format(json_decode($movimiento['blanco']['data'])->stock, 3) }}
                                    @else
                                        {{ number_format(json_decode($movimiento['blanco']['data'])->cantidad, 3) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['blanco'])
                                    @if ($movimiento['blanco']['tipo'] == 'compra')
                                        {{ number_format(json_decode($movimiento['blanco']['data'])->total / json_decode($movimiento['blanco']['data'])->stock, 2) }}
                                    @else
                                        {{ number_format(json_decode($movimiento['blanco']['data'])->costo_por_kg, 3) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['blanco'])
                                    @if ($movimiento['blanco']['tipo'] == 'compra')
                                        {{ number_format(json_decode($movimiento['blanco']['data'])->total, 2) }}
                                    @else
                                        {{ number_format(json_decode($movimiento['blanco']['data'])->total_costo, 3) }}
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                            <x-td class="text-center">
                                @if ($movimiento['blanco'])
                                    @if ($movimiento['blanco']['tipo'] == 'compra')
                                        <x-button
                                            wire:click="cambiarCompraA('negro', {{ json_decode($movimiento['blanco']['data'])->compra_id }})">
                                            <i class="fa fa-arrow-left"></i>
                                        </x-button>
                                    @else
                                        <x-button
                                            wire:click="cambiarSalidaA('negro', {{ json_decode($movimiento['blanco']['data'])->salida_id }})">
                                            <i class="fa fa-arrow-left"></i>
                                        </x-button>
                                    @endif
                                @else
                                    -
                                @endif
                            </x-td>
                        </x-tr>
                    @endforeach
                    <x-tr>
                        <x-th class="text-center"></x-th>
                        <x-th class="text-center" colspan="5">
                            <x-danger-button type="button" class="w-full" wire:click="eliminarComprasSalidas('negro')">
                                <i class="fa fa-remove"></i> Eliminar Compras y Salidas
                            </x-danger-button>
                        </x-th>
                        <x-th class="text-center" colspan="5"></x-th>
                        <x-th class="text-center" colspan="5">
                            <x-danger-button type="button" class="w-full" wire:click="eliminarComprasSalidas('blanco')">
                                <i class="fa fa-remove"></i> Eliminar Compras y Salidas
                            </x-danger-button>
                        </x-th>
                    </x-tr>
                @endif
            </x-slot>
        </x-table>
    </x-card2>
</div>