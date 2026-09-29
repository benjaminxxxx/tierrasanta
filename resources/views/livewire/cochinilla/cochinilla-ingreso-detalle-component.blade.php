<div>
    <!--MODULO COCHINILLA INGRESO - WIZARD-->
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="2xl">
        <x-slot name="title">
            @if ($step === 1)
                Buscar / crear lote
            @elseif ($step === 2)
                Datos del lote {{ $loteBuscado }}
            @else
                Sublotes del lote {{ $loteBuscado }}
            @endif
        </x-slot>

        <x-slot name="content">

            {{-- PASO 1: BUSCAR LOTE --}}
            @if ($step === 1)
                <div class="space-y-3">
                    <x-success>
                        Escribe el número de lote. Si ya existe, iremos directo a sus sublotes.
                        Si no existe, primero pediremos los datos del lote.
                    </x-success>
                    <x-input type="number" label="N° de lote" step="1" min="1" wire:model="loteBuscado" error="loteBuscado" class="w-full" />
                </div>
            @endif

            {{-- PASO 2: DATOS DEL LOTE --}}
            @if ($step === 2)
                <div class="space-y-3">
                    @if ($campania)
                        <x-success>
                            <p>Campaña: {{ $campania->nombre_campania ?? '' }}</p>
                            <p>Variedad: {{ $campania->variedad_tuna ?? '' }}</p>
                            <p>Fecha de Inicio: {{ $campania->fecha_inicio ?? '' }}</p>
                        </x-success>
                    @else
                        <x-warning>
                            No hay campañas registradas para este campo y esta fecha.
                        </x-warning>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                    <x-select label="Campo" wire:model.live="campoSeleccionado" error="campoSeleccionado" fullWidth="true">
                        <option value="">Seleccionar campo</option>
                        @foreach ($campos as $campo)
                            <option value="{{ $campo->nombre }}">{{ $campo->nombre }}</option>
                        @endforeach
                    </x-select>
                    <x-input type="number" label="Área" wire:model="area" class="w-full" />
                    <x-input type="date" label="Fecha" wire:model.live="fecha" error="fecha" class="w-full" />
                    <x-select label="Observación" wire:model="observacionSeleccionada" fullWidth="true"
                        error="observacionSeleccionada">
                        <option value="">Seleccionar observación</option>
                        @foreach ($observaciones as $observacion)
                            <option value="{{ $observacion->codigo }}">{{ $observacion->descripcion }}</option>
                        @endforeach
                    </x-select>
                </div>
            @endif

            {{-- PASO 3: SUBLOTES (HANDSONTABLE) --}}
            @if ($step === 3)
                @if ($cochinillaIngreso)
                    <x-success>
                        <table>
                            <tbody>
                                <tr>
                                    <th class="text-left pe-4">Campo</th>
                                    <td>{{ $cochinillaIngreso->campo }}</td>
                                </tr>
                                <tr>
                                    <th class="text-left pe-4">Campaña</th>
                                    <td>{{ $cochinillaIngreso->campoCampania?->nombre_campania }}</td>
                                </tr>
                                <tr>
                                    <th class="text-left pe-4">Lote principal</th>
                                    <td>{{ $cochinillaIngreso->lote }}</td>
                                </tr>
                                <tr>
                                    <th class="text-left pe-4">Total de kilos</th>
                                    <td>{{ $cochinillaIngreso->total_kilos }}</td>
                                </tr>
                                <tr>
                                    <th class="text-left pe-4">Fecha (última recogida)</th>
                                    <td>{{ $cochinillaIngreso->fecha }}</td>
                                </tr>
                                <tr>
                                    <th class="text-left pe-4">Observación</th>
                                    <td>{{ $cochinillaIngreso->observacionRelacionada?->descripcion }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </x-success>
                @endif

                <ul class="space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400 mt-4">
                    <li>No es necesario digitar el sublote, el sistema le dará su código automáticamente.</li>
                    <li>Todos los campos son obligatorios, sino esa fila no se registrará.</li>
                    <li>¿Te equivocaste de campo? Usa "Atrás" para corregirlo - se aplicará a todos los sublotes.</li>
                </ul>

                <div x-data="{{ $idTable }}" wire:ignore class="my-4">
                    <div x-ref="tableContainer"></div>
                </div>
            @endif

        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-between w-full">
                <div>
                    @if ($step === 2)
                        <x-button variant="secondary" wire:click="$set('step', 1)" wire:loading.attr="disabled">
                            Atrás
                        </x-button>
                    @elseif ($step === 3 && !$esNuevo)
                        <x-button variant="secondary" wire:click="volverAPaso2" wire:loading.attr="disabled">
                            Atrás
                        </x-button>
                    @endif
                </div>

                <x-flex class="gap-2">
                    <x-button  variant="secondary" wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                        Cerrar
                    </x-button>

                    @if ($step === 1)
                        <x-button type="button" wire:click="buscarLote">
                            <i class="fa fa-arrow-right"></i> Continuar
                        </x-button>
                    @elseif ($step === 2)
                        <x-button type="button" wire:click="guardarPaso2">
                            <i class="fa fa-arrow-right"></i> Continuar
                        </x-button>
                    @else
                        <x-button type="button" @click="$wire.dispatch('guardadoConfirmado')">
                            <i class="fa fa-save"></i> Registrar sublotes
                        </x-button>
                    @endif
                </x-flex>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('{{ $idTable }}', () => ({
        listeners: [],
        tableData: [],
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        observacionesOptions: @json($observacionesCodigos),
        hot: null,
        init() {
            this.initTable();
            this.listeners.push(
                Livewire.on('cargarData', (data) => {
                    this.tableData = data[0];
                    this.hot.destroy();
                    this.initTable();
                    this.hot.loadData(this.tableData);
                })
            );
            this.listeners.push(
                Livewire.on('guardadoConfirmado', () => {
                    this.sendDataPoblacionPlanta();
                })
            );
        },
        initTable() {
            const container = this.$refs.tableContainer;
            const hot = new Handsontable(container, {
                data: this.tableData,
                colHeaders: true,
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                rowHeaders: true,
                columns: [{
                    data: 'sublote_codigo',
                    className: '!text-center !bg-gray-200 dark:!bg-muted',
                    readOnly: true,
                    title: 'Sublote'
                },
                {
                    data: 'fecha',
                    type: 'date',
                    className: '!text-center',
                    width: '50',
                    title: 'Fecha'
                },
                {
                    data: 'total_kilos',
                    type: 'numeric',
                    className: '!text-center',
                    width: '40',
                    title: 'Kilos recogidos',
                },
                {
                    data: 'observacion',
                    type: 'dropdown',
                    className: '!text-center',
                    width: 100,
                    source: this.observacionesOptions,
                    strict: true,
                    allowInvalid: false,
                    title: 'Observación'
                }
                ],
                height: '200',
                manualColumnResize: false,
                manualRowResize: true,
                minSpareRows: 1,
                stretchH: 'all',
                autoColumnSize: false,
                licenseKey: 'non-commercial-and-evaluation',
            });

            this.hot = hot;
        },
        sendDataPoblacionPlanta() {
            let allData = [];
            for (let row = 0; row < this.hot.countRows(); row++) {
                const rowData = this.hot.getSourceDataAtRow(row);
                allData.push(rowData);
            }
            const filteredData = allData.filter(row => row && Object.values(row).some(cell => cell !==
                null && cell !== ''));

            const data = {
                datos: filteredData
            };
            $wire.dispatchSelf('storeTableDataCochinillaIngreso', data);
        }
    }));
</script>
@endscript