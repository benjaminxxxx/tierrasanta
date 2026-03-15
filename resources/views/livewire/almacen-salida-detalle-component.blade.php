<div x-data="gestionSalidaAlmacen">
    <x-card>
        <div wire:ignore>
            <div x-ref="tableContainer"></div>
        </div>
    </x-card>


    <x-dialog-modal wire:model.live="mostrarGenerarItem">
        <x-slot name="title">
            Escribe desde que numero iniciarán los correlativos
        </x-slot>

        <x-slot name="content">
            <x-label>Inicio de numeracion</x-label>
            <x-input type="number" wire:keydown.enter="generarItemCodigo" wire:model="inicioItem" />
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-5">
                <x-secondary-button wire:click="cerrarMostrarGenerarItem" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="generarItemCodigo" wire:loading.attr="disabled">
                    Generar codigo de items
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('gestionSalidaAlmacen', () => ({
            filasModificadas: @entangle('filasModificadas'),
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            tableDataSalidas:@js($registros),
            init() {
                this.initTable(this.tableDataSalidas);
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

                       
                    }

                });

                this.hot = hot;
                this.hot.render();
            },
            getColumns() {
                return [

                    {
                        data: 'item',
                        type: 'numeric',
                        title: 'ITEM',
                        readOnly: true,
                    },

                    {
                        data: 'fecha_reporte',
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
                        data: 'nombre_producto',
                        type: 'text',
                        title: 'PRODUCTO'
                    },

                    {
                        data: 'unidad_medida',
                        type: 'text',
                        title: 'UND. MEDIDA',
                        readOnly: true,
                        className: '!bg-muted !text-center'
                    },

                    {
                        data: 'cantidad',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0.00'
                        },
                        title: 'CANTIDAD'
                    },

                    {
                        data: 'categoria',
                        type: 'numeric',
                        readOnly: true,
                        title: 'CATEGORIA',
                        className: '!bg-muted'
                    },

                    {
                        data: 'costo_por_kg',
                        type: 'text',
                        title: 'COSTO X UNIDAD'
                    },

                    {
                        data: 'total_costo',
                        type: 'dropdown',
                        source: ['carton', 'tubo', 'malla'],
                        title: 'TOTAL COSTO',
                        className: 'uppercase',
                        width: 55
                    }

                ];
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

