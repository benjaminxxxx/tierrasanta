<div x-data="suspensionesPlanilla">
    <x-heading title="Gestión de Permisos y Suspensiones"
        subtitle="Administra vacaciones, licencias y otras suspensiones de los trabajadores" />

    @include('livewire.planilla.asistencia.partials.periodos-planilla-filter')

    {{-- Transferencia del registro diario a suspensiones: sugerencias (izq.) y lo que requiere decisión (der.) --}}
    @if ($mes && $anio)
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 my-4">
            {{-- PANEL 1: sugerencias listas para registrar --}}
            <x-card class="xl:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div>
                        <h3 class="font-semibold text-foreground">Sugerencias desde el registro diario</h3>
                        <p class="text-xs text-muted-foreground">
                            Rangos armados con los días consecutivos de cada trabajador (los domingos sin registro se
                            incluyen en vacaciones, descanso médico y maternidad). Revisa y acepta de golpe.
                        </p>
                    </div>
                    @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                        @if (count($sugerencias))
                            <x-button wire:click="aceptarSugerencias" wire:confirm="¿Registrar las {{ count($seleccionadas) }} suspensiones marcadas?">
                                <i class="fa fa-check-double"></i> Aceptar {{ count($seleccionadas) }} de {{ count($sugerencias) }}
                            </x-button>
                        @endif
                    @endcan
                </div>

                @if (count($sugerencias))
                    <div class="overflow-auto max-h-[28rem] border border-border rounded">
                        <table class="w-full text-xs">
                            <thead class="bg-muted sticky top-0 text-muted-foreground">
                                <tr>
                                    <th class="p-2 w-8">
                                        <input type="checkbox" class="rounded" wire:click="alternarTodas"
                                            @checked(count($seleccionadas) === count($sugerencias))>
                                    </th>
                                    <th class="p-2 text-left">Trabajador</th>
                                    <th class="p-2 text-center">Asist.</th>
                                    <th class="p-2 text-left">Suspensión</th>
                                    <th class="p-2 text-left">Rango</th>
                                    <th class="p-2 text-center">Días</th>
                                    <th class="p-2 text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sugerencias as $s)
                                    <tr class="border-t border-border hover:bg-muted/50" wire:key="sug-{{ $s['clave'] }}">
                                        <td class="p-2 text-center">
                                            <input type="checkbox" class="rounded" value="{{ $s['clave'] }}" wire:model.live="seleccionadas">
                                        </td>
                                        <td class="p-2">{{ $s['trabajador'] }}</td>
                                        <td class="p-2 text-center font-mono">{{ $s['codigos'] }}</td>
                                        <td class="p-2">{{ $s['tipo_suspension'] }}</td>
                                        <td class="p-2 whitespace-nowrap">
                                            {{ \Illuminate\Support\Carbon::parse($s['fecha_inicio'])->format('d/m') }}
                                            @if ($s['fecha_fin'] !== $s['fecha_inicio'])
                                                – {{ \Illuminate\Support\Carbon::parse($s['fecha_fin'])->format('d/m') }}
                                            @endif
                                        </td>
                                        <td class="p-2 text-center">
                                            {{ $s['dias'] }}
                                            @if ($s['domingos'])
                                                <span class="text-muted-foreground" title="Domingos incluidos">(+{{ $s['domingos'] }} dom.)</span>
                                            @endif
                                        </td>
                                        <td class="p-2 text-center">
                                            @php
                                                $accion = [
                                                    'crear' => ['Nueva', 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'],
                                                    'extender_fin' => ['Extiende', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
                                                    'extender_inicio' => ['Extiende', 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'],
                                                    'unir' => ['Une dos', 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300'],
                                                ][$s['accion']];
                                            @endphp
                                            <span class="px-2 py-0.5 rounded {{ $accion[1] }}"
                                                title="Queda: {{ $s['rango_final'][0] }} a {{ $s['rango_final'][1] ?? 'sin fin' }}">{{ $accion[0] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-success>No hay suspensiones por registrar con los vínculos actuales.</x-success>
                @endif
            </x-card>

            {{-- PANEL 2: lo que el sistema no puede decidir --}}
            <x-card>
                <h3 class="font-semibold text-foreground">Por decidir</h3>
                <p class="text-xs text-muted-foreground mb-3">
                    Códigos de asistencia sin suspensión vinculada. El vínculo se guarda en el tipo de asistencia y
                    vale para todos los meses; al vincular, sus días pasan a sugerencias.
                </p>

                @forelse ($porDecidir as $p)
                    <div class="border border-border rounded p-2 mb-2" wire:key="dec-{{ $p['codigo'] }}">
                        <div class="text-sm">
                            <span class="font-mono font-semibold">{{ $p['codigo'] }}</span> {{ $p['descripcion'] }}
                            <span class="text-xs text-muted-foreground">· {{ $p['dias'] }} día(s), {{ $p['trabajadores'] }} trabajador(es)</span>
                        </div>
                        @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                            <div class="flex gap-2 mt-2">
                                <select wire:model="vinculos.{{ $p['codigo'] }}"
                                    class="flex-1 min-w-0 h-8 rounded-md border border-input bg-background text-foreground text-xs px-2">
                                    <option value="">— Elegir suspensión —</option>
                                    <option value="sin">No genera suspensión</option>
                                    @foreach ($listaSuspensiones as $t)
                                        <option value="{{ $t['id'] }}">{{ $t['label'] }}</option>
                                    @endforeach
                                </select>
                                <x-button size="sm" wire:click="vincularCodigo('{{ $p['codigo'] }}')">Vincular</x-button>
                            </div>
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-green-700 dark:text-green-400"><i class="fa fa-check"></i> Todos los códigos tienen vínculo.</p>
                @endforelse

                @if (count($conflictos))
                    <h4 class="font-semibold text-sm text-foreground mt-4">Conflictos ({{ count($conflictos) }})</h4>
                    <p class="text-xs text-muted-foreground mb-2">Días registrados con otra suspensión: corrígelos en la tabla de abajo o en el registro diario.</p>
                    <ul class="text-xs space-y-1 max-h-48 overflow-auto">
                        @foreach ($conflictos as $c)
                            <li>
                                {{ \Illuminate\Support\Carbon::parse($c['fecha'])->format('d/m') }} · {{ $c['trabajador'] }}:
                                <b>{{ $c['codigo'] }}</b> ({{ $c['esperado'] }}) pero tiene {{ $c['registrado'] }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    @endif

    @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_VER)
        <x-card wire:ignore>
            <div x-ref="tableContainer"></div>
        </x-card>
    @else
        <x-danger>
            No tienes permiso para ver las suspensiones de los trabajadores.
        </x-danger>
    @endcan

    @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
    @if ($mes && $anio)
        <div class="fixed bottom-6 right-6 z-40">
            <x-button @click="guardarRegistrosSuspensiones">
                <i class="fa fa-save"></i> Guardar Registros
            </x-button>
        </div>
    @endif
    @endif

    <x-loading wire:loading wire:target="aceptarSugerencias,vincularCodigo,mes,anio" />
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

                const rowIndex = this.hot.countRows() - 1; // índice donde irá la nueva fila

                // Insertar una fila vacía al final
                this.hot.alter('insert_row_below', rowIndex);

                // Asignar valores en cada celda
                nuevaFila.forEach((valor, colIndex) => {
                    console.log(valor, colIndex, rowIndex);
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

                renderer: function (instance, td, row, col, prop, value) {
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

                validator: function (value, callback) {
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

                renderer: function (instance, td, row, col, prop, value) {
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

                validator: function (value, callback) {
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