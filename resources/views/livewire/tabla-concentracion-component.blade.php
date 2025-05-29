<div>
    <x-flex class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Tabla de concentración
        </x-h3>
    </x-flex>
    <x-card>
        <x-spacing>
            <x-h3>
                CONCENTRACIÓN DE NUTRIENTES EN FERTILIZANTES
            </x-h3>
            <x-label>
                Gestiona las concentraciones de nutrientes en cada producto
            </x-label>
            <div x-data="tabla_concentraciones" wire:ignore>
                <div x-ref="tableContainer" class="mt-5"></div>

                <div class="text-right mt-5">
                    <x-button @click="sendDataAvanceProductividadLista">
                        <i class="fa fa-save"></i> Guardar Información
                    </x-button>
                </div>
            </div>
        </x-spacing>
    </x-card>
    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('tabla_concentraciones', () => ({
            listeners: [],
            tableData: @json($tableData),
            hot: null,
            listaNutrientes: @json($listaNutrientes),
            listaFertilizantes: @json($listaFertilizantes),
            init() {
                this.initTable();
            },
            initTable() {
                if (this.hot != null) {
                    this.hot.destroy();
                    this.hot = null;
                }

                const listaNutrientes = this.listaNutrientes;
                const columns = this.generateColumns(listaNutrientes);

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    colHeaders: true,
                    rowHeaders: true,
                    columns: columns,
                    width: '100%',
                    height: '300',
                    manualColumnResize: false,
                    manualRowResize: true,
                    minSpareRows: 1,
                    stretchH: 'all',
                    autoColumnSize: true,
                    autoRowSize: true,
                    fixedColumnsLeft: 4,
                    licenseKey: 'non-commercial-and-evaluation',
                });

                this.hot = hot;
                this.hot.render();
            },
            generateColumns(nutrientes) {
                const columns = [];

                // Primera columna: Producto (editable)
                columns.push({
                    data: 'producto',
                    type: 'dropdown',
                    source: this.listaFertilizantes,
                    strict: true,
                    allowInvalid: false,
                    title: 'Producto',
                });

                // Luego una columna por cada nutriente
                nutrientes.forEach(nutriente => {
                    columns.push({
                        data: nutriente,
                        type: 'numeric',
                        title: nutriente,
                        className: '!text-center',
                        numericFormat: {
                            pattern: '0.00',
                            culture: 'en-US'
                        }
                    });
                });

                return columns;
            },
            sendDataAvanceProductividadLista() {
                let allData = [];

                // Recorre todas las filas de la tabla y obtiene los datos completos
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const rowData = this.hot.getSourceDataAtRow(row);
                    allData.push(rowData);
                }

                // Filtra las filas vacías
                const filteredData = allData.filter(row =>
                    row && Object.values(row).some(cell => cell !== null && cell !== '')
                );

                // Validación: si hay valores en columnas de nutrientes y producto está vacío
                for (let i = 0; i < filteredData.length; i++) {
                    const row = filteredData[i];
                    const {
                        producto,
                        ...nutrientes
                    } = row;

                    const tieneValores = Object.values(nutrientes).some(val => val !== null && val !== '');
                    const productoVacio = !producto || producto.trim() === '';

                    if (tieneValores && productoVacio) {
                        alert(`La fila ${i + 1} tiene nutrientes pero no tiene producto seleccionado.`);
                        return; // Detiene el envío
                    }
                }

                const data = {
                    datos: filteredData
                };

                $wire.dispatchSelf('guardarInformacionTablaConcentracion', data);
            }

        }));
    </script>
@endscript
