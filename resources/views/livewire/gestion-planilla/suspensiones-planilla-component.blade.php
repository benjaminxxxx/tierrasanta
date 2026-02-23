<div x-data="suspensionesPlanilla">
    <x-heading title="Gestión de Permisos y Suspensiones"
        subtitle="Administra vacaciones, licencias y otras suspensiones de los trabajadores" />

    @include('livewire.gestion-planilla.partials.periodos-planilla-filter')
    <x-card wire:ignore>
        <div x-ref="tableContainer"></div>
    </x-card>
    @if ($mes && $anio)
        <div class="fixed bottom-6 right-6 z-40">
            <x-button @click="guardarRegistrosSuspensiones">
                <i class="fa fa-save"></i> Guardar Registros
            </x-button>
        </div>
    @endif

    {{-- Vista Blade --}}
    <div class="my-4">
        <title>Suspensiones Pendientes de Registrar</title>

        @if (count($suspensionesPendientes) > 0)
            <x-card>
                <p class="text-sm text-yellow-800 dark:text-yellow-400 mb-3">
                    Se detectaron {{ count($suspensionesPendientes) }} suspensiones pendientes
                </p>

                <div class="space-y-2">
                    @foreach ($suspensionesPendientes as $index => $pendiente)
                        <div class="flex items-center justify-between bg-muted p-2 rounded border-border">
                            <span class="text-sm">{{ $pendiente['detalle'] }}</span>
                            <x-button wire:click="agregarSuspensionAlHandsontable({{ $index }})">
                                <i class="fa fa-plus"></i> Agregar
                            </x-button>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @else
            <x-success>
                No hay suspensiones pendientes de registrar
            </x-success>
        @endif
    </div>
    <x-loading wire:loading />
</div>

