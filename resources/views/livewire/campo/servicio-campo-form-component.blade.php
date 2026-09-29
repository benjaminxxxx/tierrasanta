<div>
    <x-modal wire:model="mostrarFormulario" maxWidth="complete">
        <div class="px-3 md:px-8 py-6 text-sm">
            <x-title class="mb-5">
                {{ $servicioId ? 'Editar servicio en campo' : 'Agregar servicio en campo' }}
            </x-title>
            <datalist id="sc-servicios">
                @foreach ($sugerenciasServicios as $s)
                    <option value="{{ $s }}"></option>
                @endforeach
            </datalist>
            <datalist id="sc-labores">
                @foreach ($sugerenciasLabores as $l)
                    <option value="{{ $l }}"></option>
                @endforeach
            </datalist>
            <datalist id="sc-unidades">
                @foreach ($sugerenciasUnidades as $u)
                    <option value="{{ $u }}"></option>
                @endforeach
            </datalist>

            <div x-data="servicioCampoForm" class="space-y-6">

                {{-- ── Sección 1: Servicio y comprobante ── --}}
                <div>
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-3">
                        Servicio y comprobante
                    </span>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-input label="Servicio (maquinaria)" wire:model="servicio" list="sc-servicios"
                            autocomplete="off" placeholder="Ej. Cargador frontal"
                            x-on:change="$wire.sugerirDesdeServicio()" error="servicio" />

                        <x-input label="Unidad de distribución" x-model="unidad" list="sc-unidades"
                            autocomplete="off" placeholder="hora, kilo, tonelada..." error="unidad" />

                        <x-select label="Tipo de recibo" x-model="tipoComprobante" x-on:change="cambiarComprobante()">
                            @foreach (\App\Models\ServicioCampo::COMPROBANTES as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </x-select>

                        <x-input label="N° comprobante (opcional)" wire:model="numeroComprobante"
                            placeholder="F001-000123" />

                        <x-select label="Tipo de costo" x-model="tipoCosto">
                            @foreach (\App\Models\ServicioCampo::TIPOS_COSTO as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </x-select>

                        <x-input type="date" label="Fecha del comprobante" wire:model="fechaComprobante"
                            error="fechaComprobante" />

                        <x-input type="number" step="0.01" min="0" label="% IGV" x-model="porcentajeIgv"
                            x-on:input="desdeUnitario()" />

                        <div>
                            <x-input type="number" step="any" min="0" x-model="costoUnitario"
                                x-on:input="desdeUnitario()" error="costoUnitario"
                                label="Costo por unidad" />
                            <p class="text-xs text-muted-foreground mt-1">
                                Por <span x-text="unidad || 'unidad'"></span>,
                                <span x-text="esFactura ? 'SIN IGV (factura)' : 'CON IGV (monto pagado)'"></span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- ── Sección 2: Campos trabajados ── --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                            Detalle por campo
                        </span>
                        <x-button size="sm" variant="secondary" x-on:click="agregarFila()">
                            <i class="fa fa-plus"></i> Agregar campo
                        </x-button>
                    </div>

                    @error('detalles')
                        <span class="text-xs text-red-600 block mb-2">{{ $message }}</span>
                    @enderror

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-muted-foreground border-b border-border">
                                    <th class="p-2 w-40">Fecha</th>
                                    <th class="p-2 w-40">Campo</th>
                                    <th class="p-2">Labor</th>
                                    <th class="p-2 w-32 text-right">
                                        <span x-text="unidad ? 'Cant. (' + unidad + ')' : 'Cantidad'"></span>
                                    </th>
                                    <th class="p-2 w-32 text-right">Costo</th>
                                    <th class="p-2">Campaña</th>
                                    <th class="p-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(d, i) in detalles" :key="i">
                                    <tr class="border-b border-border align-top">
                                        <td class="p-2">
                                            <x-input type="date" size="sm" x-model="d.fecha"
                                                x-on:change="cambioCampoFecha(i)" />
                                        </td>
                                        <td class="p-2">
                                            <x-select size="small" x-model="d.campo" x-on:change="cambioCampoFecha(i)">
                                                <option value="">-- Campo --</option>
                                                @foreach ($campos as $campo)
                                                    <option value="{{ $campo }}">{{ $campo }}</option>
                                                @endforeach
                                            </x-select>
                                        </td>
                                        <td class="p-2">
                                            <x-input size="sm" x-model="d.labor" list="sc-labores" autocomplete="off"
                                                placeholder="Ej. nivelación" />
                                        </td>
                                        <td class="p-2">
                                            <x-input type="number" size="sm" step="any" min="0" class="text-right"
                                                x-model="d.cantidad" x-on:input="desdeUnitario()" />
                                        </td>
                                        <td class="p-2 text-right font-medium tabular-nums"
                                            x-text="formato(costoFila(d))" title="Incluye IGV"></td>
                                        <td class="p-2 text-xs">
                                            <template x-if="d.campania === 'cargando'">
                                                <span class="text-muted-foreground"><i class="fa fa-spinner fa-spin"></i> Buscando...</span>
                                            </template>
                                            <template x-if="d.campania && d.campania !== 'cargando'">
                                                <div>
                                                    <span class="font-semibold" x-text="d.campania.nombre"></span>
                                                    <span class="block text-muted-foreground"
                                                        x-text="d.campania.fecha_inicio + ' → ' + (d.campania.fecha_fin ?? 'abierta')"></span>
                                                    <span x-show="!d.campania.fecha_fin"
                                                        class="block text-amber-600 dark:text-amber-400">
                                                        <i class="fa fa-exclamation-triangle"></i> Campaña sin cerrar: verifica que corresponda.
                                                    </span>
                                                </div>
                                            </template>
                                            <template x-if="!d.campania && d.campo && d.fecha">
                                                <span class="text-red-600 dark:text-red-400 font-semibold">
                                                    <i class="fa fa-ban"></i> Sin campaña vigente en esta fecha. Cree la campaña primero.
                                                </span>
                                            </template>
                                        </td>
                                        <td class="p-2 text-center">
                                            <x-button size="xs" variant="danger" x-on:click="quitarFila(i)"
                                                x-show="detalles.length > 1" title="Quitar">
                                                <i class="fa fa-trash"></i>
                                            </x-button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr class="font-semibold">
                                    <td class="p-2 text-right" colspan="3">Totales</td>
                                    <td class="p-2 text-right tabular-nums" x-text="formato(cantidadTotal, 3)"></td>
                                    <td class="p-2 text-right tabular-nums" x-text="formato(montoCosto)"></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- ── Sección 3: Montos del comprobante ── --}}
                <div>
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-3">
                        Monto del comprobante
                    </span>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input type="number" step="0.01" min="0" label="Subtotal (sin IGV)" x-model="subtotal"
                            x-on:input="desdeSubtotal()" />
                        <x-input type="number" step="0.01" label="IGV" x-model="igv" readonly />
                        <x-input type="number" step="0.01" min="0" label="Total pagado" x-model="total"
                            x-on:input="desdeTotal()" />
                    </div>
                    <p class="text-xs text-muted-foreground mt-2">
                        Costo que se asigna a los campos: <strong>total pagado (con IGV)</strong>,
                        porque no hay crédito fiscal (la venta de cochinilla está exonerada de IGV).
                        Si modificas el total o el subtotal se recalcula el costo por unidad.
                    </p>
                </div>

                <x-callout variant="danger" x-show="filasSinCampania > 0" x-cloak>
                    <span x-text="filasSinCampania"></span> fila(s) no tienen campaña vigente. No se puede guardar hasta corregirlo.
                </x-callout>

                @if ($errors->any())
                    <x-callout variant="danger">
                        <ul class="list-disc pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-callout>
                @endif

                <div class="flex justify-end gap-3 pt-4 border-t border-border">
                    <x-button variant="secondary" @click="$wire.set('mostrarFormulario', false)">
                        Cancelar
                    </x-button>
                    <x-button wire:click="guardar" x-bind:disabled="!puedeGuardar">
                        <i class="fa fa-save"></i> Guardar
                    </x-button>
                </div>
            </div>
        </div>
    </x-modal>
