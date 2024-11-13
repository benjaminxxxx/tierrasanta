<div>
    <x-loading wire:loading />

    <div>
        <x-h3 class="text-center">
            {{ $titulo }}
        </x-h3>
    </div>
    <div x-data="tableAsistencia" wire:ignore class="my-4">
        <div x-ref="tableContainer" class="mt-5 overflow-auto"></div>

        <div class="flex items-center gap-4">
            <x-button
                @click="$wire.dispatch('agregarCuadrilleros',{cuadrilla_asistencia_id:{{ $cuaAsistenciaSemanalId }}})"
                class="mt-5">
                <i class="fa fa-plus"></i> Agregar Cuadrilleros
            </x-button>
            <x-button @click="sendData" class="mt-5">
                <i class="fa fa-save"></i> Guardar Cambios
            </x-button>
        </div>
    </div>
    <div class="my-5 md:flex justify-end">
        <x-table class="!w-auto">
            <x-slot name="thead">
                @if ($periodo)
                <x-tr>
                    <x-th rowspan="3">
                        Cuadro Resumen
                    </x-th>
                    <x-th colspan="{{count($periodo)}}" class="text-center">
                        Precios por día
                    </x-th>
                    <x-th colspan="3" class="text-center">
                        Reporte semanal
                    </x-th>
                </x-tr>
                @endif
                <x-tr>
                    
                    @if ($periodo)
                        @foreach ($periodo as $diaBase)
                        <x-th class="text-center">{{$diaBase['dia']}}</x-th>
                        @endforeach
                    @endif
                    <x-th rowspan="2" class="text-right">Monto a pagar</x-th>
                    <x-th rowspan="2" class="text-center">Condición</x-th>
                    <x-th rowspan="2" class="text-center">Fecha</x-th>
                </x-tr>
                <x-tr>
                    @if ($periodo)
                        @foreach ($periodo as $diaBase)
                        <x-th class="text-center">{{$diaBase['nombre']}}</x-th>
                        @endforeach
                    @endif
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @if ($gruposTotales)
                    @foreach ($gruposTotales as $grupoTotal)
                        <x-tr>
                            <x-th style="background-color:{{$grupoTotal->grupo->color}}" class="!text-gray-900">
                                {{ $grupoTotal->grupo->nombre }}
                            </x-th>
                            @if ($periodo)
                                @foreach ($periodo as $indicePeriodo => $diaBase)
                                @php
                                    $claseBase = "";
                                    if(!$precios[$grupoTotal->id][$indicePeriodo]['base']){
                                        $claseBase = "!text-lime-600";
                                    }
                                @endphp
                                <x-th class="text-center">
                                    <x-input type="number" class="!w-[5rem] text-center !p-1 {{$claseBase}}"
                                        wire:model.live.debounce.1000ms="precios.{{ $grupoTotal->id }}.{{ $indicePeriodo }}.costo_dia" wire:key="cantidad{{ $grupoTotal->id }}.{{ $indicePeriodo }}" />
                                </x-th>
                                @endforeach
                            @endif
                            <x-th class="text-right">
                                {{ $grupoTotal->total_costo }}
                            </x-th>
                            <x-th class="text-center">
                                <x-select wire:change="actualizarEstadoGrupoEnSemana({{$grupoTotal->id}},$event.target.value)" class="px-1 py-2 !text-sm">
                                    <option value="pendiente" {{$grupoTotal->estado_pago=='pendiente'?'selected':''}}>Pendiente</option>
                                    <option value="pagado" {{$grupoTotal->estado_pago=='pagado'?'selected':''}}>Pagado</option>
                                </x-select>
                            </x-th>
                            <x-th class="text-center">
                                <x-input type="date" class="px-1 py-2 !text-sm" value="{{$grupoTotal->fecha_pagado}}" wire:change="actualizarFechaGrupoEnSemana({{$grupoTotal->id}},$event.target.value)" />
                            </x-th>
                        </x-tr>
                    @endforeach
                    @IF($this->semana)
                    <x-tr>
                        <x-th>
                            TOTAL
                        </x-th>
                        <x-th class="text-right">
                            {{ $this->semana->total}}
                        </x-th>
                        <x-th></x-th>
                        <x-th></x-th>
                    </x-tr>
                    @endif
                @endif
            </x-slot>
        </x-table>
    </div>
