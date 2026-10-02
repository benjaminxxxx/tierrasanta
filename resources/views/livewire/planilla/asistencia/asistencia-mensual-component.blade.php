<div class="space-y-4" x-data="asistenciaMensual">
    <x-flex class="justify-between flex-wrap gap-3">
        <div>
            <x-title>Asistencia Mensual — {{ $nombreMes }}</x-title>
            <x-subtitle>Horas, sueldo pagado y costo para la empresa de cada empleado, día por día.</x-subtitle>
        </div>
        <x-flex>
            @include('comun.selector-mes-base')
        </x-flex>
    </x-flex>

    {{-- Tarjetas: siempre de las filas que se están viendo (se recalculan al filtrar) --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Empleados</span>
            <span class="text-xl font-semibold tabular-nums" x-text="resumen.empleados"></span>
            <span class="text-xs text-muted-foreground" x-show="resumen.empleados !== datos.empleados.length" x-text="'de ' + datos.empleados.length"></span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Total de horas</span>
            <span class="text-xl font-semibold tabular-nums" x-text="num(resumen.horas, 0, 2)"></span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Sueldo pagado</span>
            <span class="text-xl font-semibold tabular-nums" x-text="datos.planilla_generada ? 'S/ ' + num(resumen.pagado) : '—'"></span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Costo para la empresa</span>
            <span class="text-xl font-bold tabular-nums" x-text="datos.planilla_generada ? 'S/ ' + num(resumen.costo) : '—'"></span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Faltas</span>
            <span class="text-xl font-semibold tabular-nums text-red-700 dark:text-red-400" x-text="resumen.faltas"></span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Riego sin sincronizar</span>
            <span class="text-xl font-semibold tabular-nums" :class="resumen.riego ? 'text-amber-600 dark:text-amber-400' : ''" x-text="resumen.riego"></span>
        </x-card>
    </div>

    <template x-if="!datos.planilla_generada">
        <x-warning>
            La planilla de {{ $nombreMes }} aún no se ha generado: solo se muestran las horas. Los sueldos aparecen al generar la planilla del mes.
        </x-warning>
    </template>
    <template x-if="desactualizados > 0">
        <x-warning>
            <span x-text="desactualizados"></span> empleado(s) tienen horas distintas a las de la planilla generada (se registraron o corrigieron después):
            su sueldo pagado y su costo no corresponden a las horas que se ven. Vuelve a generar la planilla del mes.
        </x-warning>
    </template>

    <x-card class="space-y-3">
        <div class="flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-64">
                <x-input type="search" label="Buscar" x-model.debounce.300ms="busqueda" placeholder="Nombre o documento…" />
            </div>
            <div>
                <x-label>Grupo (según contrato)</x-label>
                <select x-model="grupo" class="h-9 px-2 rounded-md border border-input bg-background text-foreground text-sm">
                    <option value="">Todos</option>
                    <template x-for="g in datos.grupos" :key="g">
                        <option :value="g" x-text="g"></option>
                    </template>
                </select>
            </div>
            <div>
                <x-label>Con algún día de</x-label>
                <select x-model="tipo" class="h-9 px-2 rounded-md border border-input bg-background text-foreground text-sm">
                    <option value="">Cualquier asistencia</option>
                    <template x-for="t in tiposUsados" :key="t.codigo">
                        <option :value="t.codigo" x-text="t.codigo + ' — ' + t.descripcion"></option>
                    </template>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm h-9">
                <input type="checkbox" x-model="soloRiego" class="rounded"> Solo con riego sin sincronizar
            </label>

            <div class="inline-flex rounded-md border border-input overflow-hidden h-9 ml-auto">
                <template x-for="v in vistas" :key="v.clave">
                    <button type="button" class="px-3 text-sm font-semibold disabled:opacity-40"
                        :class="vista === v.clave ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
                        :disabled="v.clave !== 'horas' && !datos.planilla_generada" :title="v.ayuda"
                        x-on:click="vista = v.clave" x-text="v.nombre"></button>
                </template>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
            <span>
                Cada día muestra <b x-text="vistas.find(v => v.clave === vista).nombre.toLowerCase()"></b>; el color es el tipo de asistencia (pasa el mouse para verlo).
                Clic derecho sobre un día: ir a su detalle o sumar la selección.
                <span class="inline-block px-1 rounded" style="box-shadow: inset 0 0 0 2px #F59E0B">⚠ borde naranja</span> = horas de riego sin sincronizar.
            </span>
            <x-button size="xs" variant="outline" wire:click="$set('mostrandoModalOrden', true)"><i class="fa fa-sort"></i> Cambiar orden</x-button>
        </div>
    </x-card>

    {{-- Tabla extendida a su tamaño completo: sin scroll propio, se recorre con el scroll de la página --}}
    <div wire:ignore>
        <div x-ref="tabla"></div>
    </div>

    {{-- Modal de configuración de orden --}}
    <x-dialog-modal wire:model.live="mostrandoModalOrden">
        <x-slot name="title">Configurar orden de la planilla</x-slot>
        <x-slot name="content">
            <div class="space-y-3">
                <p class="text-sm text-zinc-500">
                    Elige el orden de prioridad para ordenar la lista de empleados. Cada campo solo puede usarse una vez.
                </p>
                <template x-for="(fila, index) in filas" :key="index">
                    <div class="flex items-center gap-2">
                        <span class="w-6 text-sm text-zinc-400" x-text="index + 1"></span>
                        <x-select x-model="fila.campo" x-init="$nextTick(() => $el.value = fila.campo)">
                            <option value="">Seleccionar campo...</option>
                            <template x-for="opcion in camposOrdenables" :key="opcion.value">
                                <option :value="opcion.value" :disabled="estaUsadoEnOtraFila(opcion.value, index)" x-text="opcion.label"></option>
                            </template>
                        </x-select>
                        <x-select x-model="fila.direccion">
                            <option value="asc">Ascendente</option>
                            <option value="desc">Descendente</option>
                        </x-select>
                        <x-button type="button" variant="danger" x-on:click="quitarFila(index)" x-show="filas.length > 1">
                            <i class="fa fa-times"></i>
                        </x-button>
                    </div>
                </template>
                <button type="button" x-on:click="agregarFila()" x-show="hayCamposDisponibles()" class="text-sm text-indigo-600 hover:text-indigo-800">
                    + Agregar otro criterio de orden
                </button>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrandoModalOrden', false)" wire:loading.attr="disabled">Cancelar</x-button>
            <x-button x-on:click="guardar()" wire:loading.attr="disabled">Guardar orden</x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="mes,anio,mesAnterior,mesSiguiente,guardarOrdenConfiguracion" />
</div>

@script
<script>
    Alpine.data('asistenciaMensual', () => ({
        hot: null,
        datos: @js($datos),
        enlaces: @js($enlaces),
        busqueda: '',
        grupo: '',
        tipo: '',
        soloRiego: false,
        vista: 'horas',
        vistas: [
            { clave: 'horas', nombre: 'Horas', ayuda: 'Horas trabajadas cada día' },
            { clave: 'pagado', nombre: 'Sueldo pagado', ayuda: 'Lo que se calcula para pagarle al trabajador, repartido por sus horas' },
            { clave: 'costo', nombre: 'Costo empresa', ayuda: 'Lo pagado más sus aportes y los aportes del empleador, repartido por sus horas' },
        ],
        resumen: { empleados: 0, horas: 0, pagado: 0, costo: 0, faltas: 0, riego: 0 },
        desactualizados: 0,
        camposOrdenables: @js($camposOrdenables),
        filas: @js(count($ordenGuardado) ? $ordenGuardado : [['campo' => '', 'direccion' => 'asc']]),

        FALTA: 'F',
        ANCHO_NUMERO: 40,
        DOMINGO: '#FFC000',

        init() {
            this.crearTabla();
            ['busqueda', 'grupo', 'tipo', 'soloRiego'].forEach(p => this.$watch(p, () => this.cargarFilas()));
            // Los montos necesitan columnas más anchas que las horas
            this.$watch('vista', () => {
                this.hot.updateSettings({ columns: this.columnas(), width: this.anchoTabla() });
                this.cargarFilas();
            });
            // Cambio de mes u orden: llegan los datos nuevos (los días del mes también cambian)
            Livewire.on('asistencia-datos', ({ datos }) => {
                this.datos = datos;
                if (!datos.planilla_generada) this.vista = 'horas';
                if (this.grupo && !datos.grupos.includes(this.grupo)) this.grupo = '';
                this.hot.updateSettings({ columns: this.columnas(), colHeaders: this.encabezados(), width: this.anchoTabla() });
                this.cargarFilas();
            });
        },

        num(v, min = 2, max = 2) {
            return v === null || v === undefined || v === '' ? '' :
                Number(v).toLocaleString('es-PE', { minimumFractionDigits: min, maximumFractionDigits: max });
        },

        get tiposUsados() {
            const usados = new Set();
            this.datos.empleados.forEach(e => Object.values(e.dias).forEach(d => d.t && usados.add(d.t)));
            return [...usados].sort().map(codigo => ({ codigo, descripcion: this.datos.tipos[codigo]?.descripcion ?? codigo }));
        },

        filtrados() {
            const q = this.busqueda.trim().toLowerCase();
            return this.datos.empleados.filter(e =>
                (!q || e.nombre.toLowerCase().includes(q) || (e.documento || '').includes(q))
                && (!this.grupo || e.grupo === this.grupo)
                && (!this.tipo || Object.values(e.dias).some(d => d.t === this.tipo))
                && (!this.soloRiego || e.alertas_riego > 0));
        },

        // Valor de un día en la vista actual: horas, o el monto del mes repartido por las horas del día
        valorDia(e, d) {
            if (!d || !d.h) return null;
            if (this.vista === 'horas') return d.h;
            const total = e[this.vista];
            return total === null || !e.total_horas ? null : Math.round(total * d.h / e.total_horas * 100) / 100;
        },

        cargarFilas() {
            const empleados = this.filtrados();
            const r = { empleados: empleados.length, horas: 0, pagado: 0, costo: 0, faltas: 0, riego: 0 };
            const filas = empleados.map(e => {
                const fila = { _e: e, nombre: e.nombre, total_horas: e.total_horas, pagado: e.pagado, costo: e.costo };
                this.datos.dias.forEach(({ dia }) => fila['d' + dia] = this.valorDia(e, e.dias[dia]));
                r.horas += e.total_horas;
                r.pagado += e.pagado ?? 0;
                r.costo += e.costo ?? 0;
                r.faltas += Object.values(e.dias).filter(d => d.t === this.FALTA).length;
                r.riego += e.alertas_riego;
                return fila;
            });
            this.resumen = r;
            this.desactualizados = this.datos.empleados.filter(e => e.desactualizado).length;
            this.hot.loadData(filas);
        },

        // ------------------------------------------------------------------ tabla

        encabezados() {
            return ['Nombres', 'Horas', 'Pagado', 'Costo', ...this.datos.dias.map(d => `${d.letra}<br>${d.dia}`)];
        },

        columnas() {
            const self = this;
            const css = (td, prop, valor) => valor ? td.style.setProperty(prop, valor, 'important') : td.style.removeProperty(prop);
            const fila = (row) => self.hot.getSourceDataAtRow(self.hot.toPhysicalRow(row));

            const nombre = function (instance, td, row, col, prop, value, cellProperties) {
                Handsontable.renderers.TextRenderer.apply(this, arguments);
                const e = fila(row)?._e;
                // El grupo no ocupa columna: es la franja de color a la izquierda del nombre
                css(td, 'border-left', e?.grupo_color ? `4px solid ${e.grupo_color}` : null);
                td.title = e ? [e.documento, e.grupo ? `Grupo ${e.grupo}` : 'Sin grupo en su contrato'].filter(Boolean).join(' · ') : '';
            };
            const total = (dec) => function (instance, td, row, col, prop, value, cellProperties) {
                Handsontable.renderers.TextRenderer.apply(this, [instance, td, row, col, prop, self.num(value, dec === 2 ? 2 : 0, 2), cellProperties]);
                td.classList.add('htRight');
                const e = fila(row)?._e;
                const viejo = prop !== 'total_horas' && e?.desactualizado;
                css(td, 'font-weight', '600');
                css(td, 'color', viejo ? '#D97706' : null);
                td.title = viejo ? 'Sus horas cambiaron después de generar la planilla: vuelve a generarla' : '';
            };
            const dia = (info) => function (instance, td, row, col, prop, value, cellProperties) {
                const e = fila(row)?._e;
                const d = e?.dias[info.dia];
                const tipo = d?.t ? self.datos.tipos[d.t] : null;
                const esFalta = d?.t === self.FALTA;

                // Siempre las horas (o su monto), aunque sea descanso médico, vacaciones…; solo la falta muestra su código.
                // Sin horas y con un tipo distinto de asistido (licencia sin goce, renuncia…) se ve el código.
                let texto = '';
                if (esFalta) texto = self.FALTA;
                else if (value !== null && value !== undefined) texto = self.num(value, self.vista === 'horas' ? 0 : 2, 2);
                else if (d?.t && d.t !== 'A' && !d.h) texto = d.t;

                Handsontable.renderers.TextRenderer.apply(this, [instance, td, row, col, prop, texto, cellProperties]);
                td.classList.add('htCenter');

                const fondo = tipo?.color || (info.domingo ? self.DOMINGO : null);
                css(td, 'background', fondo);
                css(td, 'color', fondo ? '#111827' : null);
                css(td, 'font-weight', esFalta ? '700' : null);
                css(td, 'box-shadow', d?.ra ? 'inset 0 0 0 2px #F59E0B' : null);
                css(td, 'cursor', d?.ra ? 'pointer' : null);

                const partes = [];
                if (tipo) partes.push(`${d.t} — ${tipo.descripcion}` + (d.h ? ` · ${self.num(d.h, 0, 2)} h` : ''));
                if (d?.rh !== undefined) {
                    partes.push(d.ra
                        ? `⚠ Riego sin sincronizar: ${self.num(d.rh, 0, 2)} h en riego, ${self.num(d.h || 0, 0, 2)} h en el registro diario. Clic para ir a corregirlo.`
                        : `Riego: ${self.num(d.rh, 0, 2)} h (sincronizado)`);
                }
                td.title = partes.join('\n');
            };

            return [
                { data: 'nombre', width: 240, renderer: nombre },
                { data: 'total_horas', width: 58, renderer: total(0) },
                { data: 'pagado', width: 82, renderer: total(2) },
                { data: 'costo', width: 82, renderer: total(2) },
                ...this.datos.dias.map(d => ({ data: 'd' + d.dia, width: this.vista === 'horas' ? 40 : 62, renderer: dia(d), _dia: d })),
            ];
        },

        // Ancho de todas las columnas: la tabla no recorta ninguna
        anchoTabla() {
            return this.ANCHO_NUMERO + this.columnas().reduce((suma, c) => suma + c.width, 0) + 2;
        },

        // Día de la celda seleccionada (null si es una columna fija) y lo que tiene ese empleado ese día
        celdaActual() {
            const [fila, col] = this.hot.getSelectedLast() || [];
            const info = this.hot.getSettings().columns[col]?._dia;
            const e = fila >= 0 ? this.hot.getSourceDataAtRow(this.hot.toPhysicalRow(fila))?._e : null;
            return info && e ? { fecha: info.fecha, dia: e.dias[info.dia] } : null;
        },

        ir(base) {
            const celda = this.celdaActual();
            if (celda && base) window.open(`${base}?fecha=${celda.fecha}`, '_blank');
        },

        crearTabla() {
            const alerta = () => !!this.celdaActual()?.dia?.ra;
            const menu = {
                items: {
                    detalle: {
                        name: () => '<i class="fa fa-list"></i> &nbsp; ' + (alerta() ? 'Ir a registro diario' : 'Ir al detalle (registro diario)'),
                        hidden: () => !this.enlaces.detalle || !this.celdaActual(),
                        callback: () => this.ir(this.enlaces.detalle),
                    },
                    riego: {
                        name: () => '<i class="fa fa-droplet"></i> &nbsp; ' + (alerta() ? 'Ir a riego' : 'Ir al detalle de riego'),
                        hidden: () => !this.enlaces.riego || !this.celdaActual(),
                        callback: () => this.ir(this.enlaces.riego),
                    },
                    sep1: '---------',
                    copy: { name: '<i class="fa fa-clipboard"></i> &nbsp; Copiar' },
                },
            };

            this.hot = new Handsontable(this.$refs.tabla, {
                ...window.HstConfig,
                themeName: JSON.parse(localStorage.getItem('darkMode')) ? 'ht-theme-main-dark' : 'ht-theme-main',
                data: [],
                columns: this.columnas(),
                colHeaders: this.encabezados(),
                readOnly: true,
                stretchH: 'none',
                // Sin scroll propio: alto y ancho completos
                height: 'auto',
                width: this.anchoTabla(),
                rowHeaderWidth: this.ANCHO_NUMERO,
                contextMenu: menu,
                sumadorSeleccion: { columnaAgrupar: 0, columnasOmitir: [0] },
                wordWrap: false,
                autoRowSize: false,
                rowHeights: 26,
                columnHeaderHeight: 40,
                afterGetColHeader: (col, th) => {
                    const info = this.hot?.getSettings().columns[col]?._dia;
                    th.style.background = info?.domingo ? this.DOMINGO : '';
                    th.style.color = info?.domingo ? '#111827' : '';
                    th.title = info ? info.fecha.split('-').reverse().join('/') : '';
                },
                // Un día con riego sin sincronizar: el clic abre las opciones (ir a riego / ir a registro diario)
                afterOnCellMouseUp: (event, coords) => {
                    if (event.button !== 0 || coords.row < 0 || !alerta()) return;
                    this.hot.getPlugin('contextMenu').open({ left: event.clientX, top: event.clientY });
                },
            });
            this.cargarFilas();
        },

        // ------------------------------------------------------------------ orden

        estaUsadoEnOtraFila(valorCampo, index) {
            return this.filas.some((f, i) => i !== index && f.campo === valorCampo);
        },

        hayCamposDisponibles() {
            return this.filas.map(f => f.campo).filter(Boolean).length < this.camposOrdenables.length;
        },

        agregarFila() {
            if (this.hayCamposDisponibles()) this.filas.push({ campo: '', direccion: 'asc' });
        },

        quitarFila(index) {
            this.filas.splice(index, 1);
            if (this.filas.length === 0) this.filas.push({ campo: '', direccion: 'asc' });
        },

        guardar() {
            $wire.guardarOrdenConfiguracion(this.filas.filter(f => f.campo));
        },
    }));
</script>
@endscript
