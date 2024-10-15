<div>
    <x-dialog-modal wire:model="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Compra de Productos
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="closeForm" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
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
                            <button wire:click="sortBy('costo_por_kg')" class="focus:outline-none">
                                COSTO POR UNIDAD <i class="fa fa-sort"></i>
                            </button>
                        </x-th>
                        <x-th class="text-center">
                            FACTURA
                        </x-th>
                        <x-th value="ACCIONES" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    
                    @if ($compras && $compras->count() > 0)
                        @foreach ($compras as $indice => $producto)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td value="{{ $producto->tiendaComercial->nombre }}" />
                                <x-td value="{{ $producto->fecha_compra }}" class="text-center" />
                                <x-td value="{{ $producto->costo_por_kg }}" class="text-center" />
                                <x-td value="{{ $producto->factura }}" class="text-center" />

                                <x-td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if ($producto->estado != '1')
                                            <x-warning-button wire:click="enable({{ $producto->id }})">
                                                <i class="fa fa-ban"></i>
                                            </x-warning-button>
                                        @else
                                            <x-success-button wire:click="disable({{ $producto->id }})">
                                                <i class="fa fa-check"></i>
                                            </x-success-button>
                                        @endif
                                        <x-secondary-button wire:click="editarCompra({{ $producto->id }})">
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
                    <x-tr>
                        <x-th>

                        </x-th>
                        <x-th>
                            <x-select class="uppercase" wire:model="tienda_comercial_id" id="tienda_comercial_id">
                                <option value="">TIENDA COMERCIAL</option>
                                @if ($proveedores)
                                    @foreach ($proveedores as $proveedor)
                                        <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                    @endforeach
                                @endif
                            </x-select>
                            <x-input-error for="tienda_comercial_id"/>
                        </x-th>
                        <x-th>
                            <x-input type="date" wire:model="fecha_compra" id="fecha_compra" />
                            <x-input-error for="fecha_compra"/>
                        </x-th>
                        <x-th>
                            <x-input type="number" wire:model="costo_por_kg" />
                            <x-input-error for="costo_por_kg"/>
                        </x-th>
                        <x-th>
                            <x-input type="text" class="uppercase" wire:model="factura" />
                        </x-th>
                        <x-th>
                            <x-button type="button" wire:click="agregarCompra" class="w-full">
                                Agregar
                            </x-button>
                        </x-th>
                    </x-tr>
                </x-slot>
            </x-table>
            @if ($compras && $compras->count() > 0)
            <div class="mt-5">
                {{ $compras->links() }}
            </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <div class="flex items-center justify-end gap-4">
                <x-secondary-button type="button" wire:click="closeForm" class="mr-2">Cerrar</x-secondary-button>
                @if($modo == 'step')
                <x-button type="button" wire:click="continuar" class="mr-2">Siguiente</x-button>
                @endif
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
