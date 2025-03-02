<div>
    <x-loading wire:loading wire:target="cambiarSalidaA" />
    <x-loading wire:loading wire:target="cambiarCompraA" />
    <x-loading wire:loading wire:target="actualizarInformacionNegro" />
    <x-loading wire:loading wire:target="actualizarInformacionBlanco" />
    <x-loading wire:loading wire:target="generarKardex" />
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        <div>

        </div>
        <x-card>
            <x-spacing>
                <div>
                    <x-h3>Kardex Negro</x-h3>
                </div>
                <div class="mt-2">
                    <x-label>
                        Código de existencia
                    </x-label>
                    <x-input type="text" wire:model="codigoExistenciaNegro" />
                    <x-input-error for="codigoExistenciaNegro" />
                </div>
                <div class="mt-2">
                    <x-label>
                        Stock Inicial
                    </x-label>
                    <x-input type="number" wire:model="stockInicialNegro" />
                    <x-input-error for="stockInicialNegro" />
                </div>
                <div class="mt-2">
                    <x-label>
                        Costo Total Inicial
                    </x-label>
                    <x-input type="number" wire:model="costoInicialNegro" />
                    <x-input-error for="costoInicialNegro" />
                </div>
                <div class="mt-2">
                    <x-button class="w-full" type="button" wire:click="actualizarInformacionNegro">
                        <i class="fa fa-refresh"></i> Actualizar
                    </x-button>
                </div>
                @if ($kardexProductoNegro)
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            @if ($kardexProductoNegro->file)
                          
                                <x-button-a href="{{ Storage::disk('public')->url($kardexProductoNegro->file) }}" class="w-full">
                                    <i class="fa fa-file-excel"></i> Descargar Kardex
                                </x-button-a>
                            @endif
                        </div>
                        <div class="flex items-center">
                            <div class="text-center w-full">
                                <div class="h-[25px] w-full relative bg-gray-100 border border-1 rounded-lg overflow-hidden">
                                    <div style="width:{{$kardexProductoNegro->stockDisponible['stock_disponible_porcentaje']}}%" class="bg-green-600 absolute top-0 left-0 bottom-0">
    
                                    </div>
                                </div>
                                <small>{{$kardexProductoNegro->stockDisponible['stock_disponible']}}{{$kardexProductoNegro->producto->unidad_medida}} disponibles</small>
                            </div>
                        </div>
                    </div>
                @endif
            </x-spacing>
        </x-card>
        <x-card>
            <x-spacing>
                <div>
                    <x-h3>Kardex Blanco</x-h3>
                </div>
                <div class="mt-2">
                    <x-label>
                        Código de existencia
                    </x-label>
                    <x-input type="text" wire:model="codigoExistenciaBlanco" />
                    <x-input-error for="stockInicialBlanco" />
                </div>
                <div class="mt-2">
                    <x-label>
                        Stock Inicial
                    </x-label>
                    <x-input type="number" wire:model="stockInicialBlanco" />
                    <x-input-error for="stockInicialBlanco" />
                </div>
                <div class="mt-2">
                    <x-label>
                        Costo Total Inicial
                    </x-label>
                    <x-input type="number" wire:model="costoInicialBlanco" />
                    <x-input-error for="costoInicialBlanco" />
                </div>
                <div class="mt-2">
                    <x-button class="w-full" type="button" wire:click="actualizarInformacionBlanco">
                        <i class="fa fa-refresh"></i> Actualizar
                    </x-button>
                </div>
                @if ($kardexProductoBlanco)
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            @if ($kardexProductoBlanco->file)
                                <x-button-a href="{{ Storage::disk('public')->url($kardexProductoBlanco->file) }}" class="w-full">
                                    <i class="fa fa-file-excel"></i> Descargar Kardex
                                </x-button-a>
                            @endif
                        </div>
                        <div class="flex items-center">
                            <div class="text-center w-full">
                                <div class="h-[25px] w-full relative bg-gray-100 border border-1 rounded-lg overflow-hidden">
                                    <div style="width:{{$kardexProductoBlanco->stockDisponible['stock_disponible_porcentaje']}}%" class="bg-green-600 absolute top-0 left-0 bottom-0">
    
                                    </div>
                                </div>
                                <small>{{$kardexProductoBlanco->stockDisponible['stock_disponible']}}{{$kardexProductoBlanco->producto->unidad_medida}} disponibles</small>
                            </div>
                        </div>
                    </div>
                @endif
            </x-spacing>
        </x-card>
        <x-card class="mt-2 md:col-span-3">
            <x-spacing>
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
                                        <i class="fa fa-spinner fa-spin" wire:loading
                                            wire:target="corroborarBlanco"></i> Corroborar Blanco
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
            </x-spacing>
        </x-card>
    </div>
</div>
