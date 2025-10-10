<div x-data="gestion_cuadrilla_pagos">
    <x-loading wire:loading />

    <x-flex class="w-full justify-between">
        <x-flex class="my-3">
            <div>
                <x-h3>
                    Registro Pagos Cuadrilla
                </x-h3>
                <x-label>Gestión flexible de pagos por trabajador</x-label>
            </div>
        </x-flex>
        <x-button-a href="{{ route('cuadrilleros.gestion') }}">
            <i class="fa fa-arrow-left"></i> Volver a gestión de cuadrilleros
        </x-button-a>
    </x-flex>

    <x-card2>
        <x-flex class="justify-between">
            <form wire:submit="buscarRegistros">
                <x-flex class="w-full !items-end">
                    <x-input-date label="Fecha inicio" wire:model="fecha_inicio" error="fecha_inicio" />
                    <x-input-date label="Fecha fin" wire:model="fecha_fin" error="fecha_fin" />
                    
                    <x-select label="Grupo" wire:model="grupoSeleccionado" error="grupoSeleccionado">
                        <option value="">TODOS LOS GRUPOS</option>
                        @foreach ($grupoCuadrillas as $grupoCuadrilla)
                            <option value="{{ $grupoCuadrilla->codigo }}">{{ $grupoCuadrilla->nombre }}</option>
                        @endforeach
                    </x-select>
                    <x-input-string label="Buscar por nombre" wire:model="nombre_cuadrillero"
                        error="nombre_cuadrillero" />
                    <x-button type="submit">
                        <i class="fa fa-filter"></i> Filtrar
                    </x-button>
                </x-flex>
            </form>
            <div class="relative">
                <x-dropdown width="60">
                    <x-slot name="trigger">
                        <span class="inline-flex rounded-md">
                            <x-button class="flex items-center">
                                Reporte <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m1 1 4 4 4-4" />
                                </svg>
                            </x-button>
                        </span>
                    </x-slot>

                    <x-slot name="content">
                        <div class="w-60">
                            <x-dropdown-link class="text-center" wire:click="generarReportePagosCuadrilla">
                                Generar reporte Excel
                            </x-dropdown-link>
                            <x-dropdown-link class="text-center" wire:click="generarReportePagosCuadrilla">
                                Generar recibo de pagos
                            </x-dropdown-link>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>
        </x-flex>

    </x-card2>
    <x-h3 class="my-3">
        Registro de Pagos
    </x-h3>
    <x-card2 wire:ignore>
        <div x-ref="tableRegistroPagos"></div>
    </x-card2>
    <style>
        .handsontable .htDimmed.\!bg-green-400 {
            background-color: #b3ffba !important;
            color: #000 !important;
            font-weight: bold !important;
        }
    </style>
</div>
@script
<script>
    Alpine.data('gestion_cuadrilla_pagos', () => ({
        registroPagos: @json($registros),
        hot: null,
        headers: @json($header),
        init() {
            this.initTable();
            Livewire.on('cargarRegistroPagos', (data) => {
                this.registroPagos = data[0];
                this.headers = data[1];
                console.log(this.registroPagos);
                this.$nextTick(() => this.initTable());
            });
        },
        initTable() {
            if (this.hot) {
                this.hot.destroy();
            }

            const container = this.$refs.tableRegistroPagos;
            this.hot = new Handsontable(container, {
                data: this.registroPagos,
                themeName: 'ht-theme-main-dark-auto',
                colHeaders: true,
                rowHeaders: true,
                columns: this.generarColumnasDinamicas(),
                width: '100%',
                height: 'auto',
                stretchH: 'all',
                fixedColumnsLeft: 2,
                contextMenu: {
                    items: {
                        "realizar_pago": {
                            name: 'Registrar Pago',
                            callback: () => this.registrarPago()
                        }
                    }
                },
                cells: function (row, col) {
                    const cellProperties = {};

                    const colSettings = this.instance.getSettings().columns[col];
                    if (!colSettings || typeof colSettings.data !== 'string') return cellProperties;

                    const dataKey = colSettings.data; // ej: 'jornal_1'
                    const match = dataKey.match(/^jornal_(\d+)$/);
                    if (match) {
                        const index = match[1];
                        const isPagado = this.instance.getDataAtRowProp(row, `pagado_${index}`);
                        if (isPagado) {
                            cellProperties.className = '!bg-green-400';
                        }
                    }

                    return cellProperties;
                },
                licenseKey: 'non-commercial-and-evaluation'
            });
        },
        registrarPago() {
            const selected = this.hot.getSelected();
            let registrosAPagar = [];

            if (selected) {
                const columnsSettings = this.hot.getSettings().columns;

                selected.forEach(range => {
                    const [startRow, startCol, endRow, endCol] = range;

                    for (let row = startRow; row <= endRow; row++) {
                        const cuadrillero = this.hot.getSourceDataAtRow(row);

                        const filaProcesada = {
                            cuadrillero_id: cuadrillero.cuadrillero_id ?? null,
                            codigo: cuadrillero.codigo ?? null,
                            nombre_cuadrillero: cuadrillero.nombre_cuadrillero ?? '',
                            pagos: {}
                        };

                        for (let col = startCol; col <= endCol; col++) {
                            const key = columnsSettings[col]?.data; // Aquí obtenemos la clave real, como 'jornal_1'
                            const valor = this.hot.getDataAtCell(row, col);

                            if (key?.startsWith('jornal_') && valor !== null && valor !== '') {
                                filaProcesada.pagos[key] = valor;
                            }
                        }

                        registrosAPagar.push(filaProcesada);
                    }
                });

                console.log('Registros a pagar:', registrosAPagar);

                $wire.registrarPago(registrosAPagar);
            }
        },
        generarColumnasDinamicas() {
            const columnas = [
                {
                    data: 'codigo',
                    title: 'Grupo',
                    readOnly: true,
                    width: 100,
                },
                {
                    data: 'nombre_cuadrillero',
                    title: 'Trabajador',
                    readOnly: true,
                    width: 150,
                }
            ];

            this.headers.forEach(header => {
                columnas.push({
                    data: 'jornal_' + header.keyIndex,
                    title: 'Día<br/>' + header.label,
                    type: 'numeric',
                    numericFormat: { pattern: '0.00' },
                    readOnly: true
                });
            });
            this.headers.forEach(header => {
                columnas.push({
                    data: 'bono_' + header.keyIndex,
                    title: 'Bono<br/>' + header.label,
                    type: 'numeric',
                    numericFormat: { pattern: '0.00' },
                    readOnly: true
                });
            });

            columnas.push({
                data: 'total_jornal',
                title: 'TOTAL<br/>JORNAL',
                type: 'numeric',
                numericFormat: { pattern: '0.00' },
                readOnly: true,
                className: '!text-lg !font-bold'
            }, {
                data: 'total_bono',
                title: 'TOTAL<br/>BONO',
                type: 'numeric',
                numericFormat: { pattern: '0.00' },
                readOnly: true,
                className: '!text-lg !font-bold'
            }, {
                data: 'total',
                title: 'TOTAL',
                type: 'numeric',
                numericFormat: { pattern: '0.00' },
                readOnly: true,
                className: '!text-lg !font-bold'
            });


            return columnas;
        }

    }));
</script>
@endscript