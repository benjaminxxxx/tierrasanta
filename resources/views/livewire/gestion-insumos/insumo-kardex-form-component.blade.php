<div>
    <x-dialog-modal wire:model.live="mostrarFormularioKardex" maxWidth="lg">
        <x-slot name="title">
            Crear Kardex - Formulario
        </x-slot>

        <x-slot name="content">
            <div x-data="insumoKardexForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- PRODUCTO --}}
                <x-group-field>
                    <x-label for="kardex.producto_id" value="Selecciona un Producto" />
                    <x-select-dropdown wire:model="kardex.producto_id" source="getProductos"
                        placeholder="-- Seleccione Producto --" />
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

                {{-- CHECKBOX: activa/desactiva el bloque de saldo inicial --}}
                <div class="md:col-span-2 border-t border-border pt-3">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" x-model="tieneSaldoInicial" class="rounded border-input">
                        <span class="text-sm font-medium">
                            Este kardex parte con saldo inicial (stock de apertura)
                        </span>
                    </label>
                    <p class="text-xs text-muted-foreground mt-1" x-show="!tieneSaldoInicial">
                        Si el producto no tenía stock previo, deja esto desmarcado - el kardex arrancará en cero
                        y su primer movimiento será la primera compra o salida que registres.
                    </p>
                </div>

                {{-- BLOQUE SALDO INICIAL CORREGIDO (Ocupa las 2 columnas base y organiza sub-filas) --}}
                <div x-show="tieneSaldoInicial" x-cloak x-transition
                    class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">

                    {{-- CÁLCULO DE COSTOS EN 3 COLUMNAS INTERNAS --}}
                    <div class="md:col-span-2">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- STOCK INICIAL --}}
                            <x-input type="number" label="Stock Inicial" x-model="stock" @input="calcularUnitario()"
                                step="0.001" error="kardex.stock_inicial" />

                            {{-- COSTO TOTAL --}}
                            <x-input type="number" label="Costo Total" x-model="costoTotal" @input="calcularUnitario()"
                                step="0.000001" error="kardex.costo_total" />

                            {{-- COSTO UNITARIO (Solo presentación en cliente) --}}
                            <x-input type="number" label="Costo Unitario" x-model="costoUnitario" readonly tabindex="-1"
                                step="0.000000000001" />
                        </div>
                    </div>

                    {{-- COMPROBANTE Y SERIES EN LA GRILLA SECUNDARIA --}}
                    <div class="md:col-span-2">
                        <x-select label="Tipo de comprobante (Tabla 10)" wire:model="kardex.tipo_compra_codigo_inicial"
                            error="kardex.tipo_compra_codigo_inicial" fullWidth="true">
                            <option value="">Seleccione</option>
                            @foreach ($tabla10TipoComprobantePago as $tipoCompra)
                                <option value="{{ $tipoCompra->codigo }}">{{ $tipoCompra->descripcion }}</option>
                            @endforeach
                        </x-select>
                    </div>

                    <x-input type="text" label="Serie de Stock Inicial" wire:model="kardex.serie_inicial"
                        error="kardex.serie_inicial" />

                    <x-input type="text" label="Número de Stock Inicial" wire:model="kardex.numero_inicial"
                        error="kardex.numero_inicial" />
                </div>
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
@script
<script>
    Alpine.data('insumoKardexForm', () => ({
        tieneSaldoInicial: @entangle('kardex.tiene_saldo_inicial'),
        stock: @entangle('kardex.stock_inicial'),
        costoTotal: @entangle('kardex.costo_total'),
        costoUnitario: 0,

        init() {
            // Se agrega 'this' para invocar el método local
            this.calcularUnitario();

            this.$watch('tieneSaldoInicial', (value) => {
                if (!value) {
                    this.stock = 0;
                    this.costoTotal = 0;
                    this.costoUnitario = 0;
                }
            });

            // Pasamos un callback para que el evento ejecute la función al emitirse
            Livewire.on('resetearCalculos', () => {
                this.quitarCalculados();
            });
        },

        calcularUnitario() {
            let cant = parseFloat(this.stock) || 0;
            let total = parseFloat(this.costoTotal) || 0;
            this.costoUnitario = (cant > 0) ? (total / cant).toFixed(6) : 0;
        },

        quitarCalculados() {
            this.stock = 0;
            this.costoTotal = 0;
            this.costoUnitario = 0;
        }
    }));
</script>
@endscript