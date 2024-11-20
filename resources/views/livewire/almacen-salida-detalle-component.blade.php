<div>
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
                                        class="cursor-pointer underline text-indigo-600 dark:text-blue-200">
                                        {{ $registro->producto->nombre_comercial }}
                                    </div>
                                </x-td>
                                <x-td value="{{ $registro->producto->unidad_medida }}" class="text-center" />
                                <x-td class="text-center">
                                    <x-input type="number" step="3" class="!w-[8rem] text-center"
                                        wire:model.live.debounce.1000ms="cantidad.{{ $registro->id }}" wire:key="cantidad{{ $registro->id }}" />
                                </x-td>
                                <x-td value="{{ $registro->producto->categoria->nombre }}" class="text-center" />
                                <x-td value="{{ $registro->observacion }}" class="text-center" />
                                <x-td class="text-center">
                                    {{ $registro->costo_por_kg }}
                                </x-td>
                                <x-td value="{{ $registro->total_costo }}" class="text-center" />
                                <x-td class="text-center">

                                    <x-flex class="justify-end">
                                        @if ($registro->perteneceAUnaCompra)
                                            <x-secondary-button type="button"
                                                title="Quitar costos para este tipo de producto de aqui en adelante."
                                                wire:click="quitarCompraVinculada({{ $registro->id }})">
                                                <i class="fa fa-remove"></i> <i class="fa fa-money-bill"></i>
                                            </x-secondary-button>
                                            <x-secondary-button type="button" title="Ver historial de compra."
                                                @click="$wire.dispatch('verHistorialSalidaPorCompra',{salidaId:{{$registro->id}}})">
                                                <i class="fa fa-eye"></i> <i class="fa fa-money-bill"></i>
                                            </x-secondary-button>
                                        @endif

                                        <x-danger-button type="button"
                                            wire:click="confirmarEliminacion({{ $registro->id }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </x-flex>

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
   
    <x-dialog-modal wire:model.live="mostrarGenerarItem">
        <x-slot name="title">
            Escribe desde que numero iniciarán los correlativos
        </x-slot>

        <x-slot name="content">
            <x-label>Inicio de numeracion</x-label>
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
