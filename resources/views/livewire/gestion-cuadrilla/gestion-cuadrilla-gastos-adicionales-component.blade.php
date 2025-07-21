<div>
    <x-modal wire:model.live="mostrarFormularioGastosAdicionales" maxWidth="full">
        <div x-data="gestion_cuadrilla_gastos_adicionales">
            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Administración de costos adicionales durante la semana
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400 min-h-[200px]" wire:ignore>

                    <div x-ref="tableContainerGastosAdicionales" class="mt-5"></div>

                </div>
            </div>

            <div class="flex flex-row justify-end px-6 py-4 bg-whiten dark:bg-boxdarkbase text-end">
                <x-flex>
                    <x-secondary-button wire:click="$set('mostrarFormularioGastosAdicionales', false)"
                        wire:loading.attr="disabled">
                        Cerrar
                    </x-secondary-button>
                    <x-button @click="guardarGastosAdicionales">
                        <i class="fa fa-save"></i> Guardar cambios
                    </x-button>
                </x-flex>
            </div>
        </div>
    </x-modal>
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('gestion_cuadrilla_gastos_adicionales', () => ({

        hotCostosAdicionales: null,
        gastosAdicionales: [],
        grupos: @json($grupos),

        init() {
            Livewire.on('cargarGastosAdicionales', (data) => {
                console.log(data[0]);
                this.gastosAdicionales = data[0];
                this.$nextTick(() => this.initTableGastosAdicionales());
            });
        },
        initTableGastosAdicionales() {

            if (this.hotCostosAdicionales) {
                this.hotCostosAdicionales.destroy();
            }

            const containerGastoAdicional = this.$refs.tableContainerGastosAdicionales;
            console.log(containerGastoAdicional);
            this.hotCostosAdicionales = new Handsontable(containerGastoAdicional, {
                data: this.gastosAdicionales,
                themeName: 'ht-theme-main',
                colHeaders: true,
                rowHeaders: true,
                columns: [{
                    data: 'grupo',
                    title: 'Grupo',
                    type: 'dropdown',
                    source: this.grupos
                }, {
                    data: 'descripcion',
                    title: 'Descripción',
                    type: 'text',
                }, {
                    data: 'fecha',
                    title: 'Fecha',
                    type: 'date',
                }, {
                    data: 'monto',
                    title: 'Monto',
                    type: 'numeric',
                    numericFormat: { pattern: '0,0.00' },
                }],
                width: '100%',
                height: 'auto',
                stretchH: 'all',
                minSpareRows: 1,
                licenseKey: 'non-commercial-and-evaluation',
            });

            this.hotCostosAdicionales.render();
        },
        guardarGastosAdicionales() {
            let allData = [];

            // Recorre todas las filas de la tabla y obtiene los datos completos
            for (let row = 0; row < this.hotCostosAdicionales.countRows(); row++) {
                const rowData = this.hotCostosAdicionales.getSourceDataAtRow(row);
                allData.push(rowData);
            }

            // Filtra las filas vacías
            const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                null && cell !== ''));

            $wire.storeTableDataGuardarDatosAdicionales(filteredData);
        }
    }));
</script>
@endscript