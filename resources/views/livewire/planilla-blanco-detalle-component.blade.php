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
            <x-flex class="justify-end mb-4">
                <x-button x-on:click="toggleNegro()" x-text="verNegro ? 'Ver Columnas Blanco' : 'Ver Columnas Negro'">
                </x-button>
            </x-flex>
            <div wire:ignore>
                <div x-ref="tableContainer"></div>
            </div>
        </x-card>
    @endif
    @if ($planillaMensual)
        <div class="fixed bottom-6 right-6 z-40">
            {{-- enviarYGenerarPlanilla --}}
            <x-button type="button" @click="enviarYGenerarPlanilla2">
                <i class="fa fa-save"></i> Generar Planilla
            </x-button>
        </div>
    @endif

    @include('livewire.gestion-planilla.administrar-planilla.partial.modal-descuentos-beneficios')
    <x-loading wire:loading />
    <style>
        body .handsontable .htDimmed.text-total {
            color: #3fae03 !important;
        }
    </style>
</div>


@script
    <script>
        Alpine.data('planilla_blanco', () => ({
            listeners: [],
            tableData: @json($planillaMensualDetalle),
            totalHoras: @entangle('totalHoras'),
            hot: null,
            verNegro: false,
            negroColumns: [5, 6, 7],
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            init() {

                this.initTable();
                Livewire.on('renderTable', ({
                    data
                }) => {

                    const columns = this.generateColumns();
                    this.hot.updateSettings({
                        columns: columns
                    });
                    this.hot.loadData(data);
                });

                $watch('darkMode', value => {

                    this.isDark = value;
                    const columns = this.generateColumns();
                    this.hot.updateSettings({
                        themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: columns
                    });

                });
            },
            toggleNegro() {
                this.verNegro = !this.verNegro;
                this.applyColumnVisibility();
            },

            applyColumnVisibility() {
                /*
                const plugin = this.hot.getPlugin('hiddenColumns');

                if (this.verNegro) {
                    // Mostrar todo, activar vista NEGRO
                    plugin.showColumns(this.negroColumns);
                } else {
                    // Ocultar columnas negro → Vista BLANCO
                    plugin.hideColumns(this.negroColumns);
                }

                this.hot.render();*/
            },
            initTable() {
                const columns = this.generateColumns();

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                    colHeaders: true,
                    rowHeaders: true,
                    columns: columns,
                    width: '100%',
                    manualColumnResize: false,
                    manualRowResize: true,
                    /*hiddenColumns: {
                        columns: [0, 9, 10, 11, 12, 16, 18],
                        indicators: true
                    },*/
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
                this.applyColumnVisibility();
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
            generateColumns() {
                const isDark = this.isDark;
                let columns = [{
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
                    },
                    {
                        data: 'dias_laborados',
                        type: 'numeric',
                        title: 'DIAS<br/>LABOR',
                        className: '!text-center',
                        readOnly: true
                    },
                    {
                        data: 'dias_no_laborados',
                        type: 'numeric',
                        title: 'DIAS<br/>NO<br/>LABOR',
                        className: '!text-center',
                        readOnly: true
                    },
                    {
                        data: 'horas_trabajadas',
                        type: 'numeric',
                        title: 'HORAS<br/>TRAB.',
                        className: '!text-center',
                        readOnly: true
                    },
                    {
                        data: 'remuneracion_basica',
                        type: 'text',
                        title: '0121<br/>SUELDO.<br/> CONTRATO',
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    {
                        data: 'asignacion_familiar',
                        type: 'text',
                        title: 'ASIG.<br/> FAM.',
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    /*{
                        data: 'blanco_descuento_por_faltas',
                        type: 'text',
                        title: 'DESC.<br/> FALTAS.',
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },*/
                    {
                        data: 'blanco_remuneracion_bruta',
                        type: 'text',
                        title: 'REMUN.<br/> BRUTA.',
                        className: isDark ? '!text-right !bg-[#332f2c] font-bold' :
                            '!text-right !bg-yellow-500 font-bold',
                        readOnly: true
                    },
                    {
                        data: 'spp_snp',
                        type: 'text',
                        className: '!text-center',
                        title: 'ONP/<br/>AFP',
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
                        data: 'dscto_afp_seguro',
                        type: 'text',
                        title: 'DSCTO.<br/>A.F.P.<br/>PRIMA %',
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
                        data: 'blanco_descuento_onp_afp_prima',
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
                        data: 'blanco_beta30',
                        type: 'text',
                        title: '0314<br/>BETA<br/> 30% TOTAL',
                        className: '!text-right',
                        readOnly: true
                    },
                    {
                        data: 'blanco_cts',
                        type: 'text',
                        title: '0904<br/>CTS',
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    
                    {
                        data: 'blanco_gratificaciones',
                        type: 'text',
                        title: '0406<br/>GRATIFICA<br/>CIONES',
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    {
                        data: 'blanco_essalud_gratificaciones',
                        type: 'text',
                        title: '0312<br/>BONIF. EX.<br/>TEMP. LEY <br/>29351 y 30334',
                        className: isDark ? '!text-right !bg-stone-700' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    {
                        data: 'blanco_essalud',
                        type: 'text',
                        title: '0804<br/>ESSALUD',
                        className: isDark ? '!text-right !bg-indigo-900' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    {
                        data: 'blanco_vida_ley',
                        type: 'text',
                        title: '0803<br/>VIDA<br/> LEY',
                        className: isDark ? '!text-right !bg-indigo-900' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    {
                        data: 'blanco_pension_sctr',
                        type: 'text',
                        title: '0805<br/>PENS.<br/> SCTR',
                        className: isDark ? '!text-right !bg-indigo-900' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    {
                        data: 'blanco_essalud_eps',
                        type: 'text',
                        title: '0810<br/>EPS',
                        className: isDark ? '!text-right !bg-indigo-900' : '!text-right !bg-yellow-300',
                        readOnly: true
                    },
                    {
                        data: 'blanco_sueldo_neto',
                        type: 'text',
                        title: 'SUELDO<br/>NETO',
                        className: isDark ? '!text-right !bg-red-900' : '!text-right !bg-red-300',
                        readOnly: true
                    },
                    {
                        data: 'negro_sueldo_bruto',
                        type: 'text',
                        title: 'SUELDO<br/>NEGRO<br/>ESTIMADO',
                        className: '!text-right !bg-muted',
                        readOnly: true
                    },
                    {
                        data: 'sueldo_negro_subtotal',
                        type: 'text',
                        title: 'SUELDO<br/>NEGRO<br/>SUBTOTAL',
                        className: '!text-right !bg-muted',
                        readOnly: true
                    },

                    {
                        data: 'negro_bono_asistencia',
                        type: 'numeric',
                        title: 'BONIF. X<br/>100% ASIST.',
                        className: '!text-right',
                        correctFormat: true,
                        allowInvalid: false
                    },
                    {
                        data: 'negro_bono_productividad',
                        type: 'numeric',
                        title: 'BONIF. X<br/>PRODU.',
                        className: '!text-right !bg-muted',
                        readOnly: true
                    },
                    {
                        data: 'sueldo_negro_total',
                        type: 'numeric',
                        title: 'SUELDO<br/>NEGRO<br/>TOTAL',
                        className: '!text-right !bg-muted text-total !font-bold',
                        readOnly: true
                    },
                    {
                        data: 'costo_total_blanco',
                        type: 'numeric',
                        title: 'COSTO<br/>TOTAL<br/>BLANCO',
                        className: '!text-right !bg-pink-700 !font-bold',
                        readOnly: true
                    },
                    {
                        data: 'costo_total_negro',
                        type: 'numeric',
                        title: 'COSTO<br/>TOTAL<br/>NEGRO',
                        className: '!text-right !bg-pink-700 !font-bold',
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
            },
            enviarYGenerarPlanilla2() {
                //EXTRAE LA DATA HANDSONTABLE COMPLETA
                let allData = [];
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const rowData = this.hot.getSourceDataAtRow(row);
                    allData.push(rowData);
                }
                const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                    null && cell !== ''));

                $wire.generarPlanillaMensual2(filteredData);
            }
        }));
    </script>
@endscript
