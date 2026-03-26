<div x-data="gestionUsos" class="space-y-4">
    <x-title>Gestión de Usos</x-title>

    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>

    {{-- Modal Auditoría --}}
    <x-dialog-modal wire:model.live="modalAuditoria">
        <x-slot name="title">Historial de auditoría</x-slot>

        <x-slot name="content">
            @php
                $entradaCreacion = collect($auditoriaHistorial)->firstWhere('accion', 'crear');
                $ultimaEdicion = collect($auditoriaHistorial)
                    ->where('accion', 'editar')
                    ->sortByDesc('fecha_accion')
                    ->first();
            @endphp

            <div class="flex gap-6 mb-4 text-xs text-muted-foreground border-b border-border pb-3">
                <div>
                    <span class="font-semibold text-card-foreground">Creado por:</span>
                    {{ $entradaCreacion['usuario_nombre'] ?? '—' }}
                    @if ($entradaCreacion)
                        <span class="ml-1 text-gray-400">
                            {{ \Carbon\Carbon::parse($entradaCreacion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
                <div>
                    <span class="font-semibold text-card-foreground">Última edición:</span>
                    {{ $ultimaEdicion['usuario_nombre'] ?? '—' }}
                    @if ($ultimaEdicion)
                        <span class="ml-1 text-gray-400">
                            {{ \Carbon\Carbon::parse($ultimaEdicion['fecha_accion'])->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            @forelse($auditoriaHistorial as $entrada)
                <div class="mb-4 border-b border-border pb-3">
                    <div class="flex items-center justify-between text-sm">
                        <span
                            class="font-semibold uppercase
                            {{ $entrada['accion'] === 'crear' ? 'text-green-600' : ($entrada['accion'] === 'eliminar' ? 'text-red-600' : 'text-yellow-600') }}">
                            {{ $entrada['accion'] }}
                        </span>
                        <span class="text-gray-400 text-xs">
                            {{ \Carbon\Carbon::parse($entrada['fecha_accion'])->format('d/m/Y H:i') }}
                            — {{ $entrada['usuario_nombre'] ?? 'Sistema' }}
                        </span>
                    </div>

                    @if (!empty($entrada['cambios']))
                        @if ($entrada['accion'] === 'editar')
                            <table class="mt-2 w-full text-xs text-gray-700">
                                <thead>
                                    <tr class="text-left text-gray-400">
                                        <th class="pr-4">Campo</th>
                                        <th class="pr-4">Antes</th>
                                        <th>Después</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($entrada['cambios']['antes'] ?? [] as $campo => $valorAntes)
                                        <tr>
                                            <td class="pr-4 font-medium text-muted-foreground">{{ $campo }}</td>
                                            <td class="pr-4 text-red-500">{{ $valorAntes ?? '—' }}</td>
                                            <td class="text-green-600">
                                                {{ $entrada['cambios']['despues'][$campo] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <pre class="mt-2 text-xs bg-muted rounded p-2 overflow-auto max-h-40">{{ json_encode(array_values($entrada['cambios'])[0] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @endif
                    @endif

                    @if (!empty($entrada['observacion']))
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

    <x-inferior-derecha>
        <x-button @click="guardarUsos">
            <i class="fa fa-save"></i> Guardar Registros
        </x-button>
    </x-inferior-derecha>
</div>

@script
    <script>
        Alpine.data('gestionUsos', () => ({
            hot: null,
            filasModificadas: @entangle('filasModificadas'),
            isDark: JSON.parse(localStorage.getItem('darkMode') ?? 'false'),

            init() {
                this.initTable(@js($usos));

                $watch('darkMode', value => {
                    this.isDark = value;
                    this.hot?.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                    });
                });

                Livewire.on('cargarDataUsos', ({
                    data
                }) => {
                    this.filasModificadas = [];
                    this.initTable(data);
                });
            },

            initTable(tableData) {
                if (this.hot) {
                    this.hot.destroy();
                }

                const container = this.$refs.tableContainer;

                this.hot = new Handsontable(container, {
                    data: tableData,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                    colHeaders: true,
                    rowHeaders: true,
                    columns: this.getColumns(),
                    stretchH: 'all',
                    minSpareRows: 1,
                    manualColumnResize: true,
                    manualRowResize: true,
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

                    contextMenu: {
                        items: {
                            'eliminar_uso': {
                                name: '<span style="color:#ef4444"><i class="fa fa-trash" style="margin-right:6px"></i>Eliminar</span>',
                                callback: (key, selection) => {
                                    const row = selection[0].start.row;
                                    const id = this.hot.getDataAtRowProp(row, 'id');

                                    if (!id) {
                                        this.hot.alter('remove_row', row);
                                        return;
                                    }

                                    if (confirm(
                                            '¿Eliminar este uso? Esta acción no se puede deshacer.'
                                            )) {
                                        $wire.eliminarUso(id);
                                    }
                                }
                            },
                            'ver_auditoria': {
                                name: '<i class="fa fa-history" style="margin-right:6px"></i>Ver auditoría',
                                callback: (key, selection) => {
                                    const row = selection[0].start.row;
                                    const id = this.hot.getDataAtRowProp(row, 'id');

                                    if (!id) {
                                        alert('Guarda el registro antes de ver su auditoría.');
                                        return;
                                    }

                                    $wire.verAuditoria(id);
                                }
                            }
                        }
                    },
                });
            },

            getColumns() {
                return [{
                        data: 'nombre',
                        title: 'Nombre',
                        width: 80
                    },
                    {
                        data: 'categoria_codigo',
                        title: 'Categoría',
                        type: 'dropdown',
                        source: ['pesticida', 'fertilizante', 'combustible', 'herramienta'],
                        width: 35,
                    },
                    {
                        data: 'descripcion',
                        title: 'Descripción',
                        width: 150
                    },
                    {
                        data: 'productos_count',
                        title: 'N° Productos',
                        readOnly: true,
                        className: '!bg-muted',
                        type: 'numeric'
                    },
                    {
                        data: 'created_at',
                        title: 'Creado',
                        readOnly: true,
                        className: '!bg-muted'
                    },
                    {
                        data: 'updated_at',
                        title: 'Actualizado',
                        readOnly: true,
                        className: '!bg-muted'
                    },
                ];
            },

            guardarUsos() {
                if (this.filasModificadas.length === 0) return;

                const filas = this.filasModificadas
                    .map(row => {
                        const rowData = this.hot.getSourceDataAtRow(row);
                        if (!rowData?.nombre?.trim()) return null;
                        return {
                            id: rowData.id ?? null,
                            nombre: rowData.nombre,
                            categoria_codigo: rowData.categoria_codigo ?? null,
                            descripcion: rowData.descripcion ?? null,
                            activo: rowData.activo === 'Sí' || rowData.activo === true,
                        };
                    })
                    .filter(Boolean);

                if (filas.length === 0) {
                    alert('Debe modificar al menos 1 fila o agregar mas registros');
                    return;
                };

                $wire.guardarUsos(filas);
            },
        }));
    </script>
@endscript
