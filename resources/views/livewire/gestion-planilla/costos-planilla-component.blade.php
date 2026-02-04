<div x-data="costosPlanilla">
    @include('comun.selector-mes')
    <x-card class="mt-4">
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>
    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('costosPlanilla', () => ({
            tableData: @json($listaPlanilla),
            hot: null,
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            init() {
                this.initTable();

                Livewire.on('recargarCostoPlanilla', ({
                    data
                }) => {
                    this.tableData = data;
                    this.hot.destroy();
                    this.initTable();
                    this.hot.loadData(this.tableData);
                });

                $watch('darkMode', value => {

                    this.isDark = value;
                   // const columns = this.generateColumns(this.tareas);
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                    //    columns: columns
                    });
                });
            },
            initTable() {
                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    rowHeaders: true,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                    columns: [{
                            data: 'documento',
                            className: '!text-center',
                            readOnly: true,
                            title: 'DNI'
                        },
                        {
                            data: 'nombres',
                            title: 'EMPLEADO',
                            readOnly: true,
                            width: 100
                        },
                        {
                            data: 'sueldo_blanco',
                            type: 'numeric',
                            className: '!text-center',
                            readOnly: true,
                            title: 'SUELDO BLANCO',
                        },
                        {
                            data: 'sueldo_blanco',
                            type: 'numeric',
                            className: '!text-center',
                            readOnly: true,
                            title: 'SUELDO NEGRO',
                        },
                        {
                            data: 'sueldo_pagado',
                            type: 'numeric',
                            className: '!text-center',
                            readOnly: true,
                            title: 'SUELDO PAGADO<br/>BLANCO',
                        },
                        {
                            data: 'sueldo_pagado_negro',
                            type: 'numeric',
                            className: '!text-center',
                            readOnly: true,
                            title: 'SUELDO PAGADO<br/>NEGRO',
                        }
                    ],
                    stretchH: 'all',
                    autoColumnSize: false,
                    licenseKey: 'non-commercial-and-evaluation',

                });

                this.hot = hot;
                this.hot.render();
            }
        }));
    </script>
@endscript
