<div class="w-full" x-data="component_compania">
    <x-loading wire:loading />
    <x-h3 class="mb-4">
        Camapañas por Campo
    </x-h3>

    <div class="md:flex gap-5 w-full">
        <div class="flex-1">
            <x-card>
                <x-spacing>

                    <div class="mt-5" wire:ignore>
                        <div x-ref="tableContainer" class="overflow-auto"></div>
                    </div>
                    <div class="my-5 md:flex gap-5 items-center justify-end">
                        
                        <x-button @click="sendData">
                            Agregar Nueva Vigencia
                        </x-button>
                    </div>
                </x-spacing>

            </x-card>
        </div>
        <div class="md:w-[32rem]">
            <x-h3 class="mb-3">
                Historial de Campañas
            </x-h3>
            <x-card class="overflow-hidden">
                <x-spacing>
                    <x-button wire:click.prevent="agregarVigencia" aria-current="true"
                        class="block w-full px-4 py-3 text-center font-bold border-b border-gray-200 rounded-t-lg cursor-pointer dark:bg-gray-800 dark:border-gray-600">
                        Agregar Nueva Vigencia
                    </x-button>
                    @if (is_array($fechasRegistradas) && count($fechasRegistradas) > 0)
                        @foreach ($fechasRegistradas as $fechasRegistrada)
                            @php

                                $fecha_actual_estilo = $fechasRegistrada == $fecha ? 'text-primaryText bg-primary' : ''; // Ajusta esto según tu lógica
                            @endphp
                            <div class="flex items-center gap-3">
                                <a href="#" wire:click.prevent="cambiarFechaA('{{ $fechasRegistrada }}')"
                                    aria-current="true"
                                    class="block w-full px-4 py-3 {{ $fecha_actual_estilo }} text-center font-bold border-gray-200 rounded-lg mt-2 cursor-pointer dark:bg-gray-800 dark:border-gray-600">
                                    {{ $fechasRegistrada }}
                                </a>
                                <a href="#" wire:click.prevent="eliminarFecha('{{ $fechasRegistrada }}')"
                                    aria-current="true"
                                    class="block w-auto px-4 py-3 bg-red-600 text-white text-center font-bold border-red-700 rounded-lg mt-2 cursor-pointer dark:bg-gray-800 dark:border-gray-600">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </div>
                        @endforeach
                    @endif
                </x-spacing>
            </x-card>
        </div>
    </div>
    
    <x-dialog-modal wire:model.live="mostrarNuevoForm">
        <x-slot name="title">
            Agregar Nueva Vigencia
        </x-slot>

        <x-slot name="content">
            <div>
                Elija una fecha donde se regira los nuevos campos
            </div>

            <x-input wire:model="fecha" class="!w-auto my-5" type="date" />

            <label class="flex items-center">
                <x-checkbox wire:model="usarInformacionAnterior"/>
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Utilizar información de la ultima vigencia</span>
            </label>

        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-4 justify-end">
                <x-secondary-button wire:click="$set('mostrarNuevoForm', false)" wire:loading.attr="disabled">
                    Cancelar
                </x-secondary-button>
                <x-button wire:click="crearVigencia" wire:loading.attr="disabled">
                    Crear
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>

@script
    <script>
        Alpine.data('component_compania', () => ({
            listeners: [],
            tableData: @json($lotes),
            totales: null,
            hot: null,
            init() {
                this.initTable();
                this.listeners.push(
                    Livewire.on('renderTable', (data) => {
                        console.log(data);
                        let lotes = data[0];
                        this.tableData = lotes;
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
                    licenseKey: 'non-commercial-and-evaluation',
                    afterRender: function() {
                        
                        const htCoreTable = document.querySelector('.htCore');
                        const tableHeight = htCoreTable.offsetHeight;
                        if (tableHeight > 0) {
                            container.style.minHeight = `${tableHeight}px`;
                        }
                    },

                });

                this.hot = hot;
                this.hot.render();
            },
            isValidTimeFormat(time) {
                const timePattern = /^([01]\d|2[0-3]):([0-5]\d)$/;
                return timePattern.test(time);
            },
            generateColumns(tareas) {
                let columns = [{
                        data: 'lote',
                        type: 'text',
                        title: 'LOTE',
                        className: '!text-center'
                    },
                    {
                        data: "area",
                        type: 'text',
                        title: 'AREA',
                        className: '!text-center'
                    },

                    {
                        data: 'campania',
                        type: 'text',
                        title: 'CAMPAÑA',
                        className: '!text-center'
                    }
                ];

                return columns;
            },
            sendData() {
                const rawData = this.hot.getData();

                const filteredData = rawData.filter(row => {
                    return row.some(cell => cell !== null && cell !== '');
                });

                const data = {
                    datos: filteredData
                };


                $wire.dispatchSelf('GuardarInformacion', data);
            }
        }));
    </script>
@endscript
