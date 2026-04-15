<div x-data="gestionAlmacenCompraInsumos" class="space-y-4">
    <x-title>
        Gestión de Compras
    </x-title>
    <x-card>
        <x-flex>
            <!-- 🔎 Busqueda general -->
            <x-input type="search" label="Buscar" placeholder="Buscar producto, proveedor, serie..."
                wire:model.live="busquedaGeneral" class="w-auto" />

            <!-- 📅 Fecha -->
            <x-select-anios wire:model.live="filtroAnio" class="w-auto" label="Año" />
            <x-select-meses wire:model.live="filtroMes" class="w-auto" label="Mes" />

            <x-input type="number" placeholder="Día" wire:model.live="filtroDia" class="w-auto" label="Día" />

            <!-- 📄 Tipo comprobante -->
            <x-select wire:model.live="filtroTipoComprobante" class="w-auto" label="Tipo Documento">
                <option value="">Todos</option>
                @foreach ($listaTipoDocumentos as $t)
                    <option value="{{ $t['id'] }}">{{ $t['label'] }}</option>
                @endforeach
            </x-select>

            <!-- 🎯 Kardex -->
            <x-select wire:model.live="filtroTipoKardex" class="w-auto" label="Tipo Kardex">
                <option value="">Todos</option>
                <option value="negro">Negro</option>
                <option value="blanco">Blanco</option>
            </x-select>
        </x-flex>


    </x-card>
    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>
    <x-inferior-derecha>
        <x-button @click="guardarCompraInsumos">
            <i class="fa fa-save"></i> Guardar Compras
        </x-button>
    </x-inferior-derecha>
    <x-dialog-modal wire:model.live="mostrarDetalleCompras">
        <x-slot name="title">
            Detalle de Compras Seleccionadas
        </x-slot>

        <x-slot name="content">
            @forelse ($comprasDetalle as $i => $c)
                <div class="{{ $i > 0 ? 'mt-6 pt-6 border-t border-border' : '' }}">
                    <h4 class="text-sm font-semibold text-muted-foreground mb-3">
                        #{{ $i + 1 }} — {{ $c['producto'] }}
                    </h4>

                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">

                        {{-- Identificación --}}
                        <div class="col-span-2 text-xs font-medium text-gray-400 uppercase tracking-wide mt-1">
                            Identificación
                        </div>
                        <div><span class="text-gray-500">Proveedor:</span> {{ $c['proveedor'] }}</div>
                        <div><span class="text-gray-500">Fecha compra:</span> {{ $c['fecha_compra'] }}</div>
                        <div><span class="text-gray-500">Serie:</span> {{ $c['serie'] ?? '—' }}</div>
                        <div><span class="text-gray-500">Número:</span> {{ $c['numero'] ?? '—' }}</div>
                        <div><span class="text-gray-500">Tipo compra:</span> {{ $c['tipo_compra_codigo'] ?? '—' }}</div>
                        <div><span class="text-gray-500">Tipo kardex:</span> {{ $c['tipo_kardex'] ?? '—' }}</div>

                        {{-- Montos --}}
                        <div class="col-span-2 text-xs font-medium text-gray-400 uppercase tracking-wide mt-3">
                            Stock & Costos
                        </div>
                        <div><span class="text-gray-500">Stock:</span> {{ number_format($c['stock'], 2) }}</div>
                        <div><span class="text-gray-500">Total:</span> S/ {{ number_format($c['total'], 2) }}</div>
                        <div><span class="text-gray-500">Costo x kg:</span> S/
                            {{ number_format($c['costo_por_unidad'], 4) }}
                        </div>

                        {{-- Auditoría --}}
                        <div class="col-span-2 text-xs font-medium text-gray-400 uppercase tracking-wide mt-3">
                            Auditoría
                        </div>
                        <div><span class="text-gray-500">Creado por:</span> {{ $c['creado_por'] ?? '—' }}</div>
                        <div><span class="text-gray-500">Creado el:</span> {{ $c['created_at'] ?? '—' }}</div>
                        <div><span class="text-gray-500">Editado por:</span> {{ $c['editado_por'] ?? '—' }}</div>
                        <div><span class="text-gray-500">Editado el:</span> {{ $c['updated_at'] ?? '—' }}</div>
                        <div><span class="text-gray-500">Eliminado por:</span> {{ $c['eliminado_por'] ?? '—' }}</div>
                        <div><span class="text-gray-500">Eliminado el:</span> {{ $c['deleted_at'] ?? '—' }}</div>
                    </div>
                </div>
                {{-- Historial de Auditoría --}}
                @if(!empty($c['auditoria']))
                    <div class="col-span-2 text-xs font-medium text-gray-400 uppercase tracking-wide mt-3 mb-2">
                        Historial de Cambios
                    </div>

                    @php
                        $entradaCreacion = collect($c['auditoria'])->firstWhere('accion', 'crear');
                        $ultimaEdicion = collect($c['auditoria'])->where('accion', 'editar')->sortByDesc('fecha_accion')->first();
                    @endphp

                    <div class="col-span-2 flex gap-6 mb-3 text-xs text-muted-foreground border-b border-border pb-2">
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

                    <div class="col-span-2 space-y-3">
                        @foreach($c['auditoria'] as $entrada)
                            <div class="border-b border-border pb-3">
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
                        @endforeach
                    </div>
                @else
                    <div class="col-span-2 text-xs text-gray-400 mt-2">Sin historial de auditoría.</div>
                @endif
            @empty
                <p class="text-sm text-gray-500">No hay registros para mostrar.</p>
            @endforelse
        </x-slot>

        <x-slot name="footer">
            <x-button wire:click="$set('mostrarDetalleCompras', false)" wire:loading.attr="disabled">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('gestionAlmacenCompraInsumos', () => ({
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        tableData: @js($compras),
        filasModificadas: @entangle('filasModificadas'),
        listaProductos: @js($listaProductos),
        listaProveedores: @js($listaProveedores),
        listaTipoDocumentos: @js($listaTipoDocumentos),
        init() {
            this.initTable(this.tableData);
            $watch('darkMode', value => {

                this.isDark = value;
                const columns = this.getColumns();
                this.hot.updateSettings({
                    themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                    columns: columns
                });

            });
            Livewire.on('actualizarCompraProductos', ({
                data
            }) => {
                if (!this.$refs.tableContainer) return;

                this.$nextTick(() => {
                    if (!this.$refs.tableContainer) return; // doble check tras nextTick
                    this.tableData = data;
                    this.initTable(data);
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

            const self = this;

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
                afterChange: async (changes, source) => {
                    if (source === 'recalculado' || source === 'loadData') return;

                    changes.forEach(([row]) => {
                        if (!this.filasModificadas.includes(row)) {
                            this.filasModificadas = [...this.filasModificadas, row];
                        }
                    });

                    if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;
                },
                contextMenu: {
                    items: {
                        // ── Ver detalle ──────────────────────────────────────────────────
                        'ver_detalle': {
                            name: '🔍 Ver detalle completo',
                            callback: function (key, selection) {
                                const ids = self.obtenerIdsSeleccionados(this, selection);

                                if (ids.length === 0) {
                                    alert('Selecciona al menos un registro guardado.');
                                    return;
                                }

                                @this.call('verInformacionSeleccionados', ids);
                            }
                        },
                        'sep1': '---------',
                        // ── Eliminar ─────────────────────────────────────────────────────
                        'eliminar_compra': {
                            name: '🗑️ Eliminar compra',
                            callback: function (key, selection) {
                                const ids = self.obtenerIdsSeleccionados(this, selection);

                                if (ids.length === 0) {
                                    alert('Selecciona al menos un registro guardado.');
                                    return;
                                }

                                @this.call('eliminarSeleccionados', ids);
                            }
                        }
                    }
                },
            });

            this.hot = hot;
            this.hot.render();
        },
        obtenerIdsSeleccionados(hotInstance, selection) {
            const ids = [];

            selection.forEach(({
                start,
                end
            }) => {
                const rowStart = Math.min(start.row, end.row);
                const rowEnd = Math.max(start.row, end.row);

                for (let row = rowStart; row <= rowEnd; row++) {
                    // Asume que 'id' está en la columna 0 — ajusta el índice si es diferente
                    const id = hotInstance.getDataAtRowProp(row, 'id');
                    if (id !== null && id !== undefined && id !== '') {
                        ids.push(id);
                    }
                }
            });

            // Deduplicar por si hay selecciones solapadas
            return [...new Set(ids)];
        },
        getColumns() {

            // Mapas para productos

            const productosLabels = this.listaProductos.map(p => p.label);
            const productosMap = Object.fromEntries(this.listaProductos.map(p => [p.label, p.id]));
            const productosRevMap = Object.fromEntries(this.listaProductos.map(p => [p.id, p.label]));

            const proveedoresLabels = this.listaProveedores.map(p => p.label);
            const proveedoresMap = Object.fromEntries(this.listaProveedores.map(p => [p.label, p.id]));
            const proveedoresRevMap = Object.fromEntries(this.listaProveedores.map(p => [p.id, p.label]));

            const tipoDocumentosLabels = this.listaTipoDocumentos.map(p => p.label);
            const tipoDocumentosMap = Object.fromEntries(this.listaTipoDocumentos.map(p => [p.label, p
                .id
            ]));
            const tipoDocumentosRevMap = Object.fromEntries(this.listaTipoDocumentos.map(p => [p.id, p
                .label
            ]));



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
                    td.classList.remove('!text-gray-400', 'italic', '!text-red-500');
                    if (value === null || value === undefined || value === '') {
                        td.classList.add('!text-gray-400', 'italic');
                        td.innerText = 'Buscar...';
                        return;
                    }
                    const label = revMap[value] ?? revMap[String(value)];
                    if (label) {
                        td.innerText = label;
                    } else {
                        td.classList.add('!text-red-500');
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
                data: 'fecha_compra',
                type: 'date',
                dateFormat: 'YYYY-MM-DD',
                correctFormat: true,
                title: 'FECHA',
                width: 60
            },
            autocompleteCol(productosLabels, productosMap, productosRevMap, 'producto_id',
                'PRODUCTO', 120),
            autocompleteCol(proveedoresLabels, proveedoresMap, proveedoresRevMap,
                'tienda_comercial_id',
                'PROVEEDOR', 90),
            autocompleteCol(tipoDocumentosLabels, tipoDocumentosMap, tipoDocumentosRevMap,
                'tipo_compra_codigo',
                'TIPO DOC', 60),
            {
                data: 'serie',
                type: 'text',
                title: 'SERIE',
            },
            {
                data: 'numero',
                type: 'text',
                title: 'NÚMERO',
            },
            {
                data: 'tipo_kardex',
                title: 'KARDEX',
                type: 'dropdown',
                source: ['blanco', 'negro'],
                className: '!text-center',
            },

            {
                data: 'stock',
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0.000'
                },
                title: 'CANTIDAD',
            },
            {
                data: 'costo_por_unidad',
                type: 'numeric',
                readOnly: true,
                numericFormat: {
                    pattern: '0,0.00'
                },
                title: 'COSTO X <br/>UNIDAD',
                className: '!bg-muted',
            },
            {
                data: 'total',
                type: 'numeric',
                numericFormat: {
                    pattern: '0,0.00'
                },
                title: 'TOTAL',
            }
            ];

            return columns;
        },
        guardarCompraInsumos() {
            if (this.filasModificadas.length === 0) {
                alert('Niguna fila modificada');
                return;
            };

            const data = [...this.filasModificadas]
                .map(i => this.hot.getSourceDataAtRow(i))
                .filter(fila => fila && Object.values(fila).some(v => v !== null && v !== ''));

            $wire.guardarCompraInsumos(data);
        },
    }));
</script>
@endscript