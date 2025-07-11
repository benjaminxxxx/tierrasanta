<div x-data="reporte_semanal_cuadrilleros">
    <x-loading wire:loading />

    <x-flex class="w-full justify-between">
        <x-flex class="my-3">
            <x-h3>
                Registro Semanal Cuadrilla
            </x-h3>
            <x-button wire:click="asignarCostos">
                Asignar costos
            </x-button>
        </x-flex>
        <x-button-a href="{{ route('cuadrilleros.gestion') }}">
            <i class="fa fa-arrow-left"></i> Volver a gestión de cuadrilleros
        </x-button-a>
    </x-flex>


    <div class="flex justify-between items-center">
        <x-button wire:click="semanaAnterior">
            <i class="fa fa-chevron-left"></i> Semana Anterior
        </x-button>

        <div class="text-center">
            <form wire:submit.prevent="seleccionarSemana">
                <div class="flex items-center space-x-2">
                    <x-select wire:model.live="anio">
                        <option value="">Año</option>
                        @for ($y = now()->year - 5; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </x-select>

                    <x-select wire:model.live="mes">
                        <option value="">Mes</option>
                        @foreach ($meses as $num => $nombre)
                            <option value="{{ $num }}">{{ $nombre }}</option>
                        @endforeach
                    </x-select>

                    <x-select wire:model.live="semanaNumero">
                        <option value="">Semana</option>
                        @for ($s = 1; $s <= 5; $s++)
                            <option value="{{ $s }}">Semana {{ $s }}</option>
                        @endfor
                    </x-select>
                </div>
            </form>

        </div>

        <x-button wire:click="siguienteSemana">
            Semana Posterior <i class="fa fa-chevron-right"></i>
        </x-button>
    </div>


    <x-card2>
        <x-h3 class="text-center w-full">
            <strong>Semana:</strong> {{ $semana->inicio }} - {{ $semana->fin }}
        </x-h3>
    </x-card2>

    <div wire:ignore>
        <div x-ref="tableContainerSemana" class="mt-5"></div>
    </div>

    <x-flex class="w-full justify-end mt-4">
        <x-button @click="registrarHoras">
            <i class="fa fa-save"></i> Actualizar horas
        </x-button>
    </x-flex>
    <livewire:gestion-cuadrilla.gestion-cuadrilla-asignacion-costos-component />

</div>
@script
<script>
    Alpine.data('reporte_semanal_cuadrilleros', () => ({
        listeners: [],
        reporteSemanal: @json($reporteSemanal),
        headers: @json($headers),
        totalDias: @json($totalDias),
        gruposDisponibles: @json($gruposDisponibles),
        colorPorGrupo: @json($colorPorGrupo),
        cuadrilleros: @json($cuadrilleros),
        hot: null,
        init() {
            this.$nextTick(() => {
                this.initTable();
            });

            Livewire.on('actualizarTablaReporteSemanal', (data) => {
                console.log(data[0]);
                this.reporteSemanal = data[0];
                this.totalDias = data[1];
                this.headers = data[2];
                this.$nextTick(() => this.initTable());
            });
        },
        initTable() {

            if (this.hot) {
                this.hot.destroy();
            }

            const container = this.$refs.tableContainerSemana;
            this.hot = new Handsontable(container, {
                data: this.reporteSemanal,
                themeName: 'ht-theme-main-dark-auto',
                colHeaders: this.headers,
                rowHeaders: true,
                columns: this.generarColumnasDinamicas(),
                width: '100%',
                height: 'auto',
                stretchH: 'all',
                filters: true,
                // enable the column menu
                dropdownMenu: true,
                autoColumnSize: true,
                minSpareRows: 1,
                fixedColumnsLeft: 2,
                afterChange: (changes, source) => {
                    if (source === 'edit') {
                        changes.forEach(([row, prop, oldVal, newVal]) => {
                            if (prop === 'codigo_grupo') {
                                // Asigna el nuevo color
                                const color = this.colorPorGrupo[newVal] || '#ffffff';
                                this.hot.setDataAtRowProp(row, 'color', color);
                            }
                        });
                    }
                },
                licenseKey: 'non-commercial-and-evaluation',
                plugins: ['Filters', 'DropdownMenu'],
            });
        },
        generarColumnasDinamicas() {
            const cols = [

                {
                    data: 'codigo_grupo',
                    title: 'Grupo',
                    type: 'dropdown',
                    source: this.gruposDisponibles,
                    strict: true,
                    allowInvalid: false,
                    renderer: function (instance, td, row, col, prop, value, cellProperties) {
                        const rowData = instance.getSourceDataAtRow(row);
                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                        td.style.backgroundColor = rowData?.color || '#ffffff';
                        td.style.color = '#000000';
                    }
                }, {
                    data: 'cuadrillero_nombres',
                    title: 'Nombre',
                    type: 'autocomplete',
                    source: this.cuadrilleros
                },
            ];

            // 🟦 Asistencia (día_1, día_2, ...)
            for (let i = 1; i <= this.totalDias; i++) {
                cols.push({
                    data: `dia_${i}`,
                    title: this.headers[i - 1] ?? '-',
                    type: 'numeric',
                    strict: true,
                    allowInvalid: false,
                    className: '!text-center !text-lg',
                    renderer: function (instance, td, row, col, prop, value, cellProperties) {
                        Handsontable.renderers.NumericRenderer.apply(this, arguments);
                        if (value > 0) {
                            td.style.color = '';
                        } else {
                            td.style.color = 'rgba(255,0,0,0.8)';
                        }
                    }

                });
            }


            // 🟨 Costos (jornal_1, jornal_2, ...)
            for (let i = 1; i <= this.totalDias; i++) {
                const mas = this.totalDias;
                cols.push({
                    data: `jornal_${i}`,
                    title: this.headers[mas + i - 1] ?? '-',
                    className: '!text-center !bg-gray-100',
                    readOnly: true
                });
            }

            // 🟩 Bonos (bono_1, bono_2, ...)
            for (let i = 1; i <= this.totalDias; i++) {
                const mas = this.totalDias * 2;
                cols.push({
                    data: `bono_${i}`,
                    title: this.headers[mas + i - 1] ?? '-',
                    type: 'numeric',
                    numericFormat: { pattern: '0,0.00' },
                    className: '!text-center !bg-gray-100',
                    readOnly: true
                });
            }

            // 🔢 Totales
            cols.push(

                {
                    data: 'total_costo',
                    title: 'Total<br/>Costo',
                    type: 'numeric',
                    readOnly: true,
                    className: 'bg-yellow-100 !text-center font-bold'
                }
            );

            return cols;
        },
        registrarHoras() {
            let allData = [];

            // Recorre todas las filas de la tabla y obtiene los datos completos
            for (let row = 0; row < this.hot.countRows(); row++) {
                const rowData = this.hot.getSourceDataAtRow(row);
                allData.push(rowData);
            }

            // Filtra las filas vacías
            const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                null && cell !== ''));

            $wire.storeTableDataGuardarHoras(filteredData);
        }
    }));
</script>
@endscript