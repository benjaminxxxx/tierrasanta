<div x-data="insumoKardexDetalle">

    <x-card>
        <x-flex class="justify-between">
            <x-title>

                <x-flex>
                    <div>
                        <a href="{{ route('gestion_insumos.kardex') }}"
                            class="underline text-blue-600 dark:text-blue-300">KARDEX</a> /
                        KARDEX {{ mb_strtoupper($insumoKardex->tipo) }} {{ mb_strtoupper($insumoKardex->descripcion) }}
                        {{ $insumoKardex->anio }}
                    </div>
                </x-flex>
            </x-title>
            <x-flex>

                <div class="ms-3 relative">
                    <x-dropdown align="right" width="60">
                        <x-slot name="trigger">
                            <span class="inline-flex rounded-md">
                                <button type="button"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                    OPCIONES
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
                                @if ($kardexOpuesto)
                                    <x-dropdown-link
                                        href="{{ route('gestion_insumos.kardex.detalle', $kardexOpuesto->id) }}">
                                        Ver Kardex {{ ucfirst($tipoOpuesto) }}
                                    </x-dropdown-link>
                                @endif

                                @if ($insumoKardex->estado == 'activo')
                                    <div x-data="{ openFileDialog() { $refs.fileInputNegro.click() } }">
                                        @can(\App\Constants\Permisos::INSUMO_KARDEX_IMPORTAR)
                                            <x-dropdown-link @click="openFileDialog()">
                                                Importar Kardex {{ $insumoKardex->tipo }}
                                            </x-dropdown-link>
                                        @endcan

                                        <input type="file"
                                            accept="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                            x-ref="fileInputNegro" style="display: none;"
                                            wire:model.live="archivoExcelKardex" />
                                    </div>
                                @endif
                                @if ($insumoKardex->estado == 'activo')
                                    <x-dropdown-link
                                        @click="$wire.dispatch('cerrarKardexSeleccionado',{kardexId:{{ $insumoKardex->id }}})">
                                        Cerrar Kardex
                                    </x-dropdown-link>
                                @endif
                                @if ($insumoKardex->estado == 'cerrado')
                                    <x-dropdown-link
                                        @click="$wire.dispatch('reabrirKardexSeleccionado',{kardexId:{{ $insumoKardex->id }}})">
                                        Reabrir Kardex
                                    </x-dropdown-link>
                                @endif
                                @if ($insumoKardex->estado == 'activo')
                                    @can(\App\Constants\Permisos::INSUMO_KARDEX_GENERAR_RESUMEN)
                                        <x-dropdown-link wire:click="generarDetalleKardexInsumo">
                                            Generar Resumen
                                        </x-dropdown-link>
                                    @endcan
                                @endif
                                @if ($insumoKardex->file)
                                    <x-dropdown-link href="{{ Storage::disk('public')->url($insumoKardex->file) }}">
                                        Descargar Reporte
                                    </x-dropdown-link>
                                @endif
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
            </x-flex>

        </x-flex>


        <div class="mt-4">
            {{-- -TABLA --}}
            @include('livewire.gestion-insumos.partials.insumo-kardex-detalle-tabla')
        </div>


    </x-card>

    <x-dialog-modal wire:model.live="mostrarModalImportacionKardex" maxWidth="full">
        <x-slot name="title">
            Comparar Kardex físico — {{ $insumoKardex->producto->nombre_comercial ?? '' }}
            ({{ strtoupper($insumoKardex->tipo) }} · {{ $insumoKardex->anio }})
        </x-slot>

        <x-slot name="content">
            @if ($datosImportacionKardex)
                <div class="space-y-6">

                    {{-- ── SALDO INICIAL ─────────────────────────────── --}}
                    @if ($datosImportacionKardex['saldo_inicial']['propuesto'])
                        <div>
                            <h4 class="text-sm font-bold uppercase text-muted-foreground mb-2">Saldo Inicial</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="border rounded-lg p-3">
                                    <p class="text-xs text-muted-foreground mb-1">Actual (en el Kardex)</p>
                                    <p>Stock:
                                        <strong>{{ number_format($datosImportacionKardex['saldo_inicial']['actual']['stock_inicial'], 3) }}</strong>
                                    </p>
                                    <p>Costo total:
                                        <strong>{{ number_format($datosImportacionKardex['saldo_inicial']['actual']['costo_total'], 2) }}</strong>
                                    </p>
                                </div>
                                <div class="border rounded-lg p-3 bg-emerald-500/5 border-emerald-500/30">
                                    <p class="text-xs text-emerald-600 mb-1">Propuesto (Excel)</p>
                                    <p>Stock:
                                        <strong>{{ number_format($datosImportacionKardex['saldo_inicial']['propuesto']['stock_inicial'], 3) }}</strong>
                                    </p>
                                    <p>Costo total:
                                        <strong>{{ number_format($datosImportacionKardex['saldo_inicial']['propuesto']['costo_total'], 2) }}</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ── COMPRAS ────────────────────────────────────── --}}
                    <div>
                        <h4 class="text-sm font-bold uppercase text-muted-foreground mb-2">
                            Compras — {{ $datosImportacionKardex['compras']['actuales']->count() }} actuales (se eliminarán)
                            vs
                            {{ collect($datosImportacionKardex['compras']['propuestas'])->pluck('lineas')->flatten(1)->count() }}
                            propuestas
                        </h4>
                        <div class="grid grid-cols-2 gap-4">

                            <div class="border rounded-lg overflow-hidden">
                                <div class="bg-red-500/10 text-red-600 text-xs font-bold px-3 py-1.5">Actual — se eliminará
                                </div>
                                <div class="max-h-56 overflow-y-auto">
                                    <table class="w-full text-xs">
                                        <thead class="bg-muted sticky top-0">
                                            <tr>
                                                <th class="px-2 py-1 text-left">Fecha</th>
                                                <th class="px-2 py-1 text-left">Serie-Núm</th>
                                                <th class="px-2 py-1 text-right">Cant.</th>
                                                <th class="px-2 py-1 text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($datosImportacionKardex['compras']['actuales'] as $detalle)
                                                <tr class="border-t">
                                                    <td class="px-2 py-1">
                                                        {{ $detalle->compra?->fecha_emision ? \Carbon\Carbon::parse($detalle->compra->fecha_emision)->format('d/m/Y') : 'N/A' }}
                                                    </td>
                                                    <td class="px-2 py-1">
                                                        {{ $detalle->compra?->serie ?? '---' }}-{{ $detalle->compra?->numero ?? '---' }}
                                                    </td>
                                                    <td class="px-2 py-1 text-right">{{ number_format($detalle->cantidad, 3) }}
                                                    </td>
                                                    <td class="px-2 py-1 text-right">
                                                        {{ number_format($detalle->total_linea, 2) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-2 py-2 text-center text-muted-foreground">Sin
                                                        registros</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="border rounded-lg overflow-hidden border-emerald-500/30">
                                <div class="bg-emerald-500/10 text-emerald-600 text-xs font-bold px-3 py-1.5">Propuesta —
                                    Excel</div>
                                <div class="max-h-56 overflow-y-auto">
                                    <table class="w-full text-xs">
                                        <thead class="bg-muted sticky top-0">
                                            <tr>
                                                <th class="px-2 py-1 text-left">Fecha</th>
                                                <th class="px-2 py-1 text-left">Serie-Núm</th>
                                                <th class="px-2 py-1 text-right">Cant.</th>
                                                <th class="px-2 py-1 text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($datosImportacionKardex['compras']['propuestas'] as $grupo)
                                                @foreach ($grupo['lineas'] as $linea)
                                                    <tr class="border-t">
                                                        <td class="px-2 py-1">
                                                            {{ \Carbon\Carbon::parse($grupo['fecha_compra'])->format('d/m/Y') }}
                                                        </td>
                                                        <td class="px-2 py-1">{{ $grupo['serie'] }}-{{ $grupo['numero'] }}</td>
                                                        <td class="px-2 py-1 text-right">{{ number_format($linea['stock'], 3) }}
                                                        </td>
                                                        <td class="px-2 py-1 text-right">{{ number_format($linea['total'], 2) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-2 py-2 text-center text-muted-foreground">Sin
                                                        registros</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- ── SALIDAS ────────────────────────────────────── --}}
                    <div>
                        <h4 class="text-sm font-bold uppercase text-muted-foreground mb-2">
                            Salidas — {{ $datosImportacionKardex['salidas']['actuales']->count() }} actuales (se eliminarán)
                            vs {{ count($datosImportacionKardex['salidas']['propuestas']) }} propuestas
                        </h4>
                        <div class="grid grid-cols-2 gap-4">

                            <div class="border rounded-lg overflow-hidden">
                                <div class="bg-red-500/10 text-red-600 text-xs font-bold px-3 py-1.5">Actual — se eliminará
                                </div>
                                <div class="max-h-56 overflow-y-auto">
                                    <table class="w-full text-xs">
                                        <thead class="bg-muted sticky top-0">
                                            <tr>
                                                <th class="px-2 py-1 text-left">Fecha</th>
                                                <th class="px-2 py-1 text-left">Campo/Maq.</th>
                                                <th class="px-2 py-1 text-right">Cant.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($datosImportacionKardex['salidas']['actuales'] as $salida)
                                                <tr class="border-t">
                                                    <td class="px-2 py-1">
                                                        {{ \Carbon\Carbon::parse($salida->fecha_reporte)->format('d/m/Y') }}
                                                    </td>
                                                    <td class="px-2 py-1">
                                                        {{ $salida->campo_nombre ?: $salida->maquinaria?->nombre }}
                                                    </td>
                                                    <td class="px-2 py-1 text-right">{{ number_format($salida->cantidad, 3) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="px-2 py-2 text-center text-muted-foreground">Sin
                                                        registros</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="border rounded-lg overflow-hidden border-emerald-500/30">
                                <div class="bg-emerald-500/10 text-emerald-600 text-xs font-bold px-3 py-1.5">Propuesta —
                                    Excel</div>
                                <div class="max-h-56 overflow-y-auto">
                                    <table class="w-full text-xs">
                                        <thead class="bg-muted sticky top-0">
                                            <tr>
                                                <th class="px-2 py-1 text-left">Fecha</th>
                                                <th class="px-2 py-1 text-left">Campo/Maq.</th>
                                                <th class="px-2 py-1 text-right">Cant.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($datosImportacionKardex['salidas']['propuestas'] as $salida)
                                                <tr class="border-t">
                                                    <td class="px-2 py-1">
                                                        {{ \Carbon\Carbon::parse($salida['fecha_reporte'])->format('d/m/Y') }}
                                                    </td>
                                                    <td class="px-2 py-1">
                                                        {{ $salida['campo_nombre'] ?: (\App\Models\Maquinaria::find($salida['maquinaria_id'])?->nombre ?? '') }}
                                                    </td>
                                                    <td class="px-2 py-1 text-right">{{ number_format($salida['cantidad'], 3) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="px-2 py-2 text-center text-muted-foreground">Sin
                                                        registros</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                    <p class="text-xs text-amber-600 bg-amber-500/10 border border-amber-500/30 rounded-lg p-3">
                        Al aceptar, se eliminarán todos los registros actuales de compras y salidas de este producto
                        para el año {{ $insumoKardex->anio }} ({{ $insumoKardex->tipo }}), y serán reemplazados
                        íntegramente por los datos propuestos del Excel.
                    </p>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="cerrarModalImportacionKardex">Cancelar</x-button>
            <x-button wire:click="confirmarImportacionKardex" wire:loading.attr="disabled">
                Aceptar propuesta del Kardex
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <livewire:gestion-insumos.cerrar-kardex-component />

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('insumoKardexDetalle', () => ({

        tableData: @json($movimientos),
        filteredData: [],
        filteredCount: 0,
        hot: null,
        isDark: JSON.parse(localStorage.getItem('darkMode') ?? 'false'),

        filtros: {
            fechaDesde: '',
            fechaHasta: '',
            comprobante: '',
            lote: '',
        },

        init() {
            this.filteredData = [...this.tableData];
            this.filteredCount = this.filteredData.length;
            this.initTable();
            Livewire.on('regenerarTablaKardex', ({ movimientos }) => {

                this.tableData = movimientos;
                this.hot.loadData(this.tableData);
            });
        },

        initTable() {

            if (this.hot) {
                try {
                    this.hot.destroy();
                } catch (e) { }
                this.hot = null;
            }

            const container = this.$refs.tableContainer;

            this.hot = new Handsontable(container, {
                ...window.HstConfig,
                data: this.filteredData,
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                colHeaders: [
                    // Documento
                    'FECHA',
                    'TIPO (TABLA 10)',
                    'SERIE',
                    'NÚMERO',
                    // Tipo operación
                    'TIPO OPERACIÓN (TABLA 12)',
                    // Entradas
                    'ENT. CANTIDAD',
                    'ENT. COSTO UNIT.',
                    'ENT. COSTO TOTAL',
                    // Salidas
                    'SAL. CANTIDAD',
                    'SAL. LOTE',
                    'SAL. COSTO UNIT.',
                    'SAL. COSTO TOTAL',
                    // Saldo final
                    'SALDO CANTIDAD',
                    'SALDO COSTO UNIT.',
                    'SALDO COSTO TOTAL',
                ],
                // Grupos de cabeceras (nestedHeaders)
                nestedHeaders: [
                    [
                        { label: 'DOCUMENTO DE TRASLADO', colspan: 4 },
                        { label: 'TIPO DE OP.', colspan: 1 },
                        { label: 'ENTRADAS', colspan: 3 },
                        { label: 'SALIDAS', colspan: 4 },
                        { label: 'SALDO FINAL', colspan: 3 },
                    ],
                    [
                        'FECHA', 'TIPO', 'SERIE', 'NÚMERO',
                        '',
                        'CANTIDAD', 'COSTO UNIT.', 'COSTO TOTAL',
                        'CANTIDAD', 'LOTE', 'COSTO UNIT.', 'COSTO TOTAL',
                        'CANTIDAD', 'COSTO UNIT.', 'COSTO TOTAL',
                    ],
                ],
                width: '100%',
                licenseKey: 'non-commercial-and-evaluation',

                columns: [
                    // Documento
                    { data: 'fecha', readOnly: true, type: 'text', width: 90 },
                    { data: 'tipo_documento', readOnly: true, type: 'text', width: 50 },
                    { data: 'serie', readOnly: true, type: 'text' },
                    { data: 'numero', readOnly: true, type: 'text' },
                    // Tipo operación
                    { data: 'tipo_operacion', readOnly: true, type: 'text' },
                    // Entradas
                    { data: 'entrada_cantidad_fmt', readOnly: true, type: 'text', className: 'htRight' },
                    { data: 'entrada_costo_unitario_fmt', readOnly: true, type: 'text', className: 'htRight' },
                    { data: 'entrada_costo_total_fmt', readOnly: true, type: 'text', className: 'htRight' },

                    // Salidas
                    { data: 'salida_cantidad_fmt', readOnly: true, type: 'text', className: 'htRight' },
                    { data: 'salida_destino_fmt', readOnly: true, type: 'text', className: 'htCenter' },
                    { data: 'salida_costo_unitario_fmt', readOnly: true, type: 'text', className: 'htRight' },
                    { data: 'salida_costo_total_fmt', readOnly: true, type: 'text', className: 'htRight' },

                    // Saldo final
                    { data: 'saldo_cantidad_fmt', readOnly: true, type: 'text', className: 'htRight' },
                    { data: 'saldo_costo_unitario_fmt', readOnly: true, type: 'text', className: 'htRight' },
                    { data: 'saldo_costo_total_fmt', readOnly: true, type: 'text', className: 'htRight' },
                ],
            });
            this.hot.render();
        },

        applyFilters() {
            const { fechaDesde, fechaHasta, comprobante, lote } = this.filtros;
            const q = comprobante.trim().toLowerCase();
            const l = lote.trim().toLowerCase();

            this.filteredData = this.tableData.filter(row => {
                // Filtro fecha desde
                if (fechaDesde && row.fecha < fechaDesde) return false;
                // Filtro fecha hasta
                if (fechaHasta && row.fecha > fechaHasta) return false;
                // Filtro comprobante (busca en serie O número)
                if (q) {
                    const serie = (row.serie ?? '').toLowerCase();
                    const numero = (row.numero ?? '').toLowerCase();
                    if (!serie.includes(q) && !numero.includes(q)) return false;
                }
                // Filtro lote
                if (l) {
                    const loteVal = (row.salida_lote ?? row.salida_maquinaria ?? '').toString().toLowerCase();
                    if (!loteVal.includes(l)) return false;
                }
                return true;
            });

            this.filteredCount = this.filteredData.length;

            if (this.hot) {
                this.hot.loadData(this.filteredData);
            }
        },

        clearFilters() {
            this.filtros = { fechaDesde: '', fechaHasta: '', comprobante: '', lote: '' };
            this.applyFilters();
        },


    }));
</script>
@endscript