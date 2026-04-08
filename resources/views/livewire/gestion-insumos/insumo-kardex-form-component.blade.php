<div>
    <x-dialog-modal wire:model.live="mostrarFormularioKardex" maxWidth="lg">
        <x-slot name="title">
            Crear Kardex de Insumos
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- PRODUCTO --}}
                <x-group-field>
                    <x-label for="kardex.producto_id" value="Selecciona un  Producto" />
                    <x-select-dropdown wire:model="kardex.producto_id" source="getProductos" placeholder="-- Seleccione Producto --"
                         />
                    <x-input-error for="kardex.producto_id" />
                </x-group-field>

                {{-- CÓDIGO EXISTENCIA --}}
                <x-input type="text" label="Código de Existencia" class="uppercase"
                    wire:model="kardex.codigo_existencia" maxlength="10" error="kardex.codigo_existencia" />

                {{-- AÑO --}}
                <x-input type="number" label="Año" wire:model="kardex.anio" error="kardex.anio" />

                {{-- TIPO --}}
                <x-select label="Tipo de Kardex" wire:model="kardex.tipo" error="kardex.tipo" fullWidth="true">
                    <option value="">-- Seleccione Tipo --</option>
                    <option value="blanco">Blanco</option>
                    <option value="negro">Negro</option>
                </x-select>

                {{-- STOCK INICIAL --}}
                <x-input type="number" label="Stock Inicial" wire:model="kardex.stock_inicial" step="0.001"
                    error="kardex.stock_inicial" />

                {{-- COSTO UNITARIO --}}
                <x-input type="number" label="Costo Unitario" wire:model="kardex.costo_unitario" step="0.000000000001"
                    error="kardex.costo_unitario" />

                {{-- COSTO TOTAL --}}
                <x-input type="number" label="Costo Total" wire:model="kardex.costo_total" step="0.000000000001"
                    error="kardex.costo_total" />
                <x-select label="Tipo de comprobante (Tabla 10)" wire:model="kardex.tipo_compra_codigo_inicial"
                    error="kardex.tipo_compra_codigo_inicial" fullWidth="true">
                    <option value="">Seleccione</option>
                    @foreach ($tabla10TipoComprobantePago as $tipoCompra)
                        <option value="{{ $tipoCompra->codigo }}">{{ $tipoCompra->descripcion }}</option>
                    @endforeach
                </x-select>
                <x-input type="text" label="Serie de Stock Inicial" wire:model="kardex.serie_inicial"
                    error="kardex.serie_inicial" />
                <x-input type="text" label="Numero de Stock Inicial" wire:model="kardex.numero_inicial"
                    error="kardex.numero_inicial" />

            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-button variant="secondary" wire:click="$set('mostrarFormularioKardex', false)"
                    wire:loading.attr="disabled">
                    Cerrar
                </x-button>

                <x-button wire:click="guardarKardex" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Registrar
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
