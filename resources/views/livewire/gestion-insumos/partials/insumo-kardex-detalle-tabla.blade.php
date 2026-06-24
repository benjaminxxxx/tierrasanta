{{-- kardex-detalle.blade.php --}}
<div class="my-5" x-data="kardexDetalle">

    {{-- FILTROS --}}
    <div class="flex flex-wrap gap-3 mb-4 items-end">
        {{-- Rango de fechas --}}
        <div class="flex flex-col gap-1">
            <x-label>Fecha desde</x-label>
            <x-input type="date" x-model="filtros.fechaDesde" @change="applyFilters()" />
        </div>
        <div class="flex flex-col gap-1">
            <x-label>Fecha hasta</x-label>
            <x-input type="date" x-model="filtros.fechaHasta" @change="applyFilters()" />
        </div>

        {{-- Búsqueda por factura / número de comprobante --}}
        <div class="flex flex-col gap-1">
            <x-label>Factura / N° Comprobante</x-label>
            <x-input x-model="filtros.comprobante" @input="applyFilters()" placeholder="Serie o número..." />
        </div>

        {{-- Filtro por lote --}}
        <div class="flex flex-col gap-1">
            <x-label>Lote</x-label>
            <x-input x-model="filtros.lote" @input="applyFilters()" placeholder="Lote..." />
        </div>
    </div>

    {{-- Botón limpiar --}}
    <x-button @click="clearFilters()" variant="ghost">
        Limpiar filtros
    </x-button>

    {{-- Contador de resultados --}}
    <span class="text-xs text-muted-foreground self-end ml-auto">
        <span x-text="filteredCount"></span> registro(s)
    </span>

    {{-- HANDSONTABLE --}}
    <div wire:ignore>
        <div x-ref="tableContainer"></div>
    </div>
</div>

@script
<script>
    Alpine.data('kardexDetalle', () => ({
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
            this.$nextTick(() => this.initTable());
        },

        initTable() {
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
                rowHeaders: true,
                width: '100%',
                stretchH: 'all',
                licenseKey: 'non-commercial-and-evaluation',

                columns: [
                    // Documento
                    { data: 'fecha', readOnly: true, type: 'text',width:90 },
                    { data: 'tipo_documento', readOnly: true, type: 'text',width:50 },
                    { data: 'serie', readOnly: true, type: 'text' },
                    { data: 'numero', readOnly: true, type: 'text' },
                    // Tipo operación
                    { data: 'tipo_operacion', readOnly: true, type: 'text' },
                    // Entradas
                    {
                        data: (row) => row.tipo_mov === 'entrada' ? this._fmt3(row.entrada_cantidad) : '-',
                        readOnly: true, type: 'text', className: 'htRight',
                    },
                    {
                        data: (row) => row.tipo_mov === 'entrada' ? this._fmt2(row.entrada_costo_unitario) : '-',
                        readOnly: true, type: 'text', className: 'htRight',
                    },
                    {
                        data: (row) => row.tipo_mov === 'entrada' ? this._fmt2(row.entrada_costo_total) : '-',
                        readOnly: true, type: 'text', className: 'htRight',
                    },
                    // Salidas
                    {
                        data: (row) => row.tipo_mov === 'salida' ? this._fmt3(row.salida_cantidad) : '-',
                        readOnly: true, type: 'text', className: 'htRight',
                    },
                    {
                        data: (row) => row.tipo_mov === 'salida' ? (row.salida_lote ?? row.salida_maquinaria ?? '-') : '-',
                        readOnly: true, type: 'text', className: 'htCenter',
                    },
                    {
                        data: (row) => row.tipo_mov === 'salida' ? this._fmt2(row.salida_costo_unitario) : '-',
                        readOnly: true, type: 'text', className: 'htRight',
                    },
                    {
                        data: (row) => row.tipo_mov === 'salida' ? this._fmt2(row.salida_costo_total) : '-',
                        readOnly: true, type: 'text', className: 'htRight',
                    },
                    // Saldo final
                    {
                        data: (row) => this._fmt3(row.saldo_cantidad),
                        readOnly: true, type: 'text', className: 'htRight',
                    },
                    {
                        data: (row) => this._fmt2(row.saldo_costo_unitario),
                        readOnly: true, type: 'text', className: 'htRight',
                    },
                    {
                        data: (row) => this._fmt2(row.saldo_costo_total),
                        readOnly: true, type: 'text', className: 'htRight',
                    },
                ],
            });
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

        _fmt2(val) {
            return val != null ? Number(val).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-';
        },

        _fmt3(val) {
            return val != null ? Number(val).toLocaleString('es-PE', { minimumFractionDigits: 3, maximumFractionDigits: 3 }) : '-';
        },
    }));
</script>
@endscript