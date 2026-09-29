<div x-data="asignacion_costos_cuadrilla">
    <x-dialog-modal wire:model.live="mostrarFormularioAignacionCostos" maxWidth="full">
        <x-slot name="title">
            <x-flex>
                <x-title>Asignación de costos</x-title>
                <x-button @click="distribuirCostoSugerido">
                    Distribuir costo sugerido
                </x-button>
            </x-flex>
        </x-slot>
        <x-slot name="content">
            <div wire:ignore>
                <div id="handsontable-asignacion-costos"></div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-flex class="justify-between w-full">
                <x-button variant="success" href="{{ route('cuadrilla.grupos') }}">
                    Administrar grupos <i class="fa fa-link"></i>
                </x-button>
                <x-flex>
                    <x-button variant="secondary" wire:click="$set('mostrarFormularioAignacionCostos', false)"
                        wire:loading.attr="disabled">
                        Cerrar
                    </x-button>
                    <x-button @click="guardarAsignacionCostos">
                        <i class="fa fa-save"></i> Guardar cambios
                    </x-button>
                </x-flex>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('asignacion_costos_cuadrilla', () => ({
            hot: null,
            costosAsignados: @json($costosAsignados),
            totalDias: @json($totalDias),
            headers: @json($headers),

            init() {

                Livewire.on('actualizarTablaAsignacionCosto', (data) => {

                    console.log(data[0]);
                    this.costosAsignados = data[0];
                    this.totalDias = data[1];
                    this.headers = data[2];

                });
                this.$watch('$wire.mostrarFormularioAignacionCostos', (visible) => {
                    if (visible) {
                        // Esperar que termine la transición del modal (300ms según x-transition)
                        setTimeout(() => this.initTable(), 350);
                    } else {
                        if (this.hot) {
                            this.hot.destroy();
                            this.hot = null;
                        }
                    }
                });
            },

            initTable() {
                if (this.hot) {
                    this.hot.destroy();
                }

                const container = document.getElementById('handsontable-asignacion-costos');

                if (!container) {
                    console.error("No se encontró el contenedor tableAsignacionCostos");
                    return;
                }

                this.hot = new Handsontable(container, {
                    data: this.costosAsignados,
                    colHeaders: true,
                    rowHeaders: true,
                    columns: this.generarColumnasDinamicas(),
                    width: '100%',
                    height: 'auto',
                    stretchH: 'all',
                    autoColumnSize: true,
                    fixedColumnsLeft: 2,
                    cells: function(row, col) {
                        const cellProperties = {};
                        const rowData = this.instance.getSourceDataAtRow(row);

                        // 👉 Traduce columna a propiedad
                        const prop = this.instance.colToProp(col);

                        // 👉 1. Aplica color del grupo SOLO a columna 0
                        if (col === 0 && rowData?.color) {
                            cellProperties.renderer = function(instance, td, row, col, prop, value,
                                cellProperties) {
                                Handsontable.renderers.TextRenderer.apply(this, arguments);
                                td.style.backgroundColor = rowData.color;
                                td.style.color = '#000'; // Contraste negro
                            };
                        }

                        // 👉 2. Resaltar celdas de días diferentes al costo sugerido
                        if (prop && prop.startsWith('dia_')) {
                            const base = parseFloat(rowData.costo_dia_sugerido);
                            const actual = parseFloat(rowData[prop]);

                            if (!isNaN(base) && !isNaN(actual) && base !== actual) {
                                cellProperties.className = (cellProperties.className ?? '') +
                                    ' !bg-yellow-200 !text-center';
                            }
                        }

                        return cellProperties;
                    },

                    licenseKey: 'non-commercial-and-evaluation',
                });
            },

            generarColumnasDinamicas() {
                const cols = [{
                        data: 'codigo_grupo',
                        title: 'Código',
                        type: 'text',
                        readOnly: true
                    },
                    {
                        data: 'nombre',
                        title: 'Nombre',
                        type: 'text',
                        readOnly: true,
                        className: '!bg-gray-100'
                    },
                    {
                        data: 'costo_dia_sugerido',
                        title: 'Costo<br/>Sugerido',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0,0.00'
                        },
                        className: '!text-center'
                    },
                ];

                for (let i = 1; i <= this.totalDias; i++) {
                    cols.push({
                        data: `dia_${i}`,
                        title: this.headers[i - 1] ?? '-',
                        type: 'numeric',
                        numericFormat: {
                            pattern: '0,0.00'
                        },
                        className: '!text-center'
                    });
                }

                return cols;
            },
            distribuirCostoSugerido() {
                const data = this.hot.getSourceData();

                for (let i = 0; i < data.length; i++) {
                    const fila = data[i];
                    const sugerido = parseFloat(fila.costo_dia_sugerido) || 0;

                    for (let d = 1; d <= this.totalDias; d++) {
                        fila[`dia_${d}`] = sugerido;
                    }
                }

                this.hot.loadData(data);
            },

            guardarAsignacionCostos() {
                let allData = [];
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const rowData = this.hot.getSourceDataAtRow(row);
                    allData.push(rowData);
                }

                const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                    null && cell !== ''));
                $wire.guardarAsignacionCostos(filteredData);
            }
        }));
    </script>
@endscript
