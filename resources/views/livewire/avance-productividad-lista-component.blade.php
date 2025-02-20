<div>
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Lista de Avance de Productividad
        </x-slot>

        <x-slot name="content">

            <div x-data="{{ $idTable }}" wire:ignore>
                <div x-ref="tableContainer" class="mt-5 overflow-auto"></div>

                <div class="text-right mt-5">
                    <x-button @click="sendDataAvanceProductividadLista">
                        <i class="fa fa-save"></i> Guardar Información
                    </x-button>
                </div>
            </div>

        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('{{ $idTable }}', () => ({
            listeners: [],
            tableData: [],
            hot: null,
            tareas: [],
            hasUnsavedChanges: false,
            init() {
                //  this.initTable();
                this.listeners.push(
                    Livewire.on('generarData', (data) => {

                        this.tareas = data[0];
                        this.tableData = data[1];

                        this.initTable();
                    })
                );
            },
            initTable() {
                if (this.hot != null) {
                    this.hot.destroy();
                    this.hot = null;
                }

                const tareas = this.tareas;
                const columns = this.generateColumns(tareas);

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    colHeaders: true,
                    rowHeaders: true,
                    columns: columns,
                    width: '100%',
                    height: 'auto',
                    manualColumnResize: false,
                    manualRowResize: true,
                    //minSpareRows: 1,
                    stretchH: 'all',
                    autoColumnSize: true,
                    autoRowSize: true,
                    fixedColumnsLeft: 4,
                    licenseKey: 'non-commercial-and-evaluation',
                    afterChange: (changes, source) => {

                        if (source === 'loadData') {
                            return;
                        }

                        if (source == 'edit' || source == 'CopyPaste.paste' || source ==
                            'timeValidator' || source == 'Autofill.fill') {
                            this.hasUnsavedChanges = true;
                        }
                    }
                });

                this.hot = hot;
                //this.hot.render();

                window.addEventListener('beforeunload', (event) => {
                    if (this.hasUnsavedChanges) {
                        const confirmationMessage =
                            'Tienes cambios no guardados. ¿Estás seguro de que deseas salir?';
                        event.returnValue = confirmationMessage; // Mostrar el mensaje de advertencia
                        return confirmationMessage;
                    }
                });
            },
            generateColumns(tareas) {
                let columns = [{
                        data: 'tipo',
                        type: 'text',
                        title: 'TIPO',
                        className: 'text-center !bg-gray-100',
                        readOnly: true
                    },
                    {
                        data: 'dni',
                        type: 'text',
                        width: 80,
                        title: 'DNI',
                        className: 'text-center !bg-gray-100',
                        readOnly: true
                    },
                    {
                        data: "nombres",
                        type: 'text',
                        width: 270,
                        className: '!bg-gray-100',
                        title: 'APELLIDOS Y NOMBRES',
                        readOnly: true
                    }
                ];

                // Generar dinámicamente las columnas según las tareas
                for (let indice = 1; indice <= tareas; indice++) {
                    columns.push({
                        data: "actividad_" + indice,
                        type: 'numeric',
                        numericFormat: {
                            pattern: '##,##0.00', // Formato de miles con al menos dos decimales
                            culture: 'en-US' // Cultura para usar coma como separador de miles y punto para decimales
                        },
                        className: '!text-center',
                        correctFormat: true,
                        title: `KG. ${indice}`
                    });
                }

                // Agregar columna final de TOTAL
                columns.push({
                    data: 'total_kg',
                    type: 'numeric',
                    numericFormat: {
                        pattern: '##,##0.00', // Formato de miles con al menos dos decimales
                        culture: 'en-US' // Cultura para usar coma como separador de miles y punto para decimales
                    },
                    correctFormat: true,
                    title: 'TOTAL KG',
                    className: '!text-center font-bold text-lg',
                }, {
                    data: 'adicional_kg',
                    type: 'numeric',
                    numericFormat: {
                        pattern: '##,##0.00', // Formato de miles con al menos dos decimales
                        culture: 'en-US' // Cultura para usar coma como separador de miles y punto para decimales
                    },
                    correctFormat: true,
                    title: 'KG ADICIONAL',
                    className: '!text-center font-bold text-lg',
                }, {
                    data: 'bono',
                    type: 'numeric',
                    numericFormat: {
                        pattern: '##,##0.00', // Formato de miles con al menos dos decimales
                        culture: 'en-US' // Cultura para usar coma como separador de miles y punto para decimales
                    },
                    correctFormat: true,
                    title: 'TOTAL BONO',
                    className: '!text-center font-bold text-lg',
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
                const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                    null && cell !== ''));

                const data = {
                    datos: filteredData
                };

                $wire.dispatchSelf('guardarInformacionAvanceProductividadLista', data);
                this.hasUnsavedChanges = false;
            }
        }));
    </script>
@endscript
