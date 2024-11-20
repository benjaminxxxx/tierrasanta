<div>
    <x-loading wire:loading />
    <x-flex>
        <x-h3>
            <a href="{{route('kardex.lista')}}" class="underline text-blue-600">Kardex Indice</a> / Kardex por Producto ({{$kardex->tipo_kardex}})
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('crearKardexProducto')">
            <i class="fa fa-plus"></i> Registrar Producto
        </x-button>
    </x-flex>
    <x-card class="my-4">
        <x-spacing>
            <x-flex>
                <x-select wire:model.live="productoKardexSeleccionado" class="!w-auto">
                    <option value="">SELECCIONE UN PRODUCTO</option>
                    @foreach ($kardexDetalleProductos as $kardexDetalleProducto)
                        <option value="{{ $kardexDetalleProducto->producto_id }}">
                            {{ $kardexDetalleProducto->producto->codigo_existencia . ' - ' . $kardexDetalleProducto->producto->nombre_completo }}
                        </option>
                    @endforeach
                </x-select>
                @if ($productoKardexSeleccionado)

                    <livewire:kardex-detalle-import-export-component :productoId="$productoKardexSeleccionado" :kardexId="$kardexId" wire:key="import{{$productoKardexSeleccionado}}" />                  
                    
                @endif
            </x-flex>

        </x-spacing>
    </x-card>
    @if ($productoKardexSeleccionado)
        @if ($kardexDetalleProductos)
            <x-card class="my-4">
                <x-spacing>
                    <x-flex class="my-4 !items-end">
                        <div>
                            <x-label value="MÉTODO DE VALUACIÓN" />
                            <x-select wire:model="metodoValuacion">
                                <option value="promedio">PROMEDIO</option>
                            </x-select>
                        </div>
                        <div>
                            <x-button type="button" type="button" wire:click="recalcularCostos">
                                <i class="fa fa-sync"></i> Recalcular Costos
                            </x-button>
                        </div>
                        @if ($kardexCalculado)
                        <div>
                            <x-button type="button" type="button" wire:click="descargarKardex">
                                <i class="fa fa-file-excel"></i> Descargar Kardex
                            </x-button>
                        </div>    
                        @endif
                        
                        <div>
                            <x-button type="button" type="button" @click="$wire.dispatch('editarKardexProducto',{kardexProductoId:{{$kardexProducto->id}}})">
                                <i class="fa fa-edit"></i> Editar Kardex {{$kardexProducto->producto->codigo_existencia}}
                            </x-button>

                            @if ($kardexProducto->file)
                                <x-button-a href="{{Storage::disk('public')->url($kardexProducto->file)}}">
                                    <i class="fa fa-file-excel"></i> 
                                    Descargar Excel
                                </x-button-a>
                            @endif
                        </div>
                    </x-flex>
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center" colspan="4">
                                    DOCUMENTO DE TRASLADO, COMPROBANTE DE PAGO,<br/>DOCUMENTO INTERNO O SIMILAR
                                </x-th>
                                <x-th class="text-center" rowspan="2">
                                    TIPO DE<br/>OPERACIÓN<br/>(TABLA 12)
                                </x-th>
                                <x-th class="text-center bg-amber-100" colspan="3">
                                    ENTRADAS
                                </x-th>
                                <x-th class="text-center" colspan="4">
                                    SALIDAS
                                </x-th>
                                <x-th class="text-center" colspan="3">
                                    SALDO FINAL
                                </x-th>
                            </x-tr>
                            <x-tr>
                                <x-th class="text-center">
                                    FECHA
                                </x-th>
                                <x-th class="text-center">
                                    TIPO (TABLA 10)
                                </x-th>
                                <x-th class="text-center">
                                    SERIE
                                </x-th>
                                <x-th class="text-center">
                                    NÚMERO
                                </x-th>
                                <x-th class="text-center bg-amber-100">
                                    CANTIDAD
                                </x-th>
                                <x-th class="text-center bg-amber-100">
                                    COSTO UNITARIO
                                </x-th>
                                <x-th class="text-center bg-amber-100">
                                    COSTO TOTAL
                                </x-th>
                                <x-th class="text-center">
                                    CANTIDAD
                                </x-th>
                                <x-th class="text-center">
                                    LOTE
                                </x-th>
                                <x-th class="text-center">
                                    COSTO UNITARIO
                                </x-th>
                                <x-th class="text-center">
                                    COSTO TOTAL
                                </x-th>
                                <x-th class="text-center">
                                    CANTIDAD
                                </x-th>
                                <x-th class="text-center">
                                    COSTO UNITARIO
                                </x-th>
                                <x-th class="text-center">
                                    COSTO TOTAL
                                </x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @if (is_array($kardexLista) && count($kardexLista)>0)
                            @foreach ($kardexLista as $kardexRegistro)
                                <x-tr>
                                    <x-td class="text-center">
                                        {{$kardexRegistro['fecha']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{$kardexRegistro['tabla10']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{$kardexRegistro['serie']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{$kardexRegistro['numero']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{$kardexRegistro['tipo_operacion']}}
                                    </x-td>
                                    <x-td class="bg-amber-50 text-center">
                                        {{is_numeric($kardexRegistro['entrada_cantidad'])? number_format($kardexRegistro['entrada_cantidad'],2):$kardexRegistro['entrada_cantidad']}}
                                    </x-td>
                                    <x-td class="bg-amber-50 text-center">
                                        {{is_numeric($kardexRegistro['entrada_costo_unitario'])? number_format($kardexRegistro['entrada_costo_unitario'],6):$kardexRegistro['entrada_costo_unitario']}}
                                    </x-td>
                                    <x-td class="bg-amber-50 text-center">
                                        {{is_numeric($kardexRegistro['entrada_costo_total'])? number_format($kardexRegistro['entrada_costo_total'],2):$kardexRegistro['entrada_costo_total']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{$kardexRegistro['salida_cantidad']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{$kardexRegistro['salida_lote']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ is_numeric($kardexRegistro['salida_costo_unitario'])? number_format($kardexRegistro['salida_costo_unitario'],2):$kardexRegistro['salida_costo_unitario']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{is_numeric($kardexRegistro['salida_costo_total'])? number_format($kardexRegistro['salida_costo_total'],2):$kardexRegistro['salida_costo_total']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{is_numeric($kardexRegistro['saldofinal_cantidad'])? number_format($kardexRegistro['saldofinal_cantidad'],2):$kardexRegistro['saldofinal_cantidad']}}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{ is_numeric($kardexRegistro['saldofinal_costo_unitario'])? number_format($kardexRegistro['saldofinal_costo_unitario'],2):$kardexRegistro['saldofinal_costo_unitario'] }}
                                    </x-td>
                                    <x-td class="text-center">
                                        {{is_numeric($kardexRegistro['saldofinal_costo_total'])? number_format($kardexRegistro['saldofinal_costo_total'],2):$kardexRegistro['saldofinal_costo_total']}}
                                    </x-td>
                                </x-tr>
                            @endforeach
                            @endif
                        </x-slot>
                    </x-table>
                    
                </x-spacing>
            </x-card>
        @endif
    @endif
</div>
