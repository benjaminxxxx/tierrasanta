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
                    {{-- Serie y número son opcionales (compras antiguas con datos perdidos); factura o boleta sin serie: solo aviso --}}
                    <p x-show="['01', '03'].includes(String($wire.tipo_comprobante_codigo)) && !String($wire.serie ?? '').trim()" x-cloak
                        class="text-xs text-amber-700 dark:text-amber-400 -mt-2">
                        <i class="fa fa-exclamation-triangle"></i> Toda factura o boleta debería tener serie. Se permite vacía solo para compras antiguas con datos perdidos.
                    </p>

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

                        <div>
                            {{-- Detalle en Handsontable (wire:ignore: Livewire no debe tocar la tabla). Eliminar: clic derecho. --}}
                            <div wire:ignore>
                                <div x-ref="tablaDetalle" class="w-full"></div>
                            </div>
                            <p x-show="detalles.length === 0" class="text-center py-3 text-muted-foreground italic text-sm">
                                Agregue productos a la compra usando el buscador superior.
                            </p>
                            <p x-show="detalles.length > 0" class="text-xs text-muted-foreground mt-1">
                                Edita presentación, cantidad, precio, descuento, IGV o el subtotal (recalcula el precio). Clic derecho sobre una fila para sacarla del detalle.
                            </p>
                        </div>

                        <div class="rounded-lg bg-muted p-4 text-right text-muted-foreground space-y-1 text-sm">
                            <div><span class="font-medium">Base imponible (sin IGV):</span> S/. <span
                                    x-text="formatNumber(subtotal)"></span></div>
                            <div><span class="font-medium">IGV (incluido, informativo):</span> S/. <span
                                    x-text="formatNumber(igvTotal)"></span></div>
                            <div class="text-amber-400 font-bold text-base">TOTAL = COSTO: S/. <span
                                    x-text="formatNumber(total)"></span></div>
                            <div class="text-xs">Sin crédito fiscal: el costo en el kardex es el total con IGV.</div>
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

            hot: null,
            sincronizando: false,

            init() {
                this.$watch('detalles', () => {
                    // Cambio desde el servidor (producto agregado, compra cargada): recargar la tabla.
                    // Si el cambio vino de la propia tabla (sincronizando), no se recarga.
                    if (!this.sincronizando) this.cargarTabla();
                    this.recalcularTotales();
                });
                this.recalcularTotales();
                this.$nextTick(() => this.crearTabla());
            },

            destroy() {
                this.hot && this.hot.destroy();
            },

            // ---------------------------------------------------------------- Handsontable del detalle
            etiquetaPresentacion(item, id) {
                const p = (item.presentaciones || []).find(p => p.id == id);
                return p ? `${p.nombre} x${parseInt(p.factor_conversion)}` : '(unidad base)';
            },

            filasTabla() {
                // Si llega como objeto ({0: …, 2: …}) se toma como lista
                return Object.values(this.detalles || {}).map(item => ({
                    ...JSON.parse(JSON.stringify(item)),
                    presentacion_label: this.etiquetaPresentacion(item, item.presentacion_id),
                    cantidad: item.cantidad === '' ? null : parseFloat(item.cantidad),
                    costo_unitario: item.costo_unitario === '' ? null : parseFloat(item.costo_unitario),
                    porcentaje_descuento: parseFloat(item.porcentaje_descuento) || 0,
                    porcentaje_igv: parseFloat(item.porcentaje_igv) || 0,
                    total_linea: item.total_linea === '' ? null : parseFloat(item.total_linea),
                    igv_linea: Math.round(this.igvLinea(item) * 100) / 100,
                }));
            },

            crearTabla() {
                const contenedor = this.$refs.tablaDetalle;
                if (!contenedor || this.hot) return;
                // Handsontable 18: numericFormat con opciones de Intl.NumberFormat
                const dinero = { minimumFractionDigits: 2, maximumFractionDigits: 2 };
                this.hot = new Handsontable(contenedor, {
                    ...window.HstConfig,
                    data: this.filasTabla(),
                    themeName: JSON.parse(localStorage.getItem('darkMode')) ? 'ht-theme-main-dark' : 'ht-theme-main',
                    colHeaders: true,
                    rowHeaders: true,
                    height: 'auto',
                    stretchH: 'all',
                    minSpareRows: 0,
                    columns: [
                        { data: 'producto_nombre', title: 'PRODUCTO', readOnly: true, width: 220, className: 'htLeft' },
                        { data: 'presentacion_label', title: 'PRESENTACIÓN', type: 'dropdown', width: 130, className: 'htLeft' },
                        { data: 'cantidad', title: 'CANT.', type: 'numeric', numericFormat: { maximumFractionDigits: 3 }, width: 70 },
                        { data: 'costo_unitario', title: 'PRECIO UNIT. (inc. IGV)', type: 'numeric', numericFormat: { minimumFractionDigits: 2, maximumFractionDigits: 6 }, width: 110 },
                        { data: 'porcentaje_descuento', title: 'DESC. %', type: 'numeric', numericFormat: dinero, width: 65 },
                        { data: 'porcentaje_igv', title: 'IGV %', type: 'numeric', numericFormat: dinero, width: 60 },
                        { data: 'igv_linea', title: 'IGV S/.', type: 'numeric', numericFormat: dinero, readOnly: true, width: 80, className: 'htRight !text-muted-foreground' },
                        { data: 'total_linea', title: 'SUBTOTAL (inc. IGV)', type: 'numeric', numericFormat: dinero, width: 110, className: 'htRight font-semibold' },
                    ],
                    // Presentaciones propias de cada producto
                    cells: (fila, col, prop) => {
                        if (prop !== 'presentacion_label') return {};
                        const item = this.hot?.getSourceDataAtRow(fila) || {};
                        return { source: ['(unidad base)', ...(item.presentaciones || []).map(p => `${p.nombre} x${parseInt(p.factor_conversion)}`)] };
                    },
                    contextMenu: {
                        items: {
                            eliminar: {
                                name: 'Sacar del detalle de compra',
                                callback: () => {
                                    const sel = this.hot.getSelectedLast();
                                    if (!sel) return;
                                    const desde = Math.min(sel[0], sel[2]);
                                    const hasta = Math.max(sel[0], sel[2]);
                                    this.hot.alter('remove_row', desde, hasta - desde + 1, 'eliminar');
                                    this.guardarDesdeTabla();
                                },
                            },
                        },
                    },
                    afterChange: (cambios, origen) => {
                        if (!cambios || origen === 'loadData' || origen === 'calculo') return;
                        cambios.forEach(([fila, prop, antes, ahora]) => {
                            if (antes === ahora) return;
                            const item = this.hot.getSourceDataAtRow(fila);
                            if (prop === 'presentacion_label') {
                                const p = (item.presentaciones || []).find(p => `${p.nombre} x${parseInt(p.factor_conversion)}` === ahora);
                                item.presentacion_id = p ? p.id : '';
                                item.factor_conversion = p ? p.factor_conversion : 1;
                            } else if (prop === 'total_linea') {
                                this.desdeSubtotal(item);
                            } else if (['cantidad', 'costo_unitario', 'porcentaje_descuento'].includes(prop)) {
                                this.desdePrecio(item);
                            }
                            item.igv_linea = Math.round(this.igvLinea(item) * 100) / 100;
                        });
                        this.hot.render();
                        this.guardarDesdeTabla();
                    },
                });
                // El modal se abre después: ajustar el ancho cuando el contenedor se hace visible
                new ResizeObserver(() => this.hot && this.hot.refreshDimensions()).observe(contenedor);
            },

            cargarTabla() {
                if (this.hot) this.hot.loadData(this.filasTabla());
            },

            // La tabla es la que se edita: sus filas pasan a `detalles` (lo que se envía al guardar)
            guardarDesdeTabla() {
                const filas = this.hot.getSourceData().map(f => {
                    const { presentacion_label, igv_linea, ...item } = f;
                    return {
                        ...item,
                        cantidad: item.cantidad ?? '',
                        costo_unitario: item.costo_unitario ?? '',
                        total_linea: item.total_linea ?? '',
                    };
                });
                this.sincronizando = true;
                this.detalles = filas;
                this.recalcularTotales();
                this.$nextTick(() => this.sincronizando = false);
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

            // Precio unitario y subtotal de línea SIEMPRE incluyen IGV (sin crédito fiscal,
            // el IGV es costo). El IGV solo se desglosa como dato informativo.
            factorDescuento(item) {
                return 1 - (parseFloat(item.porcentaje_descuento) || 0) / 100;
            },

            // Cambió cantidad, precio o descuento: se recalcula el subtotal de la línea.
            desdePrecio(item) {
                let cant = parseFloat(item.cantidad) || 0;
                let precio = parseFloat(item.costo_unitario) || 0;
                item.total_linea = Math.round(cant * precio * this.factorDescuento(item) * 100) / 100;
                this.recalcularTotales();
            },

            // Se ingresó el subtotal de la línea: se recalcula el precio unitario.
            desdeSubtotal(item) {
                let cant = parseFloat(item.cantidad) || 0;
                let divisor = cant * this.factorDescuento(item);
                if (divisor > 0) {
                    item.costo_unitario = Math.round((parseFloat(item.total_linea) || 0) / divisor * 1e6) / 1e6;
                }
                this.recalcularTotales();
            },

            totalLinea(item) {
                if (item.total_linea !== '' && item.total_linea !== null && item.total_linea !== undefined) {
                    return parseFloat(item.total_linea) || 0;
                }
                let cant = parseFloat(item.cantidad) || 0;
                let precio = parseFloat(item.costo_unitario) || 0;
                return cant * precio * this.factorDescuento(item);
            },

            igvLinea(item) {
                let total = this.totalLinea(item);
                let igvPorcentaje = parseFloat(item.porcentaje_igv) || 0;
                return total - total / (1 + igvPorcentaje / 100);
            },

            recalcularTotales() {
                let total = 0;
                let igv = 0;

                this.detalles.forEach(item => {
                    total += this.totalLinea(item);
                    igv += this.igvLinea(item);
                });

                this.total = Math.round(total * 100) / 100;
                this.igvTotal = Math.round(igv * 100) / 100;
                this.subtotal = Math.round((this.total - this.igvTotal) * 100) / 100;
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
