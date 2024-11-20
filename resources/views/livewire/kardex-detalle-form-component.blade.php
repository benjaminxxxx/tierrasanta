<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Registro de Kardex para Producto
        </x-slot>

        <x-slot name="content">
            <div class="mb-3">
                <x-label for="kardex_id" value="Kardex Principal"/>
                @if ($kardex)
                <p>
                    <b>{{$kardex->nombre}}</b>
                </p>    
                @endif
                
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="mb-3">
                    <x-label for="productoId" value="Producto Asociado"/>
                    <x-select wire:model="productoId">
                        <option value="" selected>Seleccione un Producto</option>
                        @foreach($productosDisponibles as $producto)
                            <option value="{{ $producto->id }}">{{ $producto->nombre_completo }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error for="productoId" />
                </div>
                <div class="mb-3">
                    <x-label for="stockInicial" value="Stock Inicial"/>
                    <x-input type="number" wire:model="stockInicial" />
                    <x-input-error for="stockInicial" />
                </div>
                <div class="mb-3">
                    <x-label for="costoUnitario" value="Costo Unitario"/>
                    <x-input type="number" wire:model="costoUnitario" />
                    <x-input-error for="costoUnitario" />
                </div>
                <div class="mb-3">
                    <x-label for="metodoValuacion" value="Método de Valuación"/>
                    <x-select wire:model="metodoValuacion">
                        <option value="promedio">Promedio</option>
                    </x-select>
                    <x-input-error for="metodoValuacion" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="storeKardexProductoForm" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Registrar
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
</div>
