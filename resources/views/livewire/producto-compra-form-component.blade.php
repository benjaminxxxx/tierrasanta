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

                <x-select class="uppercase" label="Proveedor" wire:model="tienda_comercial_id" id="tienda_comercial_id"
                    error="tienda_comercial_id">
                    <option value="">Seleccione la tienda comercial</option>
                    @if ($proveedores)
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                        @endforeach
                    @endif
                </x-select>

                <x-input type="date" label="Fecha compra" wire:model="fecha_compra" id="fecha_compra"
                    error="fecha_compra" />

                <div class="col-span=1 md:col-span-2">
                    <div x-data="calculoTotal(@entangle('stock'), @entangle('costo_por_kg'), @entangle('total'))"
                        class="flex items-center w-full gap-3">
                        <div>
                            <x-label for="stock">Stock en
                                @if ($producto)
                                    <span>({{ $producto->unidad_medida }})</span>
                                @endif
                            </x-label>
                            <x-input type="number" step="0.001" x-model="stock" error="stock" />
                        </div>
                        <i class="fa fa-times text-orange-600"></i>
                        <div>
                            <x-label for="costo_por_kg">Costo x
                                @if ($producto)
                                    <span>({{ $producto->unidad_medida }})</span>
                                @endif
                            </x-label>
                            <x-input type="number" readonly class="disabled" x-model="costo_por_kg" />
                            <x-input-error for="costo_por_kg" />
                        </div>
                        <i class="fa fa-equals text-orange-600"></i>
                        <div>
                            <x-input type="number" label="Costo Total" step="0.01" x-model="total" error="total" />
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

                <x-input type="text" label="Serie" class="uppercase" wire:model="serie" />

                <x-input type="text" label="Número" class="uppercase" wire:model="numero" />

                <x-input type="text" label="Tipo de operación (Tabla 12)" readonly class="uppercase"
                    wire:model="tabla12TipoOperacion" />

                <x-select label="Tipo de comprobante (Tabla 10)" wire:model="tipoCompraSeleccionada"
                    error="tipoCompraSeleccionada" fullWidth="true">
                    <option value="">Seleccione</option>
                    @foreach ($tabla10TipoComprobantePago as $tipoCompra)
                        <option value="{{ $tipoCompra->codigo }}">{{ $tipoCompra->descripcion }}</option>
                    @endforeach
                </x-select>

                <div>
                    <x-select wire:model.live="tipoKardex" label="Tipo de Kardex" fullWidth="true">
                        <option value="blanco">Blanco</option>
                        <option value="negro">Negro</option>
                    </x-select>
                    <p>
                        {{$mensajeAlCambiarTipoKardex}}
                    </p>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button wire:click="$set('mostrarFormulario', false)" variant="secondary" wire:loading.attr="disabled">
                Cancelar
            </x-button>

            <x-button class="ms-3" wire:click="guardarCompraInsumo" wire:loading.attr="disabled">
                Registrar compra <i class="fa fa-save"></i>
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>