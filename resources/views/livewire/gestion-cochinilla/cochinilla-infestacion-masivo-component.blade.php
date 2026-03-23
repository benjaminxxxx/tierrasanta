<div class="space-y-4" x-data="infestacionesMasivaCochinilla()">

    <x-flex class="justify-between w-full">
        <x-breadcrumb :items="$breadcrumb" />
        <x-flex>
            {{-- Opciones Adicionales --}}
            @include('comun.selector-mes-base')

            <div class="ms-3 relative">
                <x-dropdown align="right" width="60">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <button type="button"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                Opciones

                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                </svg>
                            </button>
                        </span>
                    </x-slot>

                    <x-slot name="content">
                        <div class="w-60">
                            <!-- Team Management -->
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                Opciones avanzadas
                            </div>

                            <!-- Team Settings -->
                            <x-dropdown-link wire:click="vincularConCampanias">
                                Vincular con Campañas
                            </x-dropdown-link>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </x-flex>
    </x-flex>

    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>

    <livewire:gestion-cochinilla.cochinilla-infestacion-resumen-component :mes="$mes" :anio="$anio"
        wire:key="cpm{{ $anio }}_{{ $mes }}_{{ $codigoActualizacion }}" />

    <x-inferior-derecha>
        <x-button @click="guardarInfestacionMasivo">
            <i class="fa fa-save"></i> Guardar Información
        </x-button>
    </x-inferior-derecha>

    <x-dialog-modal wire:model.live="modalAuditoria">
        <x-slot name="title">Historial de auditoría</x-slot>

        <x-slot name="content">

            {{-- Resumen: creado por / última edición --}}
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

            {{-- Historial completo --}}
            @forelse($auditoriaHistorial as $entrada)
                <div class="mb-4 border-b border-border pb-3">
                    <div class="flex items-center justify-between text-sm">
                        <span
                            class="font-semibold uppercase
                        {{ $entrada['accion'] === 'crear'
                            ? 'text-green-600'
                            : ($entrada['accion'] === 'eliminar'
                                ? 'text-red-600'
                                : 'text-yellow-600') }}">
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

                    @if ($entrada['observacion'])
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
        Alpine.data('infestacionesMasivaCochinilla', () => ({
            filasModificadas: @entangle('filasModificadas'),
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            init() {
                this.initTable([]);
                $watch('darkMode', value => {

                    this.isDark = value;
                    const columns = this.getColumns();
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });

                });
                Livewire.on('cargarDataInfestacion', ({
                    data
                }) => {
                    this.initTable(data);
                })
            },
            initTable(tableData) {
                if (this.hot) {
                    this.hot.destroy();
                }
                const container = this.$refs.tableContainer;
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
                    afterChange: (changes, source) => {
                        // Corta el bucle: si nosotros mismos disparamos el cambio, ignorar
                        if (source === 'recalculado' || source === 'loadData') return;

                        changes.forEach(([row]) => {
                            if (!this.filasModificadas.includes(row)) {
                                this.filasModificadas = [...this.filasModificadas, row];
                            }
                        });

                        if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;

                        // Columnas que afectan cada cálculo
                        const afectanKgMadresHa = new Set(['area', 'kg_madres']);
                        const afectanInfestadores = new Set(['numero_envases', 'capacidad_envase']);
                        // infestadores_por_ha y madres_por_infestador dependen de infestadores,
                        // pero como infestadores es readOnly, solo cambia cuando nosotros lo seteamos.
                        // Se recalculan junto con infestadores cuando cambian sus inputs.
                        const afectanTodo = new Set([...afectanKgMadresHa, ...afectanInfestadores]);

                        // Agrupar filas afectadas y qué recalcular en cada una
                        const filasMap = new Map(); // row -> Set de cálculos necesarios

                        changes.forEach(([row, prop]) => {
                            if (!afectanTodo.has(prop))
                                return; // columna irrelevante, ignorar

                            if (!filasMap.has(row)) filasMap.set(row, new Set());

                            if (afectanKgMadresHa.has(prop)) {
                                filasMap.get(row).add('kg_madres_por_ha');
                            }
                            if (afectanInfestadores.has(prop)) {
                                // Si cambian envases/capacidad, recalcular infestadores
                                // y los derivados que dependen de él
                                filasMap.get(row).add('infestadores');
                                filasMap.get(row).add('madres_por_infestador');
                                filasMap.get(row).add('infestadores_por_ha');
                            }
                            // Si cambia area o kg_madres, también afectan a los derivados de infestadores
                            if (afectanKgMadresHa.has(prop)) {
                                filasMap.get(row).add('madres_por_infestador'); // M = F / L
                                filasMap.get(row).add('infestadores_por_ha'); // N = L / D
                            }
                        });

                        filasMap.forEach((calculos, row) => {
                            const area = parseFloat(hot.getDataAtRowProp(row, 'area')) || 0;
                            const kgMadres = parseFloat(hot.getDataAtRowProp(row,
                                'kg_madres')) || 0;
                            const envases = parseFloat(hot.getDataAtRowProp(row,
                                'numero_envases')) || 0;
                            const capacidad = parseFloat(hot.getDataAtRowProp(row,
                                'capacidad_envase')) || 0;

                            // Calcular infestadores primero porque otros dependen de él
                            const infestadores = capacidad * envases || 0;

                            const updates = [];

                            if (calculos.has('kg_madres_por_ha')) {
                                updates.push([row, 'kg_madres_por_ha', area > 0 ? kgMadres /
                                    area : null
                                ]);
                            }
                            if (calculos.has('infestadores')) {
                                updates.push([row, 'infestadores', infestadores || null]);
                            }
                            if (calculos.has('madres_por_infestador')) {
                                updates.push([row, 'madres_por_infestador', infestadores >
                                    0 ? kgMadres / infestadores : null
                                ]);
                            }
                            if (calculos.has('infestadores_por_ha')) {
                                updates.push([row, 'infestadores_por_ha', area > 0 ?
                                    infestadores / area : null
                                ]);
                            }

                            if (updates.length > 0) {
                                hot.setDataAtRowProp(updates, null, null, 'recalculado');
                            }
                        });
                    },
                    contextMenu: {
                        items: {
                            'ver_auditoria': {
                                name: 'Ver historial de cambios',
                                callback: (key, selection) => {
                                    const row = selection[0].start.row;
                                    const rowData = hot.getSourceDataAtRow(row);
                                    const id = rowData?.id ?? null;

                                    if (!id) {
                                        alert(
                                            'Debes guardar los cambios antes de ver el historial.'
                                        );
                                        return;
                                    }

                                    $wire.verAuditoria(id);
                                }
                            }
                        }
                    },
                });

                this.hot = hot;
                this.hot.render();
            },
            getColumns() {
                return [

                    {
                        data: 'tipo_infestacion',
                        type: 'dropdown',
                        source: ['infestacion', 'reinfestacion'],
                        title: 'TIPO',
                        width: 70
                    },

                    {
                        data: 'fecha',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        title: 'FECHA INFESTACION',
                        width: 70
                    },

                    {
                        data: 'campo_nombre',
                        type: 'text',
                        title: 'CAMPO'
                    },

                    {
                        data: 'area',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.000'
                        },
                        title: 'AREA',
                        className: '!text-center'
                    },

                    {
                        data: 'campania',
                        type: 'text',
                        title: 'CAMPAÑA',
                        readOnly: true,
                        className: '!bg-muted !text-center'
                    },

                    {
                        data: 'kg_madres',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.00'
                        },
                        title: 'KG MADRES'
                    },

                    {
                        data: 'kg_madres_por_ha',
                        type: 'numeric',
                        readOnly: true,
                        title: 'KG MADRES/HA',
                        className: '!bg-muted'
                    },

                    {
                        data: 'campo_origen_nombre',
                        type: 'text',
                        title: 'ORIGEN CAMPO'
                    },

                    {
                        data: 'metodo',
                        type: 'dropdown',
                        source: ['carton', 'tubo', 'malla'],
                        title: 'METODO',
                        className: 'uppercase',
                        width: 55
                    },

                    {
                        data: 'capacidad_envase',
                        type: 'numeric',
                        title: 'UND X ENVASE'
                    },

                    {
                        data: 'numero_envases',
                        type: 'numeric',
                        title: 'ENVASES'
                    },

                    {
                        data: 'infestadores',
                        type: 'numeric',
                        readOnly: true,
                        title: 'INFESTADORES',
                        className: '!bg-muted'
                    },

                    {
                        data: 'madres_por_infestador',
                        type: 'numeric',
                        readOnly: true,
                        title: 'MADRES/INFES',
                        className: '!bg-muted'
                    },

                    {
                        data: 'infestadores_por_ha',
                        type: 'numeric',
                        readOnly: true,
                        title: 'INFESTADORES/HA',
                        className: '!bg-muted'
                    }

                ];
            },
            guardarInfestacionMasivo() {
                if (this.filasModificadas.length === 0) {
                    alert('Niguna fila modificada');
                    return;
                };

                const data = [...this.filasModificadas]
                    .map(i => this.hot.getSourceDataAtRow(i))
                    .filter(fila => fila && Object.values(fila).some(v => v !== null && v !== ''));

                $wire.guardarInfestacionMasivo(data);
            },
        }))
    </script>
@endscript
