<div>
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Registro de compra
        </x-slot>

        <x-slot name="content">
            @if ($producto)
                <div class="mb-4">
                    <p class="font-bold">
                        PRODUCTO: {{ $producto->nombre_completo }}
                    </p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-label for="tienda_comercial_id">Proveedor</x-label>
                    <x-select class="uppercase" wire:model="tienda_comercial_id" id="tienda_comercial_id">
                        <option value="">Seleccione la tienda comercial</option>
                        @if ($proveedores)
                            @foreach ($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                            @endforeach
                        @endif
                    </x-select>
                    <x-input-error for="tienda_comercial_id" />
                </div>
                <div>
                    <x-label for="fecha_compra">Fecha compra</x-label>
                    <x-input type="date" wire:model="fecha_compra" id="fecha_compra" />
                    <x-input-error for="fecha_compra" />
                </div>
                <div class="col-span=1 md:col-span-2">
                    <div x-data="calculoTotal(@entangle('stock'), @entangle('costo_por_kg'), @entangle('total'))" class="flex items-center w-full gap-3">
                        <div>
                            <x-label for="stock">Stock en
                                @if ($producto)
                                    <span>({{ $producto->unidad_medida }})</span>
                                @endif
                            </x-label>
                            <x-input type="number" step="0.001" x-model="stock" />
                            <x-input-error for="stock" />
                        </div>
                        <i class="fa fa-times text-orange-600"></i>
                        <div>
                            <x-label for="costo_por_kg">Costo x
                                @if ($producto)
                                    <span>({{ $producto->unidad_medida }})</span>
                                @endif
                            </x-label>
                            <x-input type="number" readonly class="!bg-gray-100 disabled" x-model="costo_por_kg" />
                            <x-input-error for="costo_por_kg" />
                        </div>
                        <i class="fa fa-equals text-orange-600"></i>
                        <div>
                            <x-label for="total">Costo Total</x-label>
                            <x-input type="number" step="0.01" x-model="total" />
                            <x-input-error for="total" />
                        </div>
                    </div>
                    
                    <script>
                        function calculoTotal(stockRef, costoPorKgRef, totalRef) {
                            return {
                                stock: stockRef,
                                costo_por_kg: costoPorKgRef,
                                total: totalRef,
                                init() {
                                    // Observa cambios en el stock o el total
                                    this.$watch('stock', value => this.calcularCostoPorUnidad());
                                    this.$watch('total', value => this.calcularCostoPorUnidad());
                                },
                                calcularCostoPorUnidad() {
                                    if (this.stock > 0 && this.total >= 0) {
                                        this.costo_por_kg = this.total / this.stock;
                                    } else {
                                        this.costo_por_kg = 0;
                                    }
                                },
                            };
                        }
                    </script>
                    



                </div>


                <div>
                    <x-label for="serie">Serie</x-label>
                    <x-input type="text" class="uppercase" wire:model="serie" />
                </div>
                <div>
                    <x-label for="numero">Número</x-label>
                    <x-input type="text" class="uppercase" wire:model="numero" />
                </div>
                <div>
                    <x-label for="serie">Tipo de operación (Tabla 12)</x-label>
                    <x-input type="text" readonly class="uppercase !bg-gray-100" wire:model="tabla12TipoOperacion" />
                </div>
                <div>
                    <x-label for="tipoCompraSeleccionada">Tipo de comprobante (Tabla 10)</x-label>
                    <x-select wire:model="tipoCompraSeleccionada">
                        <option value="">Seleccione</option>
                        @foreach ($tabla10TipoComprobantePago as $tipoCompra)
                            <option value="{{ $tipoCompra->codigo }}">{{ $tipoCompra->descripcion }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error for="tipoCompraSeleccionada" />
                </div>
                <div>
                    <x-label for="tipoKardex">Tipo de Kardex</x-label>
                    <x-select wire:model="tipoKardex">
                        <option value="blanco">Blanco</option>
                        <option value="negro">Negro</option>
                    </x-select>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                Cancelar
            </x-secondary-button>

            <x-button class="ms-3" wire:click="store" wire:loading.attr="disabled">
                Registrar compra <i class="fa fa-save"></i>
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>

