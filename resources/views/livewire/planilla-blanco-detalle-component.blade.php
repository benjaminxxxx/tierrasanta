<div x-data="planilla_blanco">
    
    @if (!$planillaMensual)
        <x-warning class="mt-4">
            No hay registros de empleados para generar esta planilla, registre asistencias en <a
                href="{{ route('reporte.reporte_diario') }}" class="underline text-blue-600">[Registro Diario de
                Planilla]</a>
        </x-warning>
    @endif
    @if ($planillaMensual)
        <x-card class="mt-5">
            <x-flex class="justify-between">
                <x-flex>
                    <div>
                        <x-label value="Días Laborables" />
                        <p class="dark:text-white font-bold text-xs">{{ $diasLaborables }}</p>
                    </div>
                    <div>
                        <x-label value="Total Horas" />
                        <p class="dark:text-white font-bold text-xs">{{ $totalHoras }}</p>
                    </div>
                    <div>
                        <x-label value="Factor Rem. Básica" />
                        <p class="dark:text-white font-bold text-xs">{{ $factorRemuneracionBasica }}</p>
                    </div>
                </x-flex>
                <x-flex>
                    <x-button type="button" @click="$wire.set('mostrarDescuentosBeneficiosPlanilla',true)">
                        <i class="fa fa-refresh"></i> Cambiar Parámetros
                    </x-button>
                    @if ($planillaMensual->excel)
                        <x-button variant="success" href="{{ Storage::disk('public')->url($planillaMensual->excel) }}">
                            <i class="fa fa-file-excel"></i> Descargar Planilla Generada
                        </x-button>
                    @endif
                </x-flex>

            </x-flex>

        </x-card>
        <x-card class="mt-5">
            <div wire:ignore>
                <div x-ref="tableContainer" class="overflow-auto"></div>
            </div>
        </x-card>
    @endif
    @if ($planillaMensual)
        <div class="fixed bottom-6 right-6 z-40">
            <x-button size="lg" @click="enviarYGenerarPlanilla">
                <i class="fa fa-save"></i> Generar Planilla
            </x-button>
        </div>
    @endif

    @include('livewire.gestion-planilla.administrar-planilla.partial.modal-descuentos-beneficios')
    <x-loading wire:loading />

</div>


@script
    <script>
        Alpine.data('planilla_blanco', () => ({
            listeners: [],
            tableData: @json($planillaMensualDetalle),
            totalHoras: @entangle('totalHoras'),
            hot: null,
            isDark: JSON.parse(localStorage.getItem('darkMode')),
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
                $watch('darkMode', value => {

                    this.isDark = value;
                    const columns = this.generateColumns(this.tareas);
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });
                });
            },
            initTable() {
                const tareas = this.tareas;
                const columns = this.generateColumns(tareas);

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
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
                    afterRender: function() {

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
            calcularTotalHoras(diasLaborables) {
                if (diasLaborables > 0) {
                    this.totalHoras = diasLaborables * 8;
                }
            },
            isValidTimeFormat(time) {
                const timePattern = /^([01]\d|2[0-3]):([0-5]\d)$/;
                return timePattern.test(time);
            },
            generateColumns(tareas) {
                const isDark = this.isDark;
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
                        renderer: function(instance, td, row, col, prop, value, cellProperties) {

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
                        renderer: function(instance, td, row, col, prop, value, cellProperties) {
                            // Cambiar el color del texto basado en el valor de la celda
                            const descuentoColores = @json($descuentoColores);
                            console.log(isDark);
                            let color = descuentoColores[value] ?? '#000000';
                            td.style.color = isDark ? '#ffffff' : color;
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
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-yellow-300',
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
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    {
                        data: 'compensacion_vacacional',
                        type: 'text',
                        title: 'COMP.<br/>VACAC.',
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    {
                        data: 'sueldo_bruto',
                        type: 'text',
                        title: 'SUELDO<br/>BRUTO',
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-[#C4BD97]',
                        readOnly: true
                    },
                    {
                        data: 'dscto_afp_seguro',
                        type: 'text',
                        title: 'DSCTO.<br/>A.F.P.<br/>PRIMA',
                        className: '!text-right',
                        renderer: function(instance, td, row, col, prop, value, cellProperties) {
                            const descuentoColores = @json($descuentoColores);
                            const dsctoAfpSeguro = instance.getDataAtRowProp(row, 'spp_snp');
                            const explicacion = instance.getDataAtRowProp(row,
                                'dscto_afp_seguro_explicacion');

                            // Limpiar propiedades anteriores
                            td.classList.remove('has-explanation');
                            td.removeAttribute('title');

                            let color = descuentoColores[dsctoAfpSeguro] ?? '#000000';
                            td.style.color = isDark ? '#ffffff' : color;
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
                        data: 'total_horas',
                        type: 'text',
                        title: 'TOTAL<br/>HORAS',
                        className: '!text-center',
                        readOnly: true
                    },
                    {
                        data: 'sueldo_blanco_pagado',
                        type: 'text',
                        title: 'MONTO BLANCO<br/>PAGADO',
                        className: '!text-right !bg-stone-600',
                    },
                    {
                        data: 'sueldo_negro_pagado',
                        type: 'text',
                        title: 'MONTO NEGRO<br/>PAGADO',
                        className: '!text-right',
                        readOnly: true
                    },
                    /*
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
                    },*/
                    {
                        data: 'sueldo_neto',
                        type: 'text',
                        title: 'SUELDO.<br/> NETO',
                        className: '!text-right',
                        readOnly: true
                    },
                    /*{
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
                    },*/
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
            enviarYGenerarPlanilla() {
                //EXTRAE LA DATA HANDSONTABLE COMPLETA
                let allData = [];
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const rowData = this.hot.getSourceDataAtRow(row);
                    allData.push(rowData);
                }
                const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                    null && cell !== ''));

                $wire.generarPlanillaMensual(filteredData);
            }
        }));
    </script>
@endscript
