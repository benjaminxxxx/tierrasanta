<div>
    <x-loading wire:loading />
    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Almacen
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('nuevoRegistro',{mes:{{$mes}},anio:{{$anio}}})" class="w-full md:w-auto ">Nuevo
            Registro</x-button>
    </div>
    <x-card>
        <x-spacing>
            <div class="flex items-center justify-between">

                <x-secondary-button wire:click="mesAnterior">
                    <i class="fa fa-chevron-left"></i> Mes Anterior
                </x-secondary-button>

                <div class="hidden md:flex items-center gap-5">
                    <!-- Selección de mes -->
                    <div class="mx-2">
                        <x-select wire:model.live="mes" class="!mt-0  !w-auto">
                            <option value="01">Enero</option>
                            <option value="02">Febrero</option>
                            <option value="03">Marzo</option>
                            <option value="04">Abril</option>
                            <option value="05">Mayo</option>
                            <option value="06">Junio</option>
                            <option value="07">Julio</option>
                            <option value="08">Agosto</option>
                            <option value="09">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </x-select>
                    </div>

                    <!-- Selección de año -->
                    <div class="mx-2">
                        <x-input type="number" wire:model.live="anio" class="text-center !mt-0 !w-auto"
                            min="1900" />
                    </div>
                </div>


                <!-- Botón para mes posterior -->
                <x-secondary-button wire:click="mesSiguiente" class="ml-3">
                    Mes Siguiente <i class="fa fa-chevron-right"></i>
                </x-secondary-button>
            </div>
        </x-spacing>
    </x-card>

    <x-card class="mt-5">
        <x-spacing>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th class="text-center">
                            <x-warning-button type="text" wire:click="generarItemCodigoForm">
                                ITEM
                            </x-warning-button>
                        </x-th>
                        <x-th value="FECHA SALIDA" class="text-center" />
                        <x-th value="CAMPO" class="text-center" />
                        <x-th value="DESCRIPCION DEL PRODUCTO" />
                        <x-th value="UND. MEDIDA" class="text-center" />
                        <x-th value="CANTIDAD" class="text-center" />
                        <x-th value="CATEGORIA" class="text-center" />
                        <x-th value="OBSERVACION" class="text-center" />
                        <x-th value="COSTO X UNIDAD" class="text-center" />
                        <x-th value="TOTAL COSTO" class="text-center" />
                        <x-th value="ACCIONES" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($registros && $registros->count() > 0)
                        @foreach ($registros as $indice => $registro)
                            <x-tr>
                                <x-th value="{{ $registro->item }}" class="text-center" />
                                <x-td value="{{ $registro->fecha_reporte }}" class="text-center" />
                                <x-td value="{{ $registro->campo_nombre }}" class="text-center" />
                                <x-td>
                                    <div @click="$wire.dispatch('EditarProducto',{'id':{{ $registro->producto->id }}})"
                                        class="cursor-pointer underline text-indigo-600">
                                        {{ $registro->producto->nombre_comercial }}
                                    </div>
                                </x-td>
                                <x-td value="{{ $registro->producto->unidad_medida }}" class="text-center" />
                                <x-td class="text-center">
                                    @if ($registro->compra_producto_id)
                                        <x-button type="button" wire:click="cambiarCantidad({{ $registro->id }})"
                                            value="{{ $registro->cantidad }}">
                                            @if ($registro->cantidad)
                                                {{ $registro->cantidad }}
                                            @else
                                                Cantidad
                                            @endif
                                        </x-button>
                                    @else
                                        @if ($registro->producto->compraActiva)
                                            <div x-data="{ open: $wire.entangle('showDropdown') }">
                                                <x-secondary-button x-on:click="open = true">Elegir
                                                    Compra</x-secondary-button>

                                                <ul x-show="open" x-on:click.outside="open = false">
                                                    @foreach ($registro->producto->compras as $compra)
                                                        <li class="mt-3">
                                                            <x-success-button
                                                                wire:click="elegirCompra({{ $compra->id }},{{ $registro->id }})">
                                                                {{ $compra->fecha_compra . ' - ' . $compra->factura . ' - ' . $compra->costo_por_kg }}

                                                            </x-success-button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @else
                                            <x-button
                                                @click="$wire.dispatch('VerComprasProducto',{'id':{{ $registro->producto->id }}})">
                                                Registrar Compra
                                            </x-button>
                                        @endif
                                    @endif
                                </x-td>
                                <x-td value="{{ $registro->producto->categoria->nombre }}" class="text-center" />
                                <x-td value="{{ $registro->observacion }}" class="text-center" />
                                <x-td value="{{ $registro->costo_por_unidad }}" class="text-center" />
                                <x-td value="{{ $registro->total_costo_calculado }}" class="text-center" />
                                <x-td class="text-center">
                                    <x-danger-button type="button"
                                        wire:click="confirmarEliminacion({{ $registro->id }})">
                                        <i class="fa fa-trash"></i>
                                    </x-danger-button>
                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay registrados para este mes.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
    <x-dialog-modal wire:model.live="mostrarCambiarCantidad">
        <x-slot name="title">
            Cambiar la cantidad de salida del producto
        </x-slot>

        <x-slot name="content">
            <x-label>Cantidad</x-label>
            <x-input type="number" wire:keydown.enter="guardarCantidadNueva" wire:model="cantidadNueva" />
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-5">
                <x-secondary-button wire:click="cerrarMostrarCambiarCantidad" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="guardarCantidadNueva" wire:loading.attr="disabled">
                    Guardar Cantidad
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
    <x-dialog-modal wire:model.live="mostrarGenerarItem">
        <x-slot name="title">
            Escribe desde que numero iniciarán los correlativos
        </x-slot>

        <x-slot name="content">
            <x-label>Cantidad</x-label>
            <x-input type="number" wire:keydown.enter="generarItemCodigo" wire:model="inicioItem" />
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-5">
                <x-secondary-button wire:click="cerrarMostrarGenerarItem" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="generarItemCodigo" wire:loading.attr="disabled">
                    Generar codigo de items
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
