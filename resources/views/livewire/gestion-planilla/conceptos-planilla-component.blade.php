<div class="space-y-4" x-data="conceptosPlanillaLista">

    <!-- Header -->
    <x-flex class="justify-between">
        <div>
            <x-title>Gestión de Conceptos de Planilla</x-title>
            <x-subtitle>Administra los conceptos de planilla de tus empleados</x-subtitle>
        </div>

    </x-flex>

    <x-card class="mt-4">
        <div wire:ignore class="w-full">
            <div x-ref="tableContainer"></div>
        </div>
        <x-flex class="justify-end">
            <x-button @click="sendDataConceptos" class="mt-5">
                <i class="fa fa-save"></i> Guardar cambios
            </x-button>
        </x-flex>

    </x-card>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('conceptosPlanillaLista', () => ({
            tableData: @json($conceptos),
            hot: null,
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            init() {
                $watch('darkMode', value => {

                    this.isDark = value;
                    const columns = this.getColumns();
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });
                });
                this.initTable();
                /*
                Livewire.on('recargarEvaluacion', (data) => {
                    this.tableData = data[0].table;
                    this.hot.destroy();
                    this.initTable();
                    this.hot.loadData(this.tableData);
                });
                Livewire.on('guardadoConfirmado', () => {
                    this.sendDataPoblacionPlanta();
                });*/
            },
            initTable() {

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    colHeaders: true,
                    rowHeaders: true,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                    columns: this.getColumns(),
                    manualColumnResize: false,
                    width: '100%',
                    autoColumnSize: true,
                    minSpareRows: 1,
                    manualRowResize: true,
                    stretchH: 'all',
                    licenseKey: 'non-commercial-and-evaluation',

                });

                this.hot = hot;
                this.hot.render();
            },
            getColumns() {
                return [{
                        data: 'codigo_sunat',
                        type: 'text',
                        title: 'CÓDIGO<br/>SUNAT',
                        className: 'htCenter',
                        placeholder: 'Ej: 0803'
                    },
                    {
                        data: 'nombre',
                        type: 'text',
                        title: 'NOMBRE DEL<br/>CONCEPTO',
                        className: 'htLeft'
                    },
                    {
                        data: 'abreviatura_excel',
                        type: 'text',
                        title: 'ABREV.<br/>EXCEL',
                        className: 'htCenter'
                    },
                    {
                        data: 'clase',
                        type: 'dropdown',
                        source: ['ingreso', 'descuento', 'aporte_patronal'],
                        title: 'CLASE',
                        className: 'htCenter'
                    },
                    {
                        data: 'origen',
                        type: 'dropdown',
                        source: ['blanco', 'negro'],
                        title: 'ORIGEN',
                        className: 'htCenter'
                    },
                    {
                        data: 'metodo_calculo',
                        type: 'dropdown',
                        source: ['porcentaje', 'monto_fijo', 'manual'],
                        title: 'MÉTODO<br/>CÁLCULO',
                        className: 'htCenter'
                    },
                    {
                        data: 'valor_base',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.0000',
                            culture: 'en-US'
                        },
                        title: 'VALOR<br/>BASE',
                        className: 'htRight'
                    },
                    {
                        data: 'incluye_igv',
                        type: 'checkbox',
                        title: '¿INC.<br/>IGV?',
                        className: 'htCenter',
                        // Handsontable maneja true/false o 1/0 automáticamente con checkbox
                    },
                    {
                        data: 'fecha_inicio',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        correctFormat: true,
                        title: 'FECHA<br/>INICIO',
                        className: 'htCenter'
                    },
                    {
                        data: 'fecha_fin',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        correctFormat: true,
                        title: 'FECHA<br/>FIN',
                        className: 'htCenter'
                    }
                ];
            },
            sendDataConceptos() {
                let allData = [];
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const rowData = this.hot.getSourceDataAtRow(row);
                    allData.push(rowData);
                }
                const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                    null && cell !== ''));
                $wire.guardarConceptos(filteredData);
            },
        }));
    </script>
@endscript
