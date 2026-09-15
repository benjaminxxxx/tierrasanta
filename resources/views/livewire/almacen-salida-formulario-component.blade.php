<div x-data="formularioSalida" x-show="mostrar" x-cloak>
    <x-dialog-modal wire:model.live="mostrar" maxWidth="full">
        <x-slot name="title">
            <span x-text="esEdicion ? 'Editar salidas' : 'Agregar salidas'"></span>
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div wire:ignore>
                    <div x-ref="tableContainer" style="min-height: 320px;"></div>
                </div>

                <x-card>
                    @if (count($stocksProductos) > 0)
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2 p-2">
                            @foreach ($stocksProductos as $stock)
                                <div class="rounded-lg border border-border p-2 flex flex-col gap-1.5 bg-muted">
                                    <p class="text-xs font-semibold text-center leading-tight line-clamp-2 min-h-[2rem]">
                                        {{ $stock['nombre'] }}
                                    </p>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] text-muted-foreground w-10 shrink-0">Blanco</span>
                                        <span class="text-[10px] font-medium text-blue-500">
                                            {{ is_null($stock['blanco']) ? '-' : number_format($stock['blanco'], 1) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-[10px] text-muted-foreground w-10 shrink-0">Negro</span>
                                        <span class="text-[10px] font-medium text-amber-500">
                                            {{ is_null($stock['negro']) ? '-' : number_format($stock['negro'], 1) }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-center text-muted-foreground">{{ $stock['unidad'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-muted-foreground text-center p-3">
                            Modifica una fila para ver el stock disponible.
                        </p>
                    @endif
                </x-card>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="cerrar">Cancelar</x-button>
            <x-button @click="guardar()">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>

@script
<script>
    Alpine.data('formularioSalida', () => ({
        mostrar: @entangle('mostrar'),
        esEdicion: false,
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        tipo: @js($tipo),
        listaProductos: @js($listaProductos),
        listaMaquinarias: @js($listaMaquinarias),
        listaCampos: @js($listaCampos),
        usosPorProducto: @js($usosPorProducto),
        usoRevMap: {},

        init() {
            // Mapa global id->nombre de TODOS los usos posibles (para renderizar
            // el valor guardado, sin importar de qué producto viene).
            this.usoRevMap = Object.fromEntries(
                Object.values(this.usosPorProducto).flat().map(u => [u.id, u.label])
            );

            Livewire.on('formularioSalidaAbierto', ({ data, esEdicion }) => {
                this.esEdicion = esEdicion;
                this.usosPorProducto = @js($usosPorProducto); // refresca por si cambió el tipo
                this.$nextTick(() => this.initTable(data));
            });
        },

        initTable(tableData) {
            if (this.hot) {
                try { this.hot.destroy(); } catch (e) { }
                this.hot = null;
            }
            const container = this.$refs.tableContainer;
            if (!container) return;

            this.hot = new Handsontable(container, {
                ...window.HstConfig,
                data: tableData,
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                colHeaders: true,
                rowHeaders: true,
                columns: this.getColumns(),
                // Sin paginar: si es edición, exactamente las filas seleccionadas;
                // si es creación, arranca vacío y crece libremente.
                minSpareRows: this.esEdicion ? 0 : 1,
                manualColumnResize: false,
                manualRowResize: true,
                stretchH: 'all',
                height: 400,
                licenseKey: 'non-commercial-and-evaluation',
                // La columna USO depende del producto de la MISMA fila —
                // se resuelve por celda, no con un 'source' fijo de columna.
                cells: (row, col) => {
                    if (this.tipo === 'combustible') return {};
                    const usoColIndex = this.hot?.propToCol('uso_id');
                    if (col !== usoColIndex) return {};

                    const productoId = this.hot.getDataAtRowProp(row, 'producto_id');
                    const usos = this.usosPorProducto[productoId] ?? [];

                    return {
                        type: 'autocomplete',
                        source: usos.map(u => u.label),
                        strict: false,
                        allowInvalid: true, // opcional: puede quedar vacío
                    };
                },
                afterChange: async (changes, source) => {
                    if (source === 'loadData') return;

                    if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;

                    const columnasRelevantes = new Set(['producto_id', 'cantidad', 'tipo_kardex']);
                    const cambioRelevante = changes.some(([, prop]) => columnasRelevantes.has(prop));
                    if (!cambioRelevante) return;

                    // Si cambió el producto, limpia el uso de esa fila (ya no aplica el anterior)
                    changes.forEach(([row, prop]) => {
                        if (prop === 'producto_id' && this.tipo !== 'combustible') {
                            this.hot.setDataAtRowProp(row, 'uso_id', null, 'limpieza');
                        }
                    });

                    const totalRows = this.hot.countRows();
                    const productosActivos = [];
                    for (let i = 0; i < totalRows; i++) {
                        const pid = this.hot.getDataAtRowProp(i, 'producto_id');
                        if (pid) productosActivos.push(pid);
                    }
                    await this.$wire.limpiarStocksHuerfanos([...new Set(productosActivos)]);

                    const productosAfectados = [...new Set(
                        changes.filter(([, prop]) => columnasRelevantes.has(prop))
                            .map(([row]) => this.hot.getDataAtRowProp(row, 'producto_id'))
                            .filter(Boolean)
                    )];
                    for (const pid of productosAfectados) {
                        await this.$wire.preguntarStock(pid);
                    }

                    this.hot.render(); // refresca la columna USO tras el cambio de producto
                },
            });

            this.hot.render();
        },

        getColumns() {
            const esCombustible = this.tipo === 'combustible';

            const productosLabels = this.listaProductos.map(p => p.label);
            const productosMap = Object.fromEntries(this.listaProductos.map(p => [p.label, p.id]));
            const productosRevMap = Object.fromEntries(this.listaProductos.map(p => [p.id, p.label]));

            const destinoLista = esCombustible ? this.listaMaquinarias : this.listaCampos;
            const destinoLabels = destinoLista.map(d => d.label);
            const destinoMap = Object.fromEntries(destinoLista.map(d => [d.label, d.id ?? d.label]));
            const destinoRevMap = Object.fromEntries(destinoLista.map(d => [(d.id ?? d.label), d.label]));

            const autocompleteCol = (labels, map, revMap, prop, title, width) => ({
                data: prop, title, type: 'autocomplete', source: labels,
                strict: false, allowInvalid: false, filter: true, width,
                renderer(instance, td, row, col, prop, value) {
                    td.classList.remove('text-gray-400', 'italic', 'text-red-500');
                    if (value === null || value === undefined || value === '') {
                        td.classList.add('text-gray-400', 'italic');
                        td.innerText = 'Buscar...';
                        return;
                    }
                    const label = revMap[value] ?? revMap[String(value)];
                    if (label) { td.innerText = label; }
                    else { td.classList.add('text-red-500'); td.innerText = '⚠️ ' + value; }
                },
                validator(value, callback) {
                    if (!value || value === '') return callback(true);
                    if (revMap[value] || revMap[String(value)]) return callback(true);
                    if (typeof value === 'string' && map[value]) {
                        setTimeout(() => this.instance.setDataAtCell(this.row, this.col, map[value], 'validator'), 0);
                        return callback(true);
                    }
                    callback(false);
                }
            });

            const columns = [
                // 'id' no se muestra como columna visible, pero viaja en cada fila
                // del dataset — es lo que distingue "editar" (id presente) de
                // "crear" (id ausente) al momento de guardar. No requiere columna extra.
                { data: 'fecha_reporte', type: 'date', dateFormat: 'YYYY-MM-DD', title: 'FECHA', width: 90 },
                autocompleteCol(productosLabels, productosMap, productosRevMap, 'producto_id', 'PRODUCTO', 140),
                { data: 'unidad_medida', type: 'text', title: 'UND', readOnly: true, className: '!bg-muted !text-center' },
                { data: 'cantidad', type: 'numeric', numericFormat: { pattern: '0.000' }, title: 'CANTIDAD' },
                esCombustible
                    ? autocompleteCol(destinoLabels, destinoMap, destinoRevMap, 'maquinaria_id', 'MAQUINARIA', 130)
                    : autocompleteCol(destinoLabels, destinoMap, destinoRevMap, 'campo_nombre', 'CAMPO', 100),
                { data: 'tipo_kardex', title: 'TIPO KARDEX', type: 'dropdown', source: ['blanco', 'negro', ''], allowEmpty: true, className: '!text-center' },
            ];

            if (!esCombustible) {
                columns.push({
                    data: 'uso_id',
                    title: 'USO',
                    type: 'autocomplete',
                    // 'source' real se resuelve por fila en cells(); esto es solo el fallback.
                    source: [],
                    strict: false,
                    allowInvalid: true,
                    width: 130,
                    renderer: (instance, td, row, col, prop, value) => {
                        td.classList.remove('text-gray-400', 'italic');
                        if (value === null || value === undefined || value === '') {
                            td.classList.add('text-gray-400', 'italic');
                            td.innerText = 'Sin uso';
                            return;
                        }
                        td.innerText = this.usoRevMap[value] ?? this.usoRevMap[String(value)] ?? value;
                    },
                    validator: (value, callback) => {
                        if (!value || value === '') return callback(true);
                        // Acepta el id directo o la etiqueta escrita (la resuelve al id correspondiente)
                        const match = Object.entries(this.usoRevMap).find(([, label]) => label === value);
                        if (match) {
                            setTimeout(() => this.hot.setDataAtCell(this.hot.getSelectedLast()[0], col, Number(match[0]), 'validator'), 0);
                        }
                        callback(true); // el uso es opcional: nunca bloquea el guardado
                    },
                });
            }

            columns.push(
                { data: 'categoria', type: 'text', readOnly: true, title: 'CATEGORIA', className: '!bg-muted' },
                { data: 'costo_por_kg', type: 'numeric', title: 'COSTO X UND', readOnly: true, className: '!bg-muted' },
                { data: 'total_costo', type: 'numeric', readOnly: true, title: 'TOTAL COSTO', className: '!bg-muted' },
            );

            if (esCombustible) {
                columns.push({ data: 'distribuciones_count', type: 'numeric', title: 'DISTRIB.', readOnly: true, width: 70, className: 'text-center !bg-muted' });
            }

            return columns;
        },

        guardar() {
            const totalRows = this.hot.countRows();
            const data = [];
            for (let i = 0; i < totalRows; i++) {
                const fila = this.hot.getSourceDataAtRow(i);
                if (fila && Object.values(fila).some(v => v !== null && v !== '')) {
                    data.push(fila);
                }
            }
            if (data.length === 0) {
                alert('No hay filas para guardar.');
                return;
            }
            this.$wire.guardar(data);
        },
    }))
</script>
@endscript