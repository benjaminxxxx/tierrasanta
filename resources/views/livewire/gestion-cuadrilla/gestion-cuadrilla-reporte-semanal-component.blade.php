<div>
    <div x-data="reporte_semanal_cuadrilleros">
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
            <x-flex>
                <x-select name="columns" id="columns" label="Filtro">
                    <option value="1">Nombre</option>
                    <option value="0">Código</option>
                </x-select>
                <x-group-field>
                    <x-label>
                        Descripción
                    </x-label>
                    <x-input id="filterField" type="text" placeholder="Buscar por código o nombre" />
                </x-group-field>
            </x-flex>
            <div x-ref="tableContainerSemana" class="mt-5"></div>
        </div>
        <x-flex class="mt-4 justify-between">
            <x-button @click="abrirAgregarCuadrillero" wire:loading.attr="disabled">
                <i class="fa fa-plus"></i> Agregar cuadrilleros
            </x-button>
            
            <x-flex>
                <x-button @click="$wire.dispatch('abrirGastosAdicionales',{inicio:fechaInicioSemana})">
                    <i class="fa fa-plus-minus"></i> Agregar/Quitar gastos adicionales
                </x-button>
                <x-button @click="registrarHoras">
                    <i class="fa fa-save"></i> Actualizar horas
                </x-button>
            </x-flex>
        </x-flex>

    </div>


    <x-dialog-modal wire:model.live="mostrarFormularioCostoHora" maxWidth="full">
        <x-slot name="title">
            Personaliza el precio por cuadrillero
        </x-slot>

        <x-slot name="content">

            <x-flex class="w-full">
                <div class="flex-1">
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center" rowspan="2">
                                    N°
                                </x-th>
                                <x-th rowspan="2">
                                    Cuadrillero
                                </x-th>
                                @if ($diasSemana)
                                    @foreach ($diasSemana as $diaSemana)
                                        <x-th class="text-center">{{ \Carbon\Carbon::parse($diaSemana)->format('d') }}</x-th>
                                    @endforeach
                                @endif
                            </x-tr>
                            <x-tr>
                                @if ($diasSemana)
                                    @foreach ($diasSemana as $diaSemana)
                                        <x-th class="text-center">
                                            {{ \Carbon\Carbon::parse($diaSemana)->isoFormat('ddd') }}
                                        </x-th>
                                    @endforeach
                                @endif
                            </x-tr>
                        </x-slot>

                        <x-slot name="tbody">
                            @if ($cuadrillerosCostosPersonalizados)
                                @foreach ($cuadrillerosCostosPersonalizados as $indice => $cuadrillero)
                                    <x-tr>
                                        <x-td class="text-center">
                                            {{ $indice + 1 }}
                                        </x-td>
                                        <x-td>
                                            {{ $cuadrillero['cuadrillero_nombres'] ?? '-' }}
                                        </x-td>
                                        @foreach ($cuadrillero['costos'] as $indice => $costo)
                                            @php
                                                $id = $cuadrillero['cuadrillero_id'];
                                            @endphp
                                            <x-td class="text-center">
                                                <x-input class="!p-2 text-center" wire:key="costo-{{ $indice }}-{{ $id }}"
                                                    wire:model="cuadrillerosCostosPersonalizados.{{ $id }}.costos.{{ $indice }}"
                                                    type="number" step="0.01" />
                                            </x-td>
                                        @endforeach
                                    </x-tr>
                                @endforeach
                            @endif
                        </x-slot>
                    </x-table>

                </div>
            </x-flex>
            <div>
                <b class="block my-2">Referencia:</b>
                <ul>
                    <li>
                        <x-flex>
                            <div class="w-5 h-5 block bg-lime-600"></div>
                            Montos Personalizados por Día
                        </x-flex>
                    </li>
                    <li>
                        <x-flex>
                            <div class="w-5 h-5 block bg-amber-400"></div>
                            Montos Personalizados por Cuadrillero
                        </x-flex>
                    </li>
                </ul>
                <b class="block my-2">Instrucciones:</b>
                <ul>
                    <li>
                        <p>Modifique directamente los precios, el sistema te permitirá 1 segundo para que puedas digitar
                            y luego lo guardará de forma automática.</p>
                    </li>
                    <li>
                        <p>
                            Si deseas quitar el precio personalizado, simplemente bórralo, el sistema borrará el
                            registro y usará el precio semanal o si existe, el precio personalizado por día.
                        </p>
                    </li>
                </ul>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex class="flex-end">
                <x-secondary-button wire:click="$set('mostrarFormularioCostoHora', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="registrarCostoPersonalizado" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> Registrar costo
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    <livewire:gestion-cuadrilla.gestion-cuadrilla-asignacion-costos-component />

    
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('reporte_semanal_cuadrilleros', () => ({
        listeners: [],
        fechaInicioSemana: @entangle('fechaInicioSemana'),
        ocurrioModificaciones: @entangle('ocurrioModificaciones'),
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
                //this.initTableGastosAdicionales();
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
                minSpareRows: 1,
                fixedColumnsLeft: 2,
                contextMenu: {
                    items: {
                        "customize_quadrillero": {
                            name: 'Personalizar costo por día',
                            callback: () => this.customizeCuadrillero()
                        }
                    }
                },
                afterChange: (changes, source) => {
                    if (source === 'edit') {
                        this.ocurrioModificaciones = true;
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
                plugins: ['Filters'],
            });

            const filterField = document.getElementById('filterField');
            const columnSelector = document.getElementById('columns');
            if (filterField && columnSelector) {
                filterField.addEventListener('keyup', (event) => {
                    const filtersPlugin = this.hot.getPlugin('filters');
                    const columnIndex = parseInt(columnSelector.value, 10);

                    filtersPlugin.clearConditions();
                    filtersPlugin.addCondition(columnIndex, 'contains', [event.target.value]);
                    filtersPlugin.filter();
                    this.hot.render();
                });
            }
        },
        abrirAgregarCuadrillero(){
            $wire.dispatch('agregarCuadrilleros',{fecha:this.fechaInicioSemana})
        },
        generarColumnasDinamicas() {
            const cols = [

                {
                    data: 'codigo_grupo',
                    title: 'Grupo',
                    type: 'dropdown',
                    width: 100,
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
                    width: 40,
                    title: this.headers[i - 1] ?? '-',
                    type: 'numeric',
                    strict: true,
                    filter: false,
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
        customizeCuadrillero() {

            const selected = this.hot.getSelected();
            let preciosamodificar = [];

            if (selected) {
                selected.forEach(range => {

                    const [startRow, , endRow] = range;
                    for (let row = startRow; row <= endRow; row++) {
                        const cuadrillero = this.hot.getSourceDataAtRow(row);
                        preciosamodificar.push(cuadrillero);
                    }
                });
                const data = {
                    cuadrilleros: preciosamodificar
                };
                $wire.abrirPrecioPersonalizado(preciosamodificar);
            }
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

            this.ocurrioModificaciones = false;

            $wire.storeTableDataGuardarHoras(filteredData);
        }
    }));
</script>
@endscript