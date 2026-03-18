<div x-data="gestionSalidaAlmacen" class="space-y-4">
    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>
    <x-card>
        @if (count($stocksProductos) > 0)
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2 p-2">
                @foreach ($stocksProductos as $stock)
                    <div class="rounded-lg border border-border p-2 flex flex-col gap-1.5 bg-muted">

                        {{-- Nombre --}}
                        <p class="text-xs font-semibold text-center leading-tight line-clamp-2 min-h-[2rem] gap-4">
                            {{ $stock['nombre'] }} <x-button size="sm"
                                wire:click="actualizarStockInsumo({{ $stock['producto_id'] }})">
                                <i class="fa fa-sync"></i>
                            </x-button>
                        </p>

                        {{-- Barra BLANCO --}}
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] text-muted-foreground w-10 shrink-0">Blanco</span>
                            @if (is_null($stock['blanco']))
                                <div class="flex-1 h-2 rounded-full bg-muted"></div>
                                <span class="text-[10px] text-muted-foreground">-</span>
                            @elseif($stock['blanco'] <= 0)
                                <div class="flex-1 h-2 rounded-full bg-gray-300 dark:bg-gray-700"></div>
                                <span class="text-[10px] text-gray-400">0</span>
                            @else
                                <div class="flex-1 h-2 rounded-full bg-blue-500/30">
                                    <div class="h-2 rounded-full bg-blue-500" style="width: 100%"></div>
                                </div>
                                <span class="text-[10px] font-medium text-blue-500">
                                    {{ number_format($stock['blanco'], 1) }}
                                </span>
                            @endif
                        </div>

                        {{-- Barra NEGRO --}}
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] text-muted-foreground w-10 shrink-0">Negro</span>
                            @if (is_null($stock['negro']))
                                <div class="flex-1 h-2 rounded-full bg-muted"></div>
                                <span class="text-[10px] text-muted-foreground">-</span>
                            @elseif($stock['negro'] <= 0)
                                <div class="flex-1 h-2 rounded-full bg-gray-300 dark:bg-gray-700"></div>
                                <span class="text-[10px] text-gray-400">0</span>
                            @else
                                <div class="flex-1 h-2 rounded-full bg-amber-500/30">
                                    <div class="h-2 rounded-full bg-amber-500" style="width: 100%"></div>
                                </div>
                                <span class="text-[10px] font-medium text-amber-500">
                                    {{ number_format($stock['negro'], 1) }}
                                </span>
                            @endif
                        </div>

                        {{-- Unidad --}}
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
    <x-inferior-derecha>
        <x-button @click="guardarSalidaAlmacen()">
            <i class="fa fa-save"></i> Guardar Salidas Modificadas
        </x-button>
    </x-inferior-derecha>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('gestionSalidaAlmacen', () => ({
            filasModificadas: @entangle('filasModificadas'),
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            tableDataSalidas: @js($registros),
            tipo: @js($tipo),
            listaProductos: @js($listaProductos),
            listaMaquinarias: @js($listaMaquinarias),
            listaCampos: @js($listaCampos),
            mes: @js($mes),
            anio: @js($anio),
            init() {
                this.initTable(this.tableDataSalidas);
                $watch('darkMode', value => {

                    this.isDark = value;
                    const columns = this.getColumns();
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });

                });
                Livewire.on('cargarDataSlidaAlmacen', ({
                    data
                }) => {
                    if (!this.$refs.tableContainer) return;

                    this.$nextTick(() => {
                        if (!this.$refs.tableContainer) return; // doble check tras nextTick
                        this.tableDataSalidas = data;
                        this.initTable(data);
                    });
                })
            },
            initTable(tableData) {
                if (this.hot) {
                    try {
                        this.hot.destroy();
                    } catch (e) {}
                    this.hot = null;
                }
                const container = this.$refs.tableContainer;
                if (!container) return;
                const esCombustible = this.tipo === 'combustible';

                const hot = new Handsontable(container, {
                    data: tableData,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                    colHeaders: true,
                    rowHeaders: true,
                    columns: this.getColumns(),
                    manualColumnResize: false,
                    manualRowResize: true,
                    stretchH: 'all',
                    minSpareRows: 1,
                    autoColumnSize: false,
                    licenseKey: 'non-commercial-and-evaluation',
                    contextMenu: esCombustible ? {
                        items: {
                            'distribucion': {
                                name: '<i class="fa fa-list"></i> &nbsp; Distribución combustible',
                                callback: () => {
                                    const selected = this.hot.getSelected();
                                    if (!selected) return;

                                    // Tomar solo la primera selección, primera fila
                                    const row = selected[0][0];
                                    const fila = this.hot.getSourceDataAtRow(row);

                                    if (!fila?.id) {
                                        alert(
                                            'Guarda el registro antes de ver la distribución.'
                                        );
                                        return;
                                    }

                                    $wire.dispatch('verDistribucionCombustible', {
                                        salidaId: fila.id,
                                        mes: this.mes,
                                        anio: this.anio,
                                    });
                                },
                            },
                            'sep1': '---------',
                        }
                    } : false,
                    afterChange: async (changes, source) => {
                        if (source === 'recalculado' || source === 'loadData') return;

                        changes.forEach(([row]) => {
                            if (!this.filasModificadas.includes(row)) {
                                this.filasModificadas = [...this.filasModificadas, row];
                            }
                        });

                        if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;

                        // Detectar cambios en producto_id
                        const columnasRelevantes = new Set(['producto_id', 'cantidad',
                            'tipo_kardex']);

                        const cambioRelevante = changes.some(([, prop]) => columnasRelevantes.has(
                            prop));
                        if (!cambioRelevante) return;

                       // Recolectar todos los producto_id activos en la tabla
const totalRows = this.hot.countRows();
const productosActivos = [];
for (let i = 0; i < totalRows; i++) {
    const pid = this.hot.getDataAtRowProp(i, 'producto_id');
    if (pid) productosActivos.push(pid);
}

// Limpiar huérfanos
await $wire.limpiarStocksHuerfanos([...new Set(productosActivos)]);

// Cargar stock de las filas que tuvieron cambio relevante
const productosAfectados = [...new Set(
    changes
        .filter(([, prop]) => columnasRelevantes.has(prop))
        .map(([row]) => this.hot.getDataAtRowProp(row, 'producto_id'))
        .filter(Boolean)
)];

for (const pid of productosAfectados) {
    await $wire.preguntarStock(pid);
}
                    },
                    /*
                    afterChange: (changes, source) => {
                        // Corta el bucle: si nosotros mismos disparamos el cambio, ignorar
                        if (source === 'recalculado' || source === 'loadData') return;

                        changes.forEach(([row]) => {
                            if (!this.filasModificadas.includes(row)) {
                                this.filasModificadas = [...this.filasModificadas, row];
                            }
                        });

                        if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;


                    }*/

                });

                this.hot = hot;
                this.hot.render();
            },
            getColumns() {
                const esCombustible = this.tipo === 'combustible';

                // Mapas para productos

                const productosLabels = this.listaProductos.map(p => p.label);
                const productosMap = Object.fromEntries(this.listaProductos.map(p => [p.label, p.id]));
                const productosRevMap = Object.fromEntries(this.listaProductos.map(p => [p.id, p.label]));


                // Mapas para destino (campo o maquinaria)
                const destinoLista = esCombustible ? this.listaMaquinarias : this.listaCampos;
                const destinoLabels = destinoLista.map(d => d.label);
                const destinoMap = Object.fromEntries(destinoLista.map(d => [d.label, d.id ?? d.label]));
                const destinoRevMap = Object.fromEntries(destinoLista.map(d => [(d.id ?? d.label), d.label]));

                const autocompleteCol = (labels, map, revMap, prop, title, width) => ({
                    data: prop,
                    title,
                    type: 'autocomplete',
                    source: labels,
                    strict: false,
                    allowInvalid: false,
                    filter: true,
                    width: width,
                    renderer(instance, td, row, col, prop, value) {
                        td.classList.remove('text-gray-400', 'italic', 'text-red-500');
                        if (value === null || value === undefined || value === '') {
                            td.classList.add('text-gray-400', 'italic');
                            td.innerText = 'Buscar...';
                            return;
                        }
                        const label = revMap[value] ?? revMap[String(value)];
                        if (label) {
                            td.innerText = label;
                        } else {
                            td.classList.add('text-red-500');
                            td.innerText = '⚠️ ' + value;
                        }
                    },
                    validator(value, callback) {
                        if (!value || value === '') return callback(true);
                        if (revMap[value] || revMap[String(value)]) return callback(true);
                        if (typeof value === 'string' && map[value]) {
                            setTimeout(() => {
                                this.instance.setDataAtCell(this.row, this.col, map[value],
                                    'validator');
                            }, 0);
                            return callback(true);
                        }
                        callback(false);
                    }
                });

                const columns = [{
                        data: 'item',
                        type: 'numeric',
                        title: 'ITEM',
                        readOnly: true,
                    },
                    {
                        data: 'fecha_reporte',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        title: 'FECHA',
                        width: 90,
                    },
                    // Columna PRODUCTO (autocomplete con id interno)
                    autocompleteCol(productosLabels, productosMap, productosRevMap, 'producto_id',
                        'PRODUCTO', 120),
                    {
                        data: 'unidad_medida',
                        type: 'text',
                        title: 'UND',
                        readOnly: true,
                        className: '!bg-muted !text-center',
                    },
                    {
                        data: 'cantidad',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.000'
                        },
                        title: 'CANTIDAD',
                    },
                    // Columna DESTINO dinámica: campo o maquinaria
                    esCombustible ?
                    autocompleteCol(destinoLabels, destinoMap, destinoRevMap, 'maquinaria_id',
                        'MAQUINARIA', 120) :
                    autocompleteCol(destinoLabels, destinoMap, destinoRevMap, 'campo_nombre', 'CAMPO', 40),
                    {
                        data: 'tipo_kardex',
                        title: 'TIPO KARDEX',
                        type: 'dropdown',
                        source: ['blanco', 'negro', ''],
                        allowEmpty: true,
                        className: '!text-center',
                    },
                    {
                        data: 'categoria',
                        type: 'text',
                        readOnly: true,
                        title: 'CATEGORIA',
                        className: '!bg-muted',
                    },
                    {
                        data: 'costo_por_kg',
                        type: 'numeric',
                        title: 'COSTO X UND',
                        readOnly: true,
                        className: '!bg-muted',
                    },
                    {
                        data: 'total_costo',
                        type: 'numeric',
                        readOnly: true,
                        title: 'TOTAL COSTO',
                        className: '!bg-muted',
                    },
                ];

                if (esCombustible) {
                    columns.push({
                        data: 'distribuciones_count',
                        type: 'numeric',
                        title: 'DISTRIB.',
                        readOnly: true,
                        width: 70,
                        className: 'text-center !bg-muted',
                    });
                }

                return columns;
            },
            guardarSalidaAlmacen() {
                if (this.filasModificadas.length === 0) {
                    alert('Niguna fila modificada');
                    return;
                };

                const data = [...this.filasModificadas]
                    .map(i => this.hot.getSourceDataAtRow(i))
                    .filter(fila => fila && Object.values(fila).some(v => v !== null && v !== ''));

                $wire.guardarSalidaAlmacen(data);
            },
        }))
    </script>
@endscript
