<div>
    <x-dialog-modal wire:model.live="mostrarModal" maxWidth="full">
        <x-slot name="title">
            {{ $modo === 'editar' ? __('Editar Compra') : __('Registro de compra') }}
        </x-slot>

        <x-slot name="content">
            <x-input-error for="general" class="mb-4" />
            <x-input-error for="detalles" class="mb-4" />

            {{-- Componente Alpine para reactividad instantánea en el cliente --}}
            <div x-data="compraForm({
                detalles: $wire.entangle('detalles'),
                subtotal: $wire.entangle('subtotal'),
                igvTotal: $wire.entangle('igvTotal'),
                total: $wire.entangle('total')
            })" class="grid gap-6 md:grid-cols-[360px_1fr]">

                {{-- ===== Columna izquierda: Datos de la compra ===== --}}
                <div class="space-y-4">
                    <div class="rounded-lg bg-amber-500 text-white text-center py-3 font-bold text-lg">
                        TOTAL S/. <span x-text="formatNumber(total)"></span>
                    </div>

                    <div>
                        <x-label for="proveedorId" value="Proveedor" />
                        <x-searchable-select :options="$proveedores" placeholder="Selecciona un proveedor"
                            search-placeholder="Escriba la razón social o RUC..." wire:model.live="proveedorId" />
                        <x-input-error for="proveedorId" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-label for="moneda" value="Moneda" />
                            <x-select id="moneda" wire:model.live="moneda" class="w-full">
                                <option value="PEN">Soles (PEN)</option>
                                <option value="USD">Dólares (USD)</option>
                            </x-select>
                            <x-input-error for="moneda" />
                        </div>

                        <div>
                            <x-label for="tipoCambio" value="T.C. (si es USD)" />
                            <x-input id="tipoCambio" type="number" step="0.0001" wire:model="tipoCambio"
                                class="w-full" :disabled="$moneda === 'PEN'" />
                            <x-input-error for="tipoCambio" />
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <x-select id="tipo_comprobante_codigo" wire:model="tipo_comprobante_codigo" label="Tipo"
                            class="w-full" error="tipo_comprobante_codigo">
                            <option value="">Seleccionar</option>
                            @foreach ($tipoComprobantes as $comprobante)
                                <option value="{{ $comprobante->codigo }}">{{ $comprobante->descripcion }}</option>
                            @endforeach

                        </x-select>

                        <div>

                            <x-input id="serie" type="text" wire:model="serie" label="Serie" placeholder="F001"
                                class="w-full" error="serie" />
                        </div>

                        <div>
                            <x-input id="numero" type="text" wire:model="numero" label="Número"
                                placeholder="00123" error="numero" />
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-label for="fechaEmision" value="Fecha emisión" />
                            <x-input id="fechaEmision" type="date" wire:model="fechaEmision" class="w-full" />
                            <x-input-error for="fechaEmision" />
                        </div>

                        <div>
                            <x-label for="fechaVencimiento" value="Fecha vencimiento" />
                            <x-input id="fechaVencimiento" type="date" wire:model="fechaVencimiento"
                                class="w-full" />
                            <x-input-error for="fechaVencimiento" />
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-label for="formaPago" value="Forma de pago" />
                            <x-select id="formaPago" wire:model="formaPago" class="w-full">
                                <option value="contado">Contado</option>
                                <option value="credito">Crédito</option>
                            </x-select>
                            <x-input-error for="formaPago" />
                        </div>

                        <div>
                            <x-label for="almacenId" value="Almacén" />
                            <x-select id="almacenId" wire:model="almacenId" class="w-full">
                                <option value="">Seleccionar...</option>
                                @foreach ($almacenes as $alm)
                                    <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                                @endforeach
                            </x-select>
                            <x-input-error for="almacenId" />
                        </div>
                        <div>
                            <x-select id="tipoKardex" wire:model="tipoKardex" label="Tipo de Kardex" error="tipoKardex">
                                <option value="blanco">Blanco (declarado)</option>
                                <option value="negro">Negro (no declarado)</option>
                            </x-select>
                        </div>
                    </div>

                    <div>
                        <x-textarea id="notas" label="Nota" error="notas" wire:model="notas" rows="2"
                            placeholder="Notas o detalles de la compra..." />
                    </div>
                </div>

                {{-- ===== Columna derecha: Productos y Totales ===== --}}
                <div class="space-y-4">
                    <div class="space-y-4">
                        <div>
                            <x-label for="productoSeleccionadoId" value="Buscar y agregar producto" />
                            <x-searchable-select :options="$productos" placeholder="Selecciona un producto..."
                                search-placeholder="Escriba el nombre o marca..."
                                wire:model.live="productoSeleccionadoId" />
                        </div>

                        <div class="overflow-x-auto">
                            <x-table>
                                <x-slot name="thead">
                                    <x-tr>
                                        <x-th class="text-left">Producto</x-th>
                                        <x-th class="text-left">Presentación</x-th>
                                        <x-th class="text-center">Cant.</x-th>
                                        <x-th class="text-center">Precio Unit.</x-th>
                                        <x-th class="text-center">Desc. (%)</x-th>
                                        <x-th class="text-center">IGV (%)</x-th>
                                        <x-th class="text-right">Total línea</x-th>
                                        <x-th class="text-center">Acción</x-th>
                                    </x-tr>
                                </x-slot>

                                <x-slot name="tbody">
                                    <template x-for="(item, index) in detalles" :key="index">
                                        <x-tr>
                                            <x-td class="text-xs font-medium" x-text="item.producto_nombre"></x-td>

                                            <x-td>
                                                <x-select size="small" x-model="item.presentacion_id"
                                                    @change="cambiarPresentacion(index)">
                                                    <option value="">(unidad base)</option>
                                                    <template x-for="pres in item.presentaciones"
                                                        :key="pres.id">
                                                        <option :value="pres.id"
                                                            x-text="`${pres.nombre} x${parseInt(pres.factor_conversion)}`">
                                                        </option>
                                                    </template>
                                                </x-select>
                                            </x-td>

                                            <x-td>
                                                <x-input type="number" step="1" x-model="item.cantidad"  />
                                            </x-td>

                                            <x-td>
                                                <x-input type="number" step="0.01" x-model="item.costo_unitario" />
                                            </x-td>

                                            <x-td>
                                                <x-input type="number" step="0.01"
                                                    x-model="item.porcentaje_descuento"  />
                                            </x-td>

                                            <x-td>
                                                <x-input type="number" step="0.01" x-model="item.porcentaje_igv" />
                                            </x-td>

                                            <x-td class="text-right text-xs font-semibold whitespace-nowrap">
                                                S/. <span x-text="formatNumber(calcularTotalLinea(item))"></span>
                                            </x-td>

                                            <x-td class="text-center">
                                                <x-button variant="danger" size="sm"
                                                    @click="eliminarDetalle(index)">
                                                    &times;
                                                </x-button>
                                            </x-td>
                                        </x-tr>
                                    </template>

                                    <template x-if="detalles.length === 0">
                                        <x-tr>
                                            <x-td colspan="8"
                                                class="text-center py-4 text-gray-500 italic text-sm">
                                                Agregue productos a la compra usando el buscador superior.
                                            </x-td>
                                        </x-tr>
                                    </template>
                                </x-slot>
                            </x-table>
                        </div>

                        <div class="rounded-lg bg-muted p-4 text-right text-muted-foreground space-y-1 text-sm">
                            <div><span class="font-medium">Subtotal Neto:</span> S/. <span
                                    x-text="formatNumber(subtotal)"></span></div>
                            <div><span class="font-medium">IGV (Impuesto):</span> S/. <span
                                    x-text="formatNumber(igvTotal)"></span></div>
                            <div class="text-amber-400 font-bold text-base">TOTAL: S/. <span
                                    x-text="formatNumber(total)"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="cerrarModal" wire:loading.attr="disabled" class="mr-2">
                {{ __('Cancelar') }}
            </x-button>

            <x-button variant="primary" wire:click="guardarCompraInsumo" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> {{ $modo === 'editar' ? __('Actualizar Compra') : __('Registrar Compra') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>

@script
    <script>
        Alpine.data('compraForm', (config) => ({
            detalles: config.detalles,
            subtotal: config.subtotal,
            igvTotal: config.igvTotal,
            total: config.total,

            init() {
                this.$watch('detalles', () => {
                    this.recalcularTotales();
                });
                this.recalcularTotales();
            },

            cambiarPresentacion(index) {
                let item = this.detalles[index];
                if (!item.presentacion_id) {
                    item.factor_conversion = 1;
                } else {
                    let pres = (item.presentaciones || []).find(p => p.id == item.presentacion_id);
                    item.factor_conversion = pres ? pres.factor_conversion : 1;
                }
                this.recalcularTotales();
            },

            calcularTotalLinea(item) {
                let cant = parseFloat(item.cantidad) || 0;
                let precio = parseFloat(item.costo_unitario) || 0;
                let descPorcentaje = parseFloat(item.porcentaje_descuento) || 0;
                let igvPorcentaje = parseFloat(item.porcentaje_igv) || 0;

                let base = cant * precio;
                let descuento = base * (descPorcentaje / 100);
                let neto = base - descuento;
                let igv = neto * (igvPorcentaje / 100);

                return neto + igv;
            },

            recalcularTotales() {
                let sub = 0;
                let igv = 0;

                this.detalles.forEach(item => {
                    let cant = parseFloat(item.cantidad) || 0;
                    let precio = parseFloat(item.costo_unitario) || 0;
                    let descPorcentaje = parseFloat(item.porcentaje_descuento) || 0;
                    let igvPorcentaje = parseFloat(item.porcentaje_igv) || 0;

                    let base = cant * precio;
                    let descuento = base * (descPorcentaje / 100);
                    let neto = base - descuento;
                    let itemIgv = neto * (igvPorcentaje / 100);

                    sub += neto;
                    igv += itemIgv;
                });

                this.subtotal = Math.round(sub * 100) / 100;
                this.igvTotal = Math.round(igv * 100) / 100;
                this.total = Math.round((this.subtotal + this.igvTotal) * 100) / 100;
            },

            eliminarDetalle(index) {
                this.detalles.splice(index, 1);
                this.recalcularTotales();
            },

            formatNumber(value) {
                return (parseFloat(value) || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }));
    </script>
@endscript
