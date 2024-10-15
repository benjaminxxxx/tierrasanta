<div x-data="planilla_blanco">
    @if ($informacionBlanco)
        <x-card class="mt-5">
            <x-spacing>
                <x-h3>MES DE {{ mb_strtoupper($mesTitulo) }} - {{ $anio }}</x-h3>
                <div class="mt-5 md:flex items-end gap-4">
                    <div class="w-full md:flex-1">
                        <p class="w-auto">Días Laborables</p>
                        <x-input class="!w-auto" wire:model.live="diasLaborables" />
                    </div>
                    <div class="w-full md:flex-1">
                        <p class="w-auto">Total Horas</p>
                        <x-input class="!w-auto" wire:model="totalHoras" />
                    </div>
                    <div class="w-full md:flex-1">
                        <p class="w-auto">Factor Rem. Básica {{$rmv}}/{{$diasMes}}</p>
                        <x-input class="!w-auto" readonly value="{{$factorRemuneracionBasica}}" />
                    </div>
                    <div class="mt-3 w-full md:flex-1">
                        <x-button wire:click="guardarPlanillaDatos">
                            Guardar
                        </x-button>
                    </div>
                    <div class="mt-3 w-full md:flex-1">
                        <x-button-a href="{{route('planilla.asistencia',['mes'=>$mes,'anio'=>$anio])}}" class="whitespace-nowrap">
                            Revisar Asistencia
                        </x-button-a>
                    </div>
                    @if($informacionBlanco)
                    <div class="mt-3 w-full">
                        <x-button wire:click="generarPlanilla">
                            @if ($informacionBlancoDetalle && $informacionBlancoDetalle->count() > 0)
                                Regenerar Planilla
                            @else
                                Generar Planilla
                            @endif
                        </x-button>
                    </div>
                    @endif
                </div>
                <div class="mt-5" wire:ignore>
                    <div x-ref="tableContainer" class="min-h-[45rem] overflow-auto"></div>
                </div>
                <div class="mt-5 flex justify-end">
                    <x-button @click="sendData">Guardar Bonificaciones</x-button>
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
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('renderTable', (data) => {
                        console.log(data);
                        let empleados = data[0];
                        this.tableData = empleados;
                        this.hot.loadData(this.tableData);
                    })
                );
                this.listeners.push(
                    Livewire.on('setColumnas', (data) => {
                        console.log(data);
                        const tareas = data[0];
                        const columns = this.generateColumns(tareas);
                        this.hot.updateSettings({
                            columns: columns
                        });

                        // Vuelve a cargar los datos actuales en la tabla (si fuera necesario)
                        this.hot.loadData(this.tableData);
                    })
                );
            },
            initTable() {
                const tareas = this.tareas;
                const columns = this.generateColumns(tareas);

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    colHeaders: true,
                    rowHeaders: true,
                    columns: columns,
                    width: '100%',
                    height: '90%',
                    manualColumnResize: false,
                    manualRowResize: true,
                    minSpareRows: 1,
                    stretchH: 'all',
                    autoColumnSize: true,
                    fixedColumnsLeft: 2,
                    licenseKey: 'non-commercial-and-evaluation',
                    

                });

                this.hot = hot;

                //this.calcularTotales();
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
                        renderer: function(instance, td, row, col, prop, value, cellProperties) {
                          
                            const color = instance.getDataAtRowProp(row, 'empleado_grupo_color');

                            td.style.background = color;
                            td.innerHTML = value;

                            return td;
                        },
                        readOnly: true
                    },//empleado_grupo_color
                    {
                        data: 'spp_snp',
                        type: 'text',
                        className: '!text-center',
                        title: 'SPP/<br/>SNP',
                        renderer: function(instance, td, row, col, prop, value, cellProperties) {
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
                        renderer: function(instance, td, row, col, prop, value, cellProperties) {
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
