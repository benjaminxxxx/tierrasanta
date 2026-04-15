<div x-data="gestionSalidaAlmacen" class="space-y-4">
    <x-card class="space-y-4">
        {{-- FILTROS --}}
        <x-flex>

            {{-- Día --}}
            <x-input type="number" x-model="filtros.dia" @input="aplicarFiltros()" min="1" max="31" label="Día"
                placeholder="Día" />

            {{-- Producto --}}
            <x-input type="text" x-model="filtros.productoTexto" @input="aplicarFiltros()" label="Producto"
                placeholder="Buscar producto..." />

            {{-- Campo / Maquinaria (según tipo) --}}
            <x-group-field>
                <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide"
                    x-text="tipo === 'combustible' ? 'Maquinaria' : 'Campo'">
                </label>
                <x-select x-model="filtros.destinoId" @change="aplicarFiltros()">
                    <option value="">Todos</option>
                    <template x-for="d in (tipo === 'combustible' ? listaMaquinarias : listaCampos)"
                        :key="d.id ?? d.label">
                        <option :value="d.id ?? d.label" x-text="d.label"></option>
                    </template>
                </x-select>
            </x-group-field>

            {{-- Categoría --}}
            <x-group-field>
                <label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Categoría</label>
                <x-select x-model="filtros.categoria" @change="aplicarFiltros()">
                    <option value="">Todas</option>
                    <template x-for="cat in categoriasDisponibles" :key="cat">
                        <option :value="cat" x-text="cat"></option>
                    </template>
                </x-select>
            </x-group-field>

            {{-- Botón limpiar --}}
            <button @click="limpiarFiltros()"
                class="h-8 px-3 text-sm rounded border border-input bg-background text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors flex items-center gap-1.5">
                <i class="fa fa-times text-xs"></i>
                Limpiar
            </button>

            {{-- Contador de resultados --}}
            <span class="text-xs text-muted-foreground ml-auto self-end pb-1">
                <span x-text="filasFiltradas"></span> resultado<span x-text="filasFiltradas !== 1 ? 's' : ''"></span>
            </span>
        </x-flex>
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

    <x-dialog-modal wire:model.live="modalAuditoriaSalida">
        <x-slot name="title">Historial de auditoría — Salida de Insumos</x-slot>

        <x-slot name="content">
            @php
                $entradaCreacion = collect($auditoriaHistorialSalida)->firstWhere('accion', 'crear');
                $ultimaEdicion = collect($auditoriaHistorialSalida)->where('accion', 'editar')->sortByDesc('fecha_accion')->first();
            @endphp

            <div class="flex gap-6 mb-4 text-xs text-muted-foreground border-b border-border pb-3">
                <div>
                    <span class="font-semibold text-card-foreground">Creado por:</span>
                    {{ $entradaCreacion['usuario_nombre'] ?? '—' }}
                    @if($entradaCreacion)
                        <span class="ml-1 text-gray-400">
                            {{ \Carbon\Carbon::parse($entradaCreacion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
                <div>
                    <span class="font-semibold text-card-foreground">Última edición:</span>
                    {{ $ultimaEdicion['usuario_nombre'] ?? '—' }}
                    @if($ultimaEdicion)
                        <span class="ml-1 text-gray-400">
                            {{ \Carbon\Carbon::parse($ultimaEdicion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            @forelse($auditoriaHistorialSalida as $entrada)
                    <div class="mb-4 border-b border-border pb-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold uppercase
                                                                                                                                        {{ $entrada['accion'] === 'crear' ? 'text-green-600' :
                ($entrada['accion'] === 'eliminar' ? 'text-red-600' : 'text-yellow-600') }}">
                                {{ $entrada['accion'] }}
                            </span>
                            <span class="text-gray-400 text-xs">
                                {{ \Carbon\Carbon::parse($entrada['fecha_accion'])->format('d/m/Y H:i') }}
                                — {{ $entrada['usuario_nombre'] ?? 'Sistema' }}
                            </span>
                        </div>

                        @if(!empty($entrada['cambios']))
                            @if($entrada['accion'] === 'editar')
                                <table class="mt-2 w-full text-xs text-gray-700">
                                    <thead>
                                        <tr class="text-left text-gray-400">
                                            <th class="pr-4">Campo</th>
                                            <th class="pr-4">Antes</th>
                                            <th>Después</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($entrada['cambios']['antes'] ?? [] as $campo => $valorAntes)
                                            <tr>
                                                <td class="pr-4 font-medium text-muted-foreground">{{ $campo }}</td>
                                                <td class="pr-4 text-red-500">{{ $valorAntes ?? '—' }}</td>
                                                <td class="text-green-600">{{ $entrada['cambios']['despues'][$campo] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <pre
                                    class="mt-2 text-xs bg-muted rounded p-2 overflow-auto max-h-40">{{ json_encode(array_values($entrada['cambios'])[0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                        @endif

                        @if(!empty($entrada['observacion']))
                            <p class="mt-1 text-xs italic text-card-foreground">{{ $entrada['observacion'] }}</p>
                        @endif
                    </div>
            @empty
                <p class="text-sm text-card-foreground">Sin historial de cambios.</p>
            @endforelse
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalAuditoriaSalida', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>

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
        filtros: {
            dia: '',
            productoTexto: '',
            destinoId: '',
            categoria: '',
        },
        filasFiltradas: 0,
        init() {
            this.initTable(this.tableDataSalidas);

            this.filasFiltradas = this.tableDataSalidas.length;
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
                    this.aplicarFiltros();
                });
            })
        },
        initTable(tableData) {
            if (this.hot) {
                try {
                    this.hot.destroy();
                } catch (e) { }
                this.hot = null;
            }
            const container = this.$refs.tableContainer;
            if (!container) return;
            const esCombustible = this.tipo === 'combustible';

            const hot = new Handsontable(container, {
                ...window.HstConfig,
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
                contextMenu: {
                    items: {
                        // Distribución combustible (solo si aplica)
                        ...(esCombustible ? {
                            'distribucion': {
                                name: '<i class="fa fa-list"></i> &nbsp; Distribución combustible',
                                callback: () => {
                                    const selected = this.hot.getSelected();
                                    if (!selected) return;
                                    const fila = this.hot.getSourceDataAtRow(selected[0][0]);
                                    if (!fila?.id) {
                                        alert('Guarda el registro antes de ver la distribución.');
                                        return;
                                    }
                                    $wire.dispatch('abrirModalDistribucion', {
                                        salidaId: fila.id
                                    });
                                    /*
                                    $wire.dispatch('verDistribucionCombustible', {
                                        salidaId: fila.id,
                                        mes: this.mes,
                                        anio: this.anio,
                                    });*/
                                },
                            },
                            'sep1': '---------',
                        } : {}),

                        'historial': {
                            name: '<i class="fa fa-history"></i> &nbsp; Ver historial',
                            callback: () => {
                                const selected = this.hot.getSelected();
                                if (!selected) return;
                                const fila = this.hot.getSourceDataAtRow(selected[0][0]);
                                if (!fila?.id) {
                                    alert('Este registro aún no ha sido guardado.');
                                    return;
                                }
                                $wire.verHistorialSalida(fila.id);
                            },
                        },

                        'sep2': '---------',

                        'eliminar': {
                            name: '<i class="fa fa-trash text-red-500"></i> &nbsp; Eliminar salida',
                            callback: () => {
                                const selected = this.hot.getSelected();
                                if (!selected) return;
                                const fila = this.hot.getSourceDataAtRow(selected[0][0]);
                                if (!fila?.id) {
                                    alert('Este registro aún no ha sido guardado.');
                                    return;
                                }
                                if (confirm(`¿Eliminar esta salida? Esta acción no se puede deshacer.`)) {
                                    $wire.eliminarSalida(fila.id);
                                }
                            },
                        },
                    },
                },
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

            });

            this.hot = hot;
            this.hot.render();
        },
        get categoriasDisponibles() {
            const cats = new Set(
                this.tableDataSalidas
                    .map(r => r.categoria)
                    .filter(Boolean)
            );
            return [...cats].sort();
        },
        aplicarFiltros() {
            if (!this.hot) return;

            const { dia, productoTexto, destinoId, categoria } = this.filtros;
            const hayFiltro = dia || productoTexto || destinoId || categoria;

            // Sin filtro activo → mostrar todos los datos originales
            if (!hayFiltro) {
                this.hot.loadData(this.tableDataSalidas);
                this.filasFiltradas = this.tableDataSalidas.length;
                return;
            }

            const datos = this.tableDataSalidas.filter(fila => {
                // Fecha
                if (dia && fila.fecha_reporte) {
                    const diaDato = parseInt(fila.fecha_reporte.split('-')[2], 10);
                    if (diaDato !== parseInt(dia, 10)) return false;
                }

                // Producto (columna guarda ID interno)
                if (productoTexto) {
                    const label = this.productosRevMap[fila.producto_id] ?? this.productosRevMap[String(fila.producto_id)] ?? '';
                    if (!label.toLowerCase().includes(productoTexto.toLowerCase())) return false;
                }

                // Destino: campo o maquinaria según tipo
                if (destinoId) {
                    const prop = this.tipo === 'combustible' ? 'maquinaria_id' : 'campo_nombre';
                    if (String(fila[prop]) !== String(destinoId)) return false;
                }

                // Categoría (texto plano)
                if (categoria && fila.categoria !== categoria) return false;

                return true;
            });

            this.hot.loadData(datos);
            this.filasFiltradas = datos.length;
        },

        limpiarFiltros() {
            this.filtros = { dia: '', productoId: '', destinoId: '', categoria: '' };
            this.aplicarFiltros();
        },
        get productosRevMap() {
            return Object.fromEntries(this.listaProductos.map(p => [p.id, p.label]));
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