@script
    <script>
        Alpine.data('suspensionesPlanilla', () => ({
            tableData: @js($suspensiones),
            hot: null,
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            listaEmpleados: @js($listaEmpleados),
            listaSuspensiones: @js($listaSuspensiones),
            registrosModificados: new Set(),
            init() {
                this.initTable();
                Livewire.on('refrescarTablaSuspensiones', ({
                    data,
                    empleados
                }) => {
                    this.hot.destroy();
                    this.listaEmpleados = empleados;
                    this.tableData = data;
                    this.initTable();
                    // this.hot.loadData(this.tableData);
                });

                Livewire.on('agregar-suspension', ({
                    data
                }) => {
                    console.log(data);

                    const nuevaFila = [
                        data.plan_empleado_id,
                        data.tipo_suspension_id,
                        data.observaciones,
                        data.fecha_inicio,
                        data.fecha_fin,
                        ''
                    ];

                    const rowIndex = this.hot.countRows()-1; // índice donde irá la nueva fila

                    // Insertar una fila vacía al final
                    this.hot.alter('insert_row_below', rowIndex);

                    // Asignar valores en cada celda
                    nuevaFila.forEach((valor, colIndex) => {
                        console.log(valor,colIndex,rowIndex);
                        this.hot.setDataAtCell(rowIndex, colIndex, valor);
                    });
                });
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
                    stretchH: 'all',
                    minSpareRows: 1,
                    autoColumnSize: true,
                    licenseKey: 'non-commercial-and-evaluation',
                    afterChange: (changes, source) => {
                        // Ignorar cambios de carga inicial
                        if (source === 'loadData' || source === 'validator') {
                            return;
                        }

                        // Rastrear filas modificadas
                        if (changes) {
                            changes.forEach(([row, prop, oldValue, newValue]) => {
                                if (oldValue !== newValue) {
                                    this.registrosModificados.add(row);
                                    console.log(`Fila ${row} modificada`);
                                }
                            });
                        }
                    }

                });

                this.hot = hot;
                this.hot.render();
            },
            generateColumns() {
                const empleadosLabels = this.listaEmpleados.map(e => e.label);
                const empleadosMap = Object.fromEntries(
                    this.listaEmpleados.map(e => [e.label, e.id])
                );
                const empleadosReverseMap = Object.fromEntries(
                    this.listaEmpleados.map(e => [e.id, e.label])
                );

                const suspensionesLabels = this.listaSuspensiones.map(s => s.label);
                const suspensionesMap = Object.fromEntries(
                    this.listaSuspensiones.map(s => [s.label, s.id])
                );
                const suspensionesReverseMap = Object.fromEntries(
                    this.listaSuspensiones.map(s => [s.id, s.label])
                );

                return [{
                        data: 'plan_empleado_id',
                        title: 'EMPLEADO',
                        type: 'autocomplete',
                        source: empleadosLabels,
                        strict: false, // ✅ No estricto
                        allowInvalid: false,
                        allowEmpty: true, // ✅ Permitir vacío
                        filter: true,
                        placeholder: 'Buscar empleado...', // ✅ Placeholder visible

                        renderer: function(instance, td, row, col, prop, value) {
                            // Limpiar clases previas
                            td.classList.remove('text-gray-400', 'italic', 'text-red-500');

                            // Valores vacíos
                            if (value === null || value === undefined || value === '' || value === 0) {
                                td.classList.add('text-gray-400', 'italic');
                                td.innerText = 'Seleccionar...';
                                return;
                            }

                            // Buscar el label correspondiente
                            const label = empleadosReverseMap[value];

                            if (label) {
                                td.innerText = label;
                            } else {
                                // ID no válido
                                td.classList.add('text-red-500', 'font-bold');
                                td.innerText = '⚠️ ID ' + value + ' no encontrado';
                            }
                        },

                        validator: function(value, callback) {
                            // ✅ Aceptar vacíos
                            if (!value || value === '' || value === null || value === undefined) {
                                callback(true);
                                return;
                            }

                            // ✅ ID numérico válido
                            if (typeof value === 'number' && empleadosReverseMap[value]) {
                                callback(true);
                                return;
                            }

                            // ✅ Label de texto → convertir a ID
                            if (typeof value === 'string') {
                                const id = empleadosMap[value];
                                if (id) {
                                    // Convertir automáticamente
                                    setTimeout(() => {
                                        this.instance.setDataAtCell(this.row, this.col, id,
                                            'validator');
                                    }, 0);
                                    callback(true);
                                } else {
                                    callback(false); // Texto inválido
                                }
                                return;
                            }

                            callback(false);
                        }
                    },
                    {
                        data: 'tipo_suspension_id',
                        title: 'TIPO DE SUSPENSIÓN',
                        type: 'autocomplete',
                        source: suspensionesLabels,
                        strict: false,
                        allowInvalid: false,
                        allowEmpty: true,
                        filter: true,
                        placeholder: 'Buscar suspensión...',
                        width: 300,

                        renderer: function(instance, td, row, col, prop, value) {
                            td.classList.remove('text-gray-400', 'italic', 'text-red-500');

                            if (value === null || value === undefined || value === '' || value === 0) {
                                td.classList.add('text-gray-400', 'italic');
                                td.innerText = 'Seleccionar...';
                                return;
                            }

                            const label = suspensionesReverseMap[value];

                            if (label) {
                                td.innerText = label;
                            } else {
                                td.classList.add('text-red-500', 'font-bold');
                                td.innerText = '⚠️ ID ' + value + ' no encontrado';
                            }
                        },

                        validator: function(value, callback) {
                            if (!value || value === '' || value === null || value === undefined) {
                                callback(true);
                                return;
                            }

                            if (typeof value === 'number' && suspensionesReverseMap[value]) {
                                callback(true);
                                return;
                            }

                            if (typeof value === 'string') {
                                const id = suspensionesMap[value];
                                if (id) {
                                    setTimeout(() => {
                                        this.instance.setDataAtCell(this.row, this.col, id,
                                            'validator');
                                    }, 0);
                                    callback(true);
                                } else {
                                    callback(false);
                                }
                                return;
                            }

                            callback(false);
                        }
                    },
                    // DESCRIPCION (autocompleta)
                    {
                        data: 'observaciones',
                        title: 'OBSERVACIÓN',
                    },

                    // FECHAS
                    {
                        data: 'fecha_inicio',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        title: 'Inicio'
                    },
                    {
                        data: 'fecha_fin',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        title: 'Fin'
                    },

                    // DIAS
                    {
                        data: 'duracion_dias',
                        title: 'Días',
                        readOnly: true
                    },
                ];
            },
            guardarRegistrosSuspensiones() {
                // ✅ Extraer TODOS los datos (no solo modificados)
                let allData = this.hot.getSourceData();

                // Filtrar solo filas con datos válidos
                const filteredData = allData.filter(row =>
                    row &&
                    row.plan_empleado_id &&
                    row.tipo_suspension_id &&
                    row.fecha_inicio
                );

                $wire.guardarRegistrosSuspensiones(filteredData, this.mes, this.anio);
            }
        }));
    </script>
@endscript
