<div x-data="gestionSubcategorias" class="space-y-4">
 
    <x-flex>
        <x-title>Gestión de Subcategorías</x-title>
        <x-button href="{{ route('categorias.index') }}">
            Administrar categorías ↗
        </x-button>
    </x-flex>
    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>

    <x-inferior-derecha>
        <x-button @click="guardarSubcategorias()">
            <i class="fa fa-save"></i> Guardar Subcategorías Modificadas
        </x-button>
    </x-inferior-derecha>

    {{-- Modal Auditoría --}}
    <x-dialog-modal wire:model.live="modalAuditoria">
        <x-slot name="title">Historial de auditoría</x-slot>
        <x-slot name="content">
            @php
                $entradaCreacion = collect($auditoriaHistorial)->firstWhere('accion', 'crear');
                $ultimaEdicion   = collect($auditoriaHistorial)
                    ->where('accion', 'editar')
                    ->sortByDesc('fecha_accion')
                    ->first();
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

            @forelse($auditoriaHistorial as $entrada)
                <div class="mb-4 border-b border-border pb-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold uppercase
                            {{ $entrada['accion'] === 'crear' ? 'text-green-600' : ($entrada['accion'] === 'eliminar' ? 'text-red-600' : 'text-yellow-600') }}">
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
                            <pre class="mt-2 text-xs bg-muted rounded p-2 overflow-auto max-h-40">{{ json_encode(array_values($entrada['cambios'])[0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @endif
                    @endif

                    @if(!empty($entrada['observacion']))
                        <p class="mt-1 text-xs text-card-foreground italic">{{ $entrada['observacion'] }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-card-foreground">Sin historial de cambios.</p>
            @endforelse
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalAuditoria', false)">Cerrar</x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('gestionSubcategorias', () => ({
        filasModificadas: @entangle('filasModificadas'),
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        tableData: @js($registros),
        listaCategorias: @js($listaCategorias),

        init() {
            this.initTable(this.tableData);

            $watch('darkMode', value => {
                this.isDark = value;
                this.hot?.updateSettings({
                    themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                    columns: this.getColumns(),
                });
            });

            Livewire.on('cargarDataSubcategorias', ({ data }) => {
                if (!this.$refs.tableContainer) return;
                this.$nextTick(() => {
                    if (!this.$refs.tableContainer) return;
                    this.tableData = data;
                    this.initTable(data);
                });
            });
        },

        initTable(tableData) {
            if (this.hot) {
                try { this.hot.destroy(); } catch (e) {}
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
                manualColumnResize: false,
                manualRowResize: true,
                stretchH: 'all',
                minSpareRows: 1,
                autoColumnSize: false,
                licenseKey: 'non-commercial-and-evaluation',
                afterChange: (changes, source) => {
                    if (source === 'loadData') return;
                    changes?.forEach(([row]) => {
                        if (!this.filasModificadas.includes(row)) {
                            this.filasModificadas = [...this.filasModificadas, row];
                        }
                    });
                },
                afterRenderer: (TD, row, col, prop, value, cellProperties) => {
    if (col !== 3) return;

    const hot = cellProperties.instance; // 👈 clave
    const sourceRow = hot.getSourceDataAtRow(row);

    if (!sourceRow?.id) return;

    TD.innerHTML = '';
    TD.classList.add('htCenter');

    const btnAudit = document.createElement('button');
    btnAudit.innerHTML = '<i class="fa fa-history"></i>';
    btnAudit.title = 'Ver auditoría';
    btnAudit.className = 'px-2 py-0.5 text-xs text-blue-600 hover:text-blue-800';
    btnAudit.onclick = () => $wire.verAuditoria(sourceRow.id);

    const btnDel = document.createElement('button');
    btnDel.innerHTML = '<i class="fa fa-trash"></i>';
    btnDel.title = 'Eliminar';
    btnDel.className = 'px-2 py-0.5 text-xs text-red-500 hover:text-red-700';
    btnDel.onclick = () => {
        if (confirm(`¿Eliminar "${sourceRow.nombre}"?`)) {
            $wire.eliminar(sourceRow.id);
        }
    };

    TD.append(btnAudit, btnDel);
}
            });
        },

        getColumns() {
            // Construir source para el dropdown a partir de listaCategorias
            // listaCategorias = { codigo: descripcion, ... }
            const categoriasSource = Object.entries(this.listaCategorias)
                .map(([codigo, descripcion]) => ({ codigo, descripcion }));

            return [
                {
                    // Dropdown de categoría: muestra descripcion, guarda codigo
                    data: 'categoria_codigo',
                    title: 'CATEGORÍA',
                    type: 'dropdown',
                    source: categoriasSource.map(c => c.codigo),
                    // Renderizar la descripción en vez del código
                    renderer: (hotInstance, TD, row, col, prop, value) => {
                        const found = categoriasSource.find(c => c.codigo === value);
                        TD.innerText = found ? found.descripcion : (value ?? '');
                        TD.classList.add('htMiddle');
                    },
                    width: 160,
                },
                {
                    data: 'nombre',
                    title: 'NOMBRE',
                    type: 'text',
                    width: 200,
                },
                {
                    data: 'descripcion',
                    title: 'DESCRIPCIÓN',
                    type: 'text',
                    width: 300,
                },
                {
                    // Columna de acciones — no editable, renderizada por afterRenderer
                    data: null,
                    title: 'ACCIONES',
                    readOnly: true,
                    width: 90,
                    disableVisualSelection: true,
                },
            ];
        },

        guardarSubcategorias() {
            if (this.filasModificadas.length === 0) {
                alert('Ninguna fila modificada');
                return;
            }

            const data = [...this.filasModificadas]
                .map(i => this.hot.getSourceDataAtRow(i))
                .filter(fila => fila && Object.values(fila).some(v => v !== null && v !== ''));

            $wire.guardarSubcategorias(data);
        },
    }))
</script>
@endscript