</div>
@script
    <script>
        Alpine.data('tableAsistencia', () => ({
            listeners: [],
            tableData: @json($cuadrilleros),
            periodo: @json($periodo),
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('obtenerCuadrilleros', (data) => {

                        console.log(data[0]);
                        this.tableData = data[0];
                        this.hot.loadData(this.tableData);
                    })
                );
            },
            initTable() {

                let columns = [];

                columns.push({
                    data: 'codigo_grupo',
                    type: 'text',
                    title: 'GRUPO',
                    renderer: function(instance, td, row, col, prop, value,
                        cellProperties) {

                        const color = instance.getDataAtRowProp(row, 'color');

                        td.style.background = color;
                        td.innerHTML = value;
                        td.className = '!text-center';

                        return td;
                    },
                }, {
                    data: 'nombres',
                    type: 'text',
                    title: 'NOMBRES',
                });

                this.periodo.forEach(dia => {
                    columns.push({
                        data: `dia_${dia.dia}`, // data como "dia_29" por ejemplo
                        type: 'numeric', // tipo número, acepta decimales
                        title: `HORA <br/> ${dia.dia} <br/> ${dia.nombre}`,
                        width: 50,
                        className: '!text-center'
                    });
                });

                this.periodo.forEach(dia => {
                    columns.push({
                        data: `dia_${dia.dia}_monto`, // data como "dia_29" por ejemplo
                        type: 'numeric', // tipo número, acepta decimales
                        title: `DIA <br/> ${dia.dia} <br/> ${dia.nombre}`, // título en formato día + nombre
                        readOnly: true,
                        width: 50,
                        className: '!text-center'
                    });
                });

                this.periodo.forEach(dia => {
                    columns.push({
                        data: `dia_${dia.dia}_bono`,
                        type: 'numeric', // tipo número, acepta decimales
                        title: `BONO <br/> ${dia.dia} <br/> ${dia.nombre}`,
                        width: 50,
                        className: '!text-center'
                    });
                });


                columns.push({
                    data: 'monto',
                    type: 'numeric',
                    title: 'MONTO',
                    className: '!text-center',
                    numericFormat: {
                        pattern: '0.00',
                    },
                });

                const container = this.$refs.tableContainer;
                const hot = new Handsontable(container, {
                    data: this.tableData,
                    colHeaders: true,
                    rowHeaders: true,
                    columns: columns,
                    width: '100%',
                    height: 'auto',
                    manualColumnResize: false,
                    manualRowResize: false,
                    stretchH: 'all',
                    autoColumnSize: true,
                    contextMenu: {
                        items: {
                            "customize_quadrillero": {
                                name: 'Personalizar costo por día',
                                callback: () => this.customizeCuadrillero()
                            },
                            "remove_quadrillero": {
                                name: 'Eliminar cuadrilleros',
                                callback: () => this.eliminarCuadrillerosSeleccionados()
                            }
                        }
                    },
                    cells: (row, col) => {
                        const cellProperties = {};

                        if (row === this.tableData.length - 1) {
                            // Asigna una clase particular a todas las celdas de la última fila
                            cellProperties.className = '!bg-gray-200 font-bold !text-center';
                        }

                        return cellProperties;
                    },
                    licenseKey: 'non-commercial-and-evaluation'
                });

                this.hot = hot;
            },
            customizeCuadrillero(){
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
                    $wire.dispatch('customizarMontosPorDia', data);
                }
            },
            eliminarCuadrillerosSeleccionados() {
                // Obtener las filas seleccionadas
                const selected = this.hot.getSelected();
                let cuadrillerosAEliminar = [];

                if (selected) {
                    selected.forEach(range => {

                        const [startRow, , endRow] = range;
                        for (let row = startRow; row <= endRow; row++) {
                            const cuadrillero = this.hot.getSourceDataAtRow(row);
                            cuadrillerosAEliminar.push(cuadrillero);
                        }
                    });
                    const data = {
                        cuadrilleros: cuadrillerosAEliminar
                    };
                    $wire.dispatch('eliminarCuadrilleros', data);
                }
            },
            sendData() {
                let allData = [];

                // Recorre todas las filas de la tabla y obtiene los datos completos
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const rowData = this.hot.getSourceDataAtRow(row);
                    allData.push(rowData);
                }

                // Filtra las filas vacías
                const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                    null && cell !== ''));

                const data = {
                    data: filteredData
                };

                console.log('Datos a enviar:', data);
                $wire.dispatchSelf('storeTableDataCuadrilla', data);
            }
        }));
    </script>
@endscript
