<div x-data="planilla_blanco">
    <x-loading wire:loading />
    @if ($informacionBlanco)
        <x-card2 class="mt-5">
            <x-h3>MES DE {{ mb_strtoupper($mesTitulo) }} - {{ $anio }}</x-h3>
            <div class="mt-5 flex flex-wrap gap-4">
                <x-group-field>
                    <x-label>Días Laborables</x-label>
                    <x-input wire:model="diasLaborables" @input="calcularTotalHoras(event.target.value)" />
                </x-group-field>

                <x-group-field>
                    <x-label>Total Horas</x-label>
                    <x-input wire:model="totalHoras" />
                </x-group-field>

                <x-group-field>
                    <x-label>Factor Rem. Básica</x-label>
                    <x-input wire:model="factorRemuneracionBasica" />
                </x-group-field>

                <x-group-field>
                    <x-label>Asignación Familiar</x-label>
                    <x-input wire:model="asignacionFamiliar" />
                </x-group-field>

                <x-group-field>
                    <x-label>CTS (%)</x-label>
                    <x-input wire:model="ctsPorcentaje" />
                </x-group-field>

                <x-group-field>
                    <x-label>Gratificaciones</x-label>
                    <x-input wire:model="gratificaciones" />
                </x-group-field>

                <x-group-field>
                    <x-label>Essalud Gratificaciones</x-label>
                    <x-input wire:model="essaludGratificaciones" />
                </x-group-field>

                <x-group-field>
                    <x-label>RMV</x-label>
                    <x-input wire:model="rmv" />
                </x-group-field>

                <x-group-field>
                    <x-label>Beta 30%</x-label>
                    <x-input wire:model="beta30" />
                </x-group-field>

                <x-group-field>
                    <x-label>Essalud (%)</x-label>
                    <x-input wire:model="essalud" />
                </x-group-field>

                <x-group-field>
                    <x-label>Vida Ley</x-label>
                    <x-input wire:model="vidaLey" />
                </x-group-field>

                <x-group-field>
                    <x-label>Vida Ley (%)</x-label>
                    <x-input wire:model="vidaLeyPorcentaje" />
                </x-group-field>

                <x-group-field>
                    <x-label>Pensión SCTR</x-label>
                    <x-input wire:model="pensionSctr" />
                </x-group-field>

                <x-group-field>
                    <x-label>Pensión SCTR (%)</x-label>
                    <x-input wire:model="pensionSctrPorcentaje" />
                </x-group-field>

                <x-group-field>
                    <x-label>Essalud EPS</x-label>
                    <x-input wire:model="essaludEps" />
                </x-group-field>

                <x-group-field>
                    <x-label>Porcentaje Constante</x-label>
                    <x-input wire:model="porcentajeConstante" />
                </x-group-field>

                <x-group-field>
                    <x-label>Rem. Básica Essalud</x-label>
                    <x-input wire:model="remBasicaEssalud" />
                </x-group-field>

                <x-group-field class="mt-5">
                    <x-button wire:click="guardarPlanillaDatos(1)">
                        <i class="fa fa-refresh"></i> Guardar cambios
                    </x-button>
                </x-group-field>

                <x-group-field class="mt-5">
                    <x-button wire:click="guardarPlanillaDatos(2)">
                        <i class="fa fa-refresh"></i> Recuperar datos desde configuración
                    </x-button>
                </x-group-field>

                <x-group-field class="mt-5">
                    <x-button wire:click="guardarPlanillaDatos(3)">
                        <i class="fa fa-refresh"></i> Guardar + Actualizar configuración
                    </x-button>
                </x-group-field>

            </div>

        </x-card2>
        <x-card class="mt-5">
            <x-spacing>
                <div class="mt-5 md:flex items-end gap-4">


                    @if ($informacionBlanco)
                        <div class="mt-3">
                            <x-button wire:click="generarPlanilla">
                                <i class="fa fa-list"></i>
                                @if ($informacionBlancoDetalle && $informacionBlancoDetalle->count() > 0)
                                    Regenerar Planilla
                                @else
                                    Generar Planilla
                                @endif
                            </x-button>
                        </div>
                        @if ($informacionBlanco->excel)
                            <div class="mt-3">
                                <x-button-a href="{{ Storage::disk('public')->url($informacionBlanco->excel) }}">
                                    <i class="fa fa-file-excel"></i> Descargar Planilla Generada
                                </x-button-a>
                            </div>
                        @endif
                    @endif
                    <div class="mt-3">
                        <x-button-a href="{{ route('planilla.asistencia', ['mes' => $mes, 'anio' => $anio]) }}"
                            class="whitespace-nowrap">
                            <i class="fa fa-link"></i> Revisar Asistencia
                        </x-button-a>
                    </div>
                </div>
                <div class="mt-5" wire:ignore>
                    <div x-ref="tableContainer" class="overflow-auto"></div>
                </div>
                <div class="mt-5 flex justify-end">
                    <x-button @click="sendData">
                        <i class="fa fa-save"></i> Guardar Bonificaciones
                    </x-button>
                </div>
            </x-spacing>
        </x-card>
    @endif
