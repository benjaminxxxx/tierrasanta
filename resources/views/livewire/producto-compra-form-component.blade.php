<div>
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Registro de compra
        </x-slot>

        <x-slot name="content">
            @if ($producto)
                <div class="mb-4">
                    <p class="font-bold">
                        PRODUCTO: {{ $producto->nombre_comercial }} - {{ $producto->ingrediente_activo }}
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
                    <div x-data="calculoTotal" class="flex items-center w-full gap-3">
                        <div>
                            <x-label for="costo_por_kg">Costo x
                                @if ($producto)
                                    <span>({{ $producto->unidad_medida }})</span>
                                @endif
                            </x-label>
                            <x-input type="number" x-model.number="costoPorKg" @change="actualizarTotal"
                                wire:model.live="costo_por_kg" />
                            <x-input-error for="costo_por_kg" />
                        </div>
                        <i class="fa fa-times text-orange-600"></i>
                        <div>
                            <x-label for="stock">Stock en
                                @if ($producto)
                                    <span>({{ $producto->unidad_medida }})</span>
                                @endif
                            </x-label>
                            <x-input type="number" x-model.number="cantidad" @change="actualizarTotal"
                                wire:model.live="stock" />
                            <x-input-error for="stock" />
                        </div>
                        <i class="fa fa-equals text-orange-600"></i>
                        <div>
                            <x-label for="total">Total</x-label>
                            <x-input type="number" x-model.number="total" @change="actualizarSubtotal"
                                wire:model.live="total" />
                            <x-input-error for="total" />
                        </div>
                    </div>



                </div>


                <div>
                    <x-label for="factura">Factura</x-label>
                    <x-input type="text" class="uppercase" wire:model="factura" />
                    <small>
                        Colocar NG si es un Producto sin factura
                    </small>
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
@script
    <script>
        Alpine.data('calculoTotal', () => ({
            costoPorKg: @entangle('costo_por_kg').defer,
            cantidad: @entangle('stock').defer,
            total: @entangle('total').defer,

            actualizarTotal() {
                if(this.costoPorKg==undefined){
                    this.costoPorKg = $wire.get('costo_por_kg');
                }
                if(this.cantidad==undefined){
                    this.cantidad = $wire.get('stock');
                }
                if (this.costoPorKg && this.cantidad) {
                    this.total = this.costoPorKg * this.cantidad;                    
                    $wire.set('total',this.total);
                }
            },

            actualizarSubtotal() {
                if(this.cantidad==undefined){
                    this.cantidad = $wire.get('stock');
                }
                this.costoPorKg = this.total / this.cantidad;
                $wire.set('costo_por_kg',this.costoPorKg);
            }
        }));
    </script>
@endscript