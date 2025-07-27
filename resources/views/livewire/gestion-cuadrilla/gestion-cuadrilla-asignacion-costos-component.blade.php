<div>
    <x-modal wire:model.live="mostrarFormularioAignacionCostos" maxWidth="full">
        <div x-data="asignacion_costos_2025">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    <x-flex>
                        <x-h3>
                            Asignación de costos
                        </x-h3>
                        <x-button @click="distribuirCostoSugerido">
                            Distribuir costo sugerido
                        </x-button>
                    </x-flex>
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div wire:ignore>
                        <div x-ref="tableAsignacionCostos"></div>
                    </div>
                </div>

            </div>
            <div class="flex flex-row justify-between px-6 py-4 bg-whiten dark:bg-boxdarkbase text-end">
                <x-button-a href="{{ route('cuadrilla.grupos') }}">
                    Administrar grupos <i class="fa fa-link"></i>
                </x-button-a>
                <x-flex>
                    <x-secondary-button wire:click="$set('mostrarFormularioAignacionCostos', false)"
                        wire:loading.attr="disabled">
                        Cerrar
                    </x-secondary-button>
                    <x-button @click="guardarAsignacionCostos">
                        <i class="fa fa-save"></i> Guardar cambios
                    </x-button>
                </x-flex>
            </div>
    </x-modal>
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('asignacion_costos_2025', () => ({
        hot: null,
        costosAsignados: @json($costosAsignados),
        totalDias: @json($totalDias),
        headers: @json($headers),

        init() {
            this.$nextTick(() => {
                this.initTable();
            });

            Livewire.on('actualizarTablaAsignacionCosto', (data) => {
                console.log(data[0]);
                this.costosAsignados = data[0];
                this.totalDias = data[1];
                this.headers = data[2];
                this.$nextTick(() => this.initTable());
            });
        },

        initTable() {
            if (this.hot) {
                this.hot.destroy();
            }

            const container = this.$refs.tableAsignacionCostos;
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
                cells: function (row, col) {
                    const cellProperties = {};
                    const rowData = this.instance.getSourceDataAtRow(row);

                    // 👉 Traduce columna a propiedad
                    const prop = this.instance.colToProp(col);

                    // 👉 1. Aplica color del grupo SOLO a columna 0
                    if (col === 0 && rowData?.color) {
                        cellProperties.renderer = function (instance, td, row, col, prop, value, cellProperties) {
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
                            cellProperties.className = (cellProperties.className ?? '') + ' !bg-yellow-200 !text-center';
                        }
                    }

                    return cellProperties;
                },

                licenseKey: 'non-commercial-and-evaluation',
            });
        },

        generarColumnasDinamicas() {
            const cols = [
                { data: 'codigo_grupo', title: 'Código', type: 'text', readOnly: true },
                { data: 'nombre', title: 'Nombre', type: 'text', readOnly: true, className: '!bg-gray-100' },
                { data: 'costo_dia_sugerido', title: 'Costo<br/>Sugerido', type: 'numeric', numericFormat: { pattern: '0,0.00' }, className: '!text-center' },
            ];

            for (let i = 1; i <= this.totalDias; i++) {
                cols.push({
                    data: `dia_${i}`,
                    title: this.headers[i - 1] ?? '-',
                    type: 'numeric',
                    numericFormat: { pattern: '0,0.00' },
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

            const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !== null && cell !== ''));
            $wire.guardarAsignacionCostos(filteredData);
        }
    }));

</script>
@endscript