</div>


@script
<script>
    Alpine.data('planilla_blanco', () => ({
        listeners: [],
        tableData: @json($informacionBlancoDetalle),
        totalHoras: @entangle('totalHoras'),
        hot: null,
        init() {

            this.initTable();
            this.listeners.push(
                Livewire.on('renderTable', (data) => {
                    /*
                    console.log(data);
                    let empleados = data[0];
                    this.tableData = empleados;
                    this.hot.loadData(this.tableData);*/
                    location.href = location.href;
                })
            );
        },
        initTable() {
            const tareas = this.tareas;
            const columns = this.generateColumns(tareas);

            const container = this.$refs.tableContainer;
            const hot = new Handsontable(container, {
                data: this.tableData,
                themeName: 'ht-theme-main',
                colHeaders: true,
                rowHeaders: true,
                columns: columns,
                width: '100%',
                height: 'auto',
                manualColumnResize: false,
                manualRowResize: true,
                hiddenColumns: {
                    columns: [0, 9, 10, 11, 12, 16, 18],
                    indicators: true
                },
                stretchH: 'all',
                autoColumnSize: true,
                fixedColumnsLeft: 2,
                licenseKey: 'non-commercial-and-evaluation',
                afterRender: function () {

                    const htCoreTable = document.querySelector('.htCore');
                    let tableHeight = htCoreTable.offsetHeight;
                    if (tableHeight > 0) {
                        tableHeight = tableHeight + 70;
                        container.style.minHeight = `${tableHeight}px`;
                    }
                },

            });

            this.hot = hot;
            this.hot.render();

            //this.calcularTotales();
        },
        calcularTotalHoras(diasLaborables){
            if(diasLaborables>0){
                this.totalHoras = diasLaborables * 8;
            }
        },
        isValidTimeFormat(time) {
            const timePattern = /^([01]\d|2[0-3]):([0-5]\d)$/;
            return timePattern.test(time);
        },
        generateColumns(tareas) {
            let columns = [{
                data: 'documento',
                type: 'text',
                title: 'DNI',
                className: '!text-center',
                readOnly: true
            },
            {
                data: "nombres",
                type: 'text',
                title: 'APELLIDOS Y NOMBRES',
                renderer: function (instance, td, row, col, prop, value, cellProperties) {

                    const color = instance.getDataAtRowProp(row, 'empleado_grupo_color');

                    td.style.background = color;
                    td.innerHTML = value;

                    return td;
                },
                readOnly: true
            }, //empleado_grupo_color
            {
                data: 'spp_snp',
                type: 'text',
                className: '!text-center',
                title: 'SPP/<br/>SNP',
                renderer: function (instance, td, row, col, prop, value, cellProperties) {
                    // Cambiar el color del texto basado en el valor de la celda
                    const descuentoColores = @json($descuentoColores);

                    let color = descuentoColores[value] ?? '#000000';
                    td.style.color = color;
                    td.style.fontWeight = 'bold';
                    td.innerHTML = value;
                    td.className = "!text-center";

                    return td;
                },
                readOnly: true
            },
            {
                data: 'remuneracion_basica',
                type: 'text',
                title: 'REMUN.<br/> BÁSICA',
                className: '!text-right !bg-yellow-300',
                readOnly: true
            },
            {
                data: 'bonificacion',
                type: 'text',
                title: 'BONIF.',
                className: '!text-right',
                correctFormat: true,
            },
            {
                data: 'asignacion_familiar',
                type: 'text',
                title: 'ASIG.<br/> FAM.',
                className: '!text-right !bg-yellow-300',
                readOnly: true
            },
            {
                data: 'compensacion_vacacional',
                type: 'text',
                title: 'COMP.<br/>VACAC.',
                className: '!text-right !bg-yellow-300',
                readOnly: true
            },
            {
                data: 'sueldo_bruto',
                type: 'text',
                title: 'SUELDO<br/>BRUTO',
                className: '!text-right !bg-[#C4BD97]',
                readOnly: true
            },
            {
                data: 'dscto_afp_seguro',
                type: 'text',
                title: 'DSCTO.<br/>A.F.P.<br/>PRIMA',
                className: '!text-right',
                renderer: function (instance, td, row, col, prop, value, cellProperties) {
                    const descuentoColores = @json($descuentoColores);
                    const dsctoAfpSeguro = instance.getDataAtRowProp(row, 'spp_snp');
                    const explicacion = instance.getDataAtRowProp(row,
                        'dscto_afp_seguro_explicacion');

                    // Limpiar propiedades anteriores
                    td.classList.remove('has-explanation');
                    td.removeAttribute('title');

                    let color = descuentoColores[dsctoAfpSeguro] ?? '#000000';
                    td.style.color = color;
                    td.style.fontWeight = 'bold';
                    td.className = '!text-right';
                    td.innerHTML = value;

                    // Solo agregar clase y título si hay explicación
                    if (explicacion && explicacion !== '') {
                        td.classList.add('has-explanation');
                        td.setAttribute('title', explicacion);
                    }

                    return td;
                },

                readOnly: true
            },
            {
                data: 'cts',
                type: 'text',
                title: 'CTS',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'gratificaciones',
                type: 'text',
                title: 'GRATIF.',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'essalud_gratificaciones',
                type: 'text',
                title: 'ESS.<br/> GRATIF.',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'beta_30',
                type: 'text',
                title: 'BETA<br/> 30%',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'essalud',
                type: 'text',
                title: 'ESSALUD',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'vida_ley',
                type: 'text',
                title: 'VIDA<br/> LEY',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'pension_sctr',
                type: 'text',
                title: 'PENS.<br/> SCTR',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'essalud_eps',
                type: 'text',
                title: 'ESSAL<br/> EPS.',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'sueldo_neto',
                type: 'text',
                title: 'SUELDO.<br/> NETO',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'rem_basica_essalud',
                type: 'text',
                title: 'REM.<br/> BAS. +<br/> ESSAL.',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'rem_basica_asg_fam_essalud_cts_grat_beta',
                type: 'text',
                title: 'REM. BA.<br/> ASIGN. FA.<br/> ESSALUD<br/>CTS<br/>GRATIF.<br/>BETA',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'jornal_diario',
                type: 'text',
                title: 'JORNAL<br/> DIARIO',
                className: '!text-right',
                readOnly: true
            },
            {
                data: 'costo_hora',
                type: 'text',
                title: 'COSTO<br/>HORA',
                className: '!text-right',
                readOnly: true
            }
            ];

            return columns;
        },
        calcularTotales() {

        },
        sendData() {
            const rawData = this.hot.getData();

            const filteredData = rawData.filter(row => {
                return row.some(cell => cell !== null && cell !== '');
            });

            const data = {
                datos: filteredData
            };
            console.log(data);

            $wire.dispatchSelf('GuardarInformacion', data);
        }
    }));
</script>
@endscript