<div x-data="actividades_diarias_cuadrilleros">
    <x-loading wire:loading />

    <x-flex class="w-full justify-between">
        <x-flex class="my-3">
            <x-h3>
                Registro Diario Cuadrilla
            </x-h3>
            <x-button @click="$wire.dispatch('registrarReporteDiarioCuadrilla',{fecha:'{{ $fecha }}'})">
                <i class="fa fa-plus"></i> Registrar reporte
            </x-button>
        </x-flex>
        <x-button-a href="{{ route('cuadrilleros.gestion') }}">
            <i class="fa fa-arrow-left"></i> Volver a gestión de cuadrilleros
        </x-button-a>
    </x-flex>

    <div class="flex items-center justify-between mb-4">
        <!-- Botón para fecha anterior -->
        <x-button wire:click="fechaAnterior">
            <i class="fa fa-chevron-left"></i> Fecha Anterior
        </x-button>

        <!-- Input para seleccionar la fecha -->
        <x-input type="date" wire:model.live="fecha" class="text-center mx-2 !w-auto" />

        <!-- Botón para fecha posterior -->
        <x-button wire:click="fechaPosterior">
            Fecha Posterior <i class="fa fa-chevron-right"></i>
        </x-button>
    </div>

    <x-card2>
        <div wire:ignore>
            <x-h3>Detalle de trabajadores</x-h3>
            <x-flex class="lg:justify-end gap-3 w-full my-3">
                <x-button @click="agregarGrupo" class="w-full lg:w-auto"><i class="fa fa-plus"></i></x-button>
                <x-danger-button @click="quitarGrupo" class="w-full lg:w-auto"><i
                        class="fa fa-minus"></i></x-danger-button>
            </x-flex>
            <div x-ref="tableReporteContainer"></div>

        </div>
        <x-flex class="w-full justify-end mt-4">
            <x-button @click="guardarReporteActividadDiaria">
                <i class="fa fa-sync"></i> Actualizar manualmente
            </x-button>
        </x-flex>
    </x-card2>

    <livewire:gestion-cuadrilla.gestion-cuadrilla-reporte-diario-form-component />
</div>
@script
<script>
    Alpine.data('actividades_diarias_cuadrilleros', () => ({
        listeners: [],
        ingresos: [],
        seleccionados: [],
        totalVenta: '0.00',
        fechaVenta: null,
        selectedRows: [],
        trabajadores: @json($trabajadores),
        totalColumnas: @json($totalColumnas),
        labores: @json($labores),
        campos: @json($campos),
        hot: null,
        hotFuente: null,
        init() {
            if (!this.horarios || this.horarios.length === 0) {
                this.horarios = [{ inicio: '', fin: '', horas: 0 }];
            }
            this.$nextTick(() => {
                this.initTable();
            });
            this.listeners.push(

                Livewire.on('actualizarTablaCuadrilleros', (data) => {

                    this.trabajadores = data[0];
                    this.totalColumnas = data[1];
                    this.$nextTick(() => {
                        this.initTable();
                    });
                })
            );
        },
        initTable() {

            if (this.hot != null) {
                this.hot.destroy();
                this.hot = null;
            }

            const tareas = this.tareas;

            const container = this.$refs.tableReporteContainer;
            const hot = new Handsontable(container, {
                data: this.trabajadores,
                colHeaders: true,
                rowHeaders: true,
                columns: this.generarColumnasDinamicas(),
                width: '100%',
                height: 'auto',
                manualColumnResize: false,
                manualRowResize: true,
                stretchH: 'all',
                autoColumnSize: true,
                fixedColumnsLeft: 2,
                cells: function (row, col) {
                    const cellProperties = {};
                    const rowData = this.instance.getSourceDataAtRow(row);

                    if (rowData?.cosecha_encontrada === true) {
                        cellProperties.className = '!bg-lime-200';
                    }
                    if (rowData?.fusionada === true) {
                        cellProperties.className = '!bg-blue-200';
                    }

                    return cellProperties;
                },

                licenseKey: 'non-commercial-and-evaluation',
            });

            this.hot = hot;
        },
        agregarGrupo() {
            this.totalColumnas++;
            const columns = this.generarColumnasDinamicas();
            this.hot.updateSettings({
                columns: columns
            });
        },
        quitarGrupo() {
            this.totalColumnas--;
            const columns = this.generarColumnasDinamicas();
            this.hot.updateSettings({
                columns: columns
            });
        },
        generarColumnasDinamicas() {
            const cols = [
                {
                    data: 'cuadrillero_nombres',
                    type: 'text',
                    readOnly: true,
                    className: '!bg-gray-200',
                    title: 'Trabajador'
                }
            ];

            // Agregar columnas dinámicas para cada actividad
            for (let i = 1; i <= this.totalColumnas; i++) {
                const bgClass = (i % 2 === 0) ? '' : '!bg-blue-300';

                cols.push(
                    {
                        data: `campo_${i}`,
                        type: 'dropdown',
                        source: this.campos,
                        className: `text-center ${bgClass}`,
                        title: `Camp. ${i}`
                    },
                    {
                        data: `labor_${i}`,
                        type: 'dropdown',
                        source: this.labores,
                        className: `text-center ${bgClass}`,
                        title: `Lab. ${i}`
                    },
                    {
                        data: `hora_inicio_${i}`,
                        type: 'text',
                        className: `text-center ${bgClass}`,
                        title: `Hora<br/>Ini. ${i}`
                    },
                    {
                        data: `hora_fin_${i}`,
                        type: 'text',
                        className: `text-center ${bgClass}`,
                        title: `Hora<br/>Fin ${i}`
                    }
                );
            }


            return cols;
        },
        guardarReporteActividadDiaria() {
            let allData = [];

            // Recorre todas las filas de la tabla y obtiene los datos completos
            for (let row = 0; row < this.hot.countRows(); row++) {
                const rowData = this.hot.getSourceDataAtRow(row);

                // Crear una copia limpia con solo columnas activas
                let cleanedRow = {
                    cuadrillero_id: rowData.cuadrillero_id ?? null,
                    cuadrillero_nombres: rowData.cuadrillero_nombres ?? '',
                    cuadrillero_dni: rowData.cuadrillero_dni ?? null,
                    asistencia: rowData.asistencia ?? true,
                };

                for (let i = 1; i <= this.totalColumnas; i++) {
                    cleanedRow[`campo_${i}`] = rowData[`campo_${i}`] ?? null;
                    cleanedRow[`labor_${i}`] = rowData[`labor_${i}`] ?? null;
                    cleanedRow[`hora_inicio_${i}`] = rowData[`hora_inicio_${i}`] ?? null;
                    cleanedRow[`hora_fin_${i}`] = rowData[`hora_fin_${i}`] ?? null;
                }

                allData.push(cleanedRow);
            }

            // Filtrar filas vacías
            const filteredData = allData.filter(row => {
                const tramos = Array.from({ length: this.totalColumnas }).some((_, i) => {
                    return row[`hora_inicio_${i + 1}`] || row[`hora_fin_${i + 1}`] || row[`labor_${i + 1}`];
                });
                return row.cuadrillero_nombres !== '' || tramos;
            });

            const data = { datos: filteredData };

            $wire.dispatchSelf('storeTableDataGuardarActividadDiaria', data);
        }

    }));
</script>
@endscript