<div class="space-y-4" x-data="parametrosPlanillaLista">

    <!-- Header -->
    <x-flex class="justify-between">
        <div>
            <x-title>Gestión de Parámetros de Planilla</x-title>
            <x-subtitle>Administra los parámetros de planilla de tus empleados</x-subtitle>
        </div>

    </x-flex>

    <x-card class="mt-4">
        <div wire:ignore class="w-full">
            <div x-ref="tableContainer"></div>
        </div>
        <x-flex class="justify-end">
            <x-button @click="sendDataParametros" class="mt-5">
                <i class="fa fa-save"></i> Guardar cambios
            </x-button>
        </x-flex>

    </x-card>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('parametrosPlanillaLista', () => ({
            tableData: @json($parametros),
            hot: null,
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            configuraciones: @js($configuraciones),
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
                        data: 'configuracion_codigo',
                        type: 'dropdown',
                        source: this.configuraciones,
                        title: 'CONFIGURACIÓN',
                        className: 'htCenter'
                    },
                    {
                        data: 'valor',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.0000',
                            culture: 'en-US'
                        },
                        title: 'VALOR<br/>BASE',
                        className: 'htRight'
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
            sendDataParametros() {
                let allData = [];
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const rowData = this.hot.getSourceDataAtRow(row);
                    allData.push(rowData);
                }
                const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                    null && cell !== ''));
                $wire.guardarParametros(filteredData);
            },
        }));
    </script>
@endscript
