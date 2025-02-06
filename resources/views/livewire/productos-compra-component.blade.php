<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-flex>
                    <x-h3>
                        Registro de Compra de Productos
                    </x-h3>
                    @if ($productoId)
                        <x-button type="button" @click="$wire.dispatch('agregarCompra',{productoId:{{ $productoId }}})"
                            class="w-auto">
                            <i class="fa fa-plus"></i> Agregar
                        </x-button>
                    @endif

                </x-flex>
                <div class="flex-shrink-0">
                    <button wire:click="closeForm" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">

            @if ($producto)
                <div class="mb-4">
                    <p class="font-bold">
                        PRODUCTO: {{ $producto->nombre_completo }}
                    </p>
                </div>
            @endif
            <x-flex class="filtros">
                <div>
                    <x-label for="filtroTipoKardex">
                        Kardex
                    </x-label>
                    <x-select wire:model.live="filtroTipoKardex">
                        <option value="">Todos</option>
                        <option value="blanco">Blanco</option>
                        <option value="negro">Negro</option>
                    </x-select>
                </div>
            </x-flex>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th>
                            <button wire:click="sortBy('tienda_comercial_id')" class="focus:outline-none">
                                TIENDA COMERCIAL <i class="fa fa-sort"></i>
                            </button>
                        </x-th>
                        <x-th class="text-center">
                            <button wire:click="sortBy('fecha_compra')" class="focus:outline-none">
                                FECHA DE COMPRA <i class="fa fa-sort"></i>
                            </button>
                        </x-th>

                        <x-th class="text-center">
                            STOCK
                        </x-th>
                        <x-th class="text-center">
                            <button wire:click="sortBy('costo_por_kg')" class="focus:outline-none">
                                COSTO POR UNIDAD <i class="fa fa-sort"></i>
                            </button>
                        </x-th>
                        <x-th class="text-center">
                            TOTAL
                        </x-th>
                        <x-th class="text-center">
                            NÚMERO
                        </x-th>
                        <x-th class="text-center">
                            KARDEX
                        </x-th>
                        <x-th value="ACCIONES" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">

                    @if ($compras && $compras->count() > 0)
                        @foreach ($compras as $indice => $producto)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td
                                    value="{{ $producto->tiendaComercial ? $producto->tiendaComercial->nombre : 'Sin tienda' }}" />
                                <x-td value="{{ $producto->fecha_compra }}" class="text-center" />
                                <x-td value="{{ $producto->stock }}" class="text-center" />
                                <x-td value="{{ $producto->costo_por_unidad }}" class="text-center" />
                                <x-td value="{{ $producto->total }}" class="text-center" />
                                <x-td value="{{ $producto->codigo_comprobante }}" class="text-center" />
                                    <x-td value="{{ ucfirst($producto->tipo_kardex) }}" class="text-center" />

                                <x-td class="text-center">
                                    <div class="flex items-center justify-center gap-2">

                                        <x-secondary-button
                                            @click="$wire.dispatch('editarCompra',{productoId:{{ $productoId }},compraId:{{ $producto->id }}})">
                                            <i class="fa fa-edit"></i>
                                        </x-secondary-button>
                                        <x-danger-button wire:click="confirmarEliminacion({{ $producto->id }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>

                                    </div>

                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No Hay Compras Registradas.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
            @if ($compras && $compras->count() > 0)
                <div class="mt-5">
                    {{ $compras->links() }}
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <div class="flex w-full items-center justify-end gap-4">

                
                <x-secondary-button type="button" wire:click="closeForm">Cerrar</x-secondary-button>
                @if ($producto)
                <livewire:compra-producto-import-export-component :productoid="$producto->producto_id"
                wire:key="registroCompraForCompra{{ $producto->id }}-{{ $producto->producto_id }}" />
                @endif
                @php
                    //De este codigo debajo no encuentro referencias, se debe quitar a posterior
                @endphp
                @if ($modo == 'step')
                    <x-button type="button" wire:click="continuar" class="mr-2">Siguiente</x-button>
                @endif
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