</div>

@script
<script>
    Alpine.data('servicioCampoForm', () => ({
        detalles: $wire.entangle('detalles'),
        unidad: $wire.entangle('unidad'),
        costoUnitario: $wire.entangle('costoUnitario'),
        subtotal: $wire.entangle('subtotal'),
        igv: $wire.entangle('igv'),
        total: $wire.entangle('total'),
        tipoComprobante: $wire.entangle('tipoComprobante'),
        tipoCosto: $wire.entangle('tipoCosto'),
        porcentajeIgv: $wire.entangle('porcentajeIgv'),

        init() {
            $wire.on('servicio-campo-recalcular', () => this.$nextTick(() => this.desdeUnitario()));
        },

        num(v) {
            const n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        },
        r2(v) {
            return Math.round((v + Number.EPSILON) * 100) / 100;
        },
        formato(v, decimales = 2) {
            return this.num(v).toLocaleString('es-PE', { minimumFractionDigits: decimales, maximumFractionDigits: decimales });
        },

        get esFactura() {
            return this.tipoComprobante === 'factura';
        },
        get tasa() {
            return this.num(this.porcentajeIgv) / 100;
        },
        get cantidadTotal() {
            return (this.detalles || []).reduce((s, d) => s + this.num(d.cantidad), 0);
        },
        // Monto en la base del costo unitario del comprobante: subtotal en factura, total en los demás.
        get montoComprobante() {
            return this.num(this.esFactura ? this.subtotal : this.total);
        },
        // Sin crédito fiscal: el costo asignado a los campos siempre es el total pagado (con IGV).
        get montoCosto() {
            return this.num(this.total);
        },
        get filasSinCampania() {
            return (this.detalles || []).filter(d => d.campo && d.fecha && !d.campania).length;
        },
        get puedeGuardar() {
            const cargando = (this.detalles || []).some(d => d.campania === 'cargando');
            return !cargando && this.filasSinCampania === 0 && this.montoCosto > 0;
        },
        costoFila(d) {
            const q = this.cantidadTotal;
            return q > 0 ? this.montoCosto * this.num(d.cantidad) / q : 0;
        },

        // El costo unitario manda: recalcula subtotal, IGV y total.
        desdeUnitario() {
            const base = this.r2(this.cantidadTotal * this.num(this.costoUnitario));
            if (this.esFactura) {
                this.subtotal = base;
                this.total = this.r2(base * (1 + this.tasa));
            } else {
                this.total = base;
                this.subtotal = this.r2(base / (1 + this.tasa));
            }
            this.igv = this.r2(this.num(this.total) - this.num(this.subtotal));
        },
        desdeTotal() {
            this.subtotal = this.r2(this.num(this.total) / (1 + this.tasa));
            this.igv = this.r2(this.num(this.total) - this.subtotal);
            this.actualizarUnitario();
        },
        desdeSubtotal() {
            this.total = this.r2(this.num(this.subtotal) * (1 + this.tasa));
            this.igv = this.r2(this.total - this.num(this.subtotal));
            this.actualizarUnitario();
        },
        actualizarUnitario() {
            const q = this.cantidadTotal;
            if (q > 0) {
                this.costoUnitario = Math.round(this.montoComprobante / q * 1e6) / 1e6;
            }
        },

        cambiarComprobante() {
            // Sugerencia: factura va a blanco, el resto a negro (se puede cambiar libremente).
            this.tipoCosto = this.esFactura ? 'blanco' : 'negro';
            this.desdeUnitario();
        },
        agregarFila() {
            const previa = this.detalles[this.detalles.length - 1];
            this.detalles.push({
                fecha: previa ? previa.fecha : $wire.fechaComprobante,
                campo: '',
                labor: previa ? previa.labor : '',
                cantidad: '',
                campania: null,
            });
        },
        quitarFila(i) {
            this.detalles.splice(i, 1);
            this.desdeUnitario();
        },
        cambioCampoFecha(i) {
            const d = this.detalles[i];
            if (!d.campo || !d.fecha) {
                d.campania = null;
                return;
            }
            d.campania = 'cargando';
            $wire.resolverCampania(i);
        },
    }));
</script>
@endscript
