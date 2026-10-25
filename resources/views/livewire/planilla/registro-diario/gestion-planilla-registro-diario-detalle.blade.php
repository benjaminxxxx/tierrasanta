<div x-data="registroDiarioActividades">
    @if ($sinLista)
        <div class="mt-4 rounded-[10px] border border-mist bg-paper px-4 py-3 text-sm text-graphite">
            <i class="fa fa-circle-info text-blue-700 dark:text-blue-400"></i>
            Este mes todavía no tiene lista: no hay contratados en planilla agraria o el mes aún no empieza. Se crea sola al
            haber contratados (con el orden del mes anterior).
        </div>
    @endif

    {{-- Aviso: vacaciones, descansos y permisos que cubren este día (solo se ve en los días del rango) --}}
    @if (count($ausencias))
        @php
            $estadosAusencia = [
                'registrado' => ['Registrado', 'text-green-700 bg-green-50 dark:text-green-400 dark:bg-green-400/10', 'fa-check'],
                'sin_registro' => ['Sin registrar', 'text-amber-700 bg-amber-50 dark:text-amber-400 dark:bg-amber-400/10', 'fa-clock'],
                'conflicto' => ['Registrado como', 'text-red-700 bg-red-50 dark:text-red-400 dark:bg-red-400/10', 'fa-triangle-exclamation'],
            ];
            $fmt = fn($f) => $f ? \Carbon\Carbon::parse($f)->format('d/m/Y') : 'sin fin';
        @endphp
        <div class="mt-4 rounded-[10px] border border-mist bg-paper font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]"
            x-data="{ abierto: true }">
            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
                <span class="text-sm font-semibold text-ink">
                    <i class="fa fa-umbrella-beach text-pewter"></i>
                    Ausencias programadas este día · {{ count($ausencias) }}
                </span>
                <span class="text-xs text-pewter">
                    Vacaciones, descansos y permisos que cubren el
                    {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                    <i class="fa ml-1" :class="abierto ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </span>
            </button>
            <div x-show="abierto" x-collapse class="border-t border-hairline">
                <ul class="divide-y divide-hairline text-[13px]">
                    @foreach ($ausencias as $a)
                        @php [$txt, $cls, $ico] = $estadosAusencia[$a['estado']]; @endphp
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-2" wire:key="aus-{{ $a['suspension_id'] }}">
                            <span class="w-64 font-medium text-ink">{{ $a['nombre'] }}</span>
                            <span class="flex items-center gap-2 text-graphite">
                                <span class="inline-block h-2.5 w-2.5 rounded-full" style="background: {{ $a['color'] }}"></span>
                                {{ $a['codigo_esperado'] ?? $a['codigo'] }} · {{ \Illuminate\Support\Str::limit($a['descripcion'], 45) }}
                            </span>
                            <span class="tabular-nums text-graphite">
                                del {{ $fmt($a['desde']) }} al {{ $fmt($a['hasta']) }}
                                <span class="text-pewter">· día {{ $a['dia'] }} de {{ $a['dias'] }}</span>
                            </span>
                            <span class="rounded-[6px] px-2 py-0.5 text-xs font-medium {{ $cls }}">
                                <i class="fa {{ $ico }}"></i> {{ $txt }}{{ $a['estado'] === 'conflicto' ? ' ' . $a['asistencia'] : '' }}
                            </span>
                            @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                                <button type="button" wire:click="regresoAnticipado({{ $a['suspension_id'] }})"
                                    wire:confirm="{{ $a['nombre'] }} regresó hoy: su {{ mb_strtolower($a['descripcion'] ?? 'suspensión') }} {{ $a['dia'] > 1 ? 'terminará el ' . \Carbon\Carbon::parse($fecha)->subDay()->format('d/m/Y') : 'se eliminará' }} y se quitarán sus {{ $a['codigo_esperado'] ?? '' }} desde hoy hasta el {{ $fmt($a['hasta']) }}. ¿Continuar?"
                                    class="ml-auto inline-flex h-8 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-xs font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25"
                                    title="Termina la suspensión ayer y quita del registro diario los días que sobran">
                                    <i class="fa fa-person-walking-arrow-loop-left"></i> Regresó hoy
                                </button>
                            @endcan
                        </li>
                    @endforeach
                </ul>
                <p class="border-t border-hairline px-4 py-2 text-xs text-pewter">
                    Al programar una suspensión en Permisos y suspensiones, sus días se marcan solos aquí. Si regresa antes,
                    usa "Regresó hoy" (o acorta el rango allá) y los días que sobran se quitan.
                </p>
            </div>
        </div>
    @endif

    <x-card class="mt-4">

        <x-flex class="justify-between">
            <x-flex>
                <x-input x-ref="filterNombre" placeholder="Buscar por nombre..." @input="aplicarFiltros"
                    class="w-auto" />

                <x-select x-ref="filterAsistencia" @change="aplicarFiltros" class="w-auto">
                    <option value="">Todas las asistencias</option>
                    <template x-for="cod in tipoAsistenciasCodigos">
                        <option :value="cod" x-text="cod"></option>
                    </template>
                </x-select>
            </x-flex>
            @can(\App\Constants\Permisos::PLANILLA_ACTIVIDAD_GESTIONAR)
                <x-flex>
                    <x-button @click="agregarTramo">
                        <i class="fa fa-plus"></i> Agregar tramo
                    </x-button>
                    <x-button @click="quitarTramo" variant="danger">
                        <i class="fa fa-minus"></i> Quitar tramo
                    </x-button>
                </x-flex>
            @endcan
        </x-flex>
        <div wire:ignore>
            <div x-ref="tableContainer" class="mt-5"></div>
        </div>
        @can(\App\Constants\Permisos::PLANILLA_ACTIVIDAD_GESTIONAR)
            <div class="fixed bottom-6 right-6 z-40">
                <x-button @click="enviarRegistrosDiariosPlanilla" class="flex items-center gap-2 shadow-lg">
                    <i class="fa fa-save"></i>
                    Guardar Información
                </x-button>
            </div>
        @endcan
    </x-card>

    <x-card class="mt-5 max-w-lg">
        <div class="space-y-6">
            {{-- Lista de Totales --}}
            @if ($resumenDiarioPlanilla?->totales?->count() > 0)
                <div class="space-y-2">
                    @foreach ($resumenDiarioPlanilla->totales as $resumenTotal)
                        <x-resumen-item :label="'TOTAL ' . $resumenTotal->descripcion"
                            :value="$resumenTotal->total_asistidos" />
                    @endforeach
                </div>
            @endif

            {{-- Grid de Cards de Impacto --}}
            @if ($resumenDiarioPlanilla)
                <div class="grid grid-cols-2 gap-4 pt-4">

                    <x-card-resumen variant="blue" label="Cuadrillas" :value="$resumenDiarioPlanilla->total_cuadrillas" />

                    <x-card-resumen variant="emerald" label="Total Planilla" :value="$resumenDiarioPlanilla->total_planilla"
                        x-ref="total_planilla" />

                </div>
            @endif
        </div>
    </x-card>
    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('registroDiarioActividades', () => ({
        listeners: [],
        tableData: @json($empleados),
        totales: null,
        hot: null,
        totalActividades: @entangle('totalActividades'),
        campos: @json($campos),
        tipoAsistenciasHoras: @json($tipoAsistenciasHoras),
        tipoAsistenciasCodigos: @json($tipoAsistenciasCodigos),
        hasUnsavedChanges: @entangle('hasUnsavedChanges'),
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        totalHorasManuales: new Set(),
        modifiedRowIndexes: @entangle('modifiedRowIndexes'),
        init() {
            Livewire.on('setEmpleados', (data) => {
                let empleados = data[0];
                this.tableData = empleados;
                this.hot.loadData(this.tableData);
            })
            this.initTable();
            $watch('darkMode', value => {

                this.isDark = value;
                const columns = this.generateColumns(this.totalActividades);
                this.hot.updateSettings({
                    themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                    columns: columns
                });
            });
        },

        initTable() {
            if (this.hot) {
                try {
                    this.hot.destroy();
                } catch (e) { }
                this.hot = null;
            }
            const totalActividades = this.totalActividades;
            const columns = this.generateColumns(totalActividades);
            let primeraCarga = 0;
            let isUpdating = false;

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
                autoColumnSize: true,
                autoRowSize: true,
                filters: true,
                dropdownMenu: false,
                fixedColumnsLeft: 3,
                licenseKey: 'non-commercial-and-evaluation',
                cells: function (row, col) {
                    const cellProperties = {};
                    // 'this' aquí se refiere a la instancia de Handsontable
                    const rowData = this.instance.getSourceDataAtRow(row);

                    if (rowData && rowData.numero_cuadrilleros && rowData.numero_cuadrilleros >
                        0) {
                        cellProperties.readOnly = true;

                        // Necesitas acceder a isDark desde el scope externo
                        const isDark = container.closest('[x-data]')?.__x?.$data?.isDark ||
                            false;

                        cellProperties.className = '!bg-muted !text-center';
                    }

                    // === NUEVA LÓGICA PARA total_horas ===
                    // solo aplica en la columna total_horas
                    const colProp = this.instance.colToProp(col);

                    if (colProp === 'total_horas') {
                        if (['A', 'LSG', 'F'].includes(rowData.asistencia) || rowData.nombres == 'CUADRILLA') {
                            // Cuando asistencia es A, LSG o F → readonly
                            cellProperties.readOnly = true;
                            cellProperties.className = '!bg-muted !text-center font-bold';
                        } else {
                            // En los demás casos → editable
                            cellProperties.readOnly = false;
                            cellProperties.className = '!text-center font-bold';
                        }
                    }

                    return cellProperties;
                },
                afterChange: (changes, source) => {
                    if (source === 'recalculado' || source === 'loadData') return;

                    changes.forEach(([row]) => {

                        const physicalRow = this.hot.toPhysicalRow(row);
                        if (!this.modifiedRowIndexes.includes(physicalRow)) {
                            this.modifiedRowIndexes.push(physicalRow);
                        }
                    });

                    if (['edit', 'CopyPaste.paste', 'timeValidator', 'Autofill.fill'].includes(
                        source)) {
                        this.hasUnsavedChanges = true;

                        const filasMap = new Map();

                        changes.forEach(([row, prop, oldVal, newVal]) => {

                            if (prop === 'total_horas') {
                                const physicalRow = this.hot.toPhysicalRow(row);
                                this.totalHorasManuales.add(physicalRow);
                                return;
                            }
                            if (prop === 'asistencia') {
                                const physicalRow = this.hot.toPhysicalRow(row);
                                this.totalHorasManuales.delete(physicalRow);
                            }
                            if (!filasMap.has(row)) {
                                filasMap.set(row, this.hot.getDataAtRow(row));
                            }
                        });

                        filasMap.forEach((data, row) => {
                            this.recalcularTotales(data, row);
                        });
                    }
                }
            });

            this.hot = hot;
            this.hot.render();

            window.addEventListener('beforeunload', (event) => {
                if (this.hasUnsavedChanges) {
                    const confirmationMessage =
                        'Tienes cambios no guardados. ¿Estás seguro de que deseas salir?';
                    event.returnValue = confirmationMessage; // Mostrar el mensaje de advertencia
                    return confirmationMessage;
                }
            });
        },
        aplicarFiltros() {
            const nombreQuery = this.$refs.filterNombre.value.toLowerCase();
            const asistenciaQuery = this.$refs.filterAsistencia.value;

            const filtersPlugin = this.hot.getPlugin('filters');

            // Limpiamos filtros previos
            filtersPlugin.clearConditions();

            // 1. Filtro de Nombre (Columna 0 usualmente)
            if (nombreQuery) {
                filtersPlugin.addCondition(0, 'contains', [nombreQuery]);
            }

            // 2. Filtro de Asistencia (Ajusta el índice según tu columna de asistencia)
            // Si 'asistencia' es la columna 2:
            if (asistenciaQuery) {
                console.log(asistenciaQuery);
                filtersPlugin.addCondition(1, 'eq', [asistenciaQuery]);
            }

            filtersPlugin.filter();
        },
        timeToMinutes(time) {
            if (!time || typeof time !== 'string') return 0;

            // Reemplazamos dos puntos por punto por si acaso viene en formato HH:mm
            const limpio = time.replace(':', '.');
            const partes = limpio.split('.');

            const hours = parseInt(partes[0], 10) || 0;
            const minutes = parseInt(partes[1], 10) || 0;

            // Validamos que sean números finitos
            if (isNaN(hours) || isNaN(minutes)) return 0;

            return (hours * 60) + minutes;
        },

        minutesToTime(minutes) {
            // Si por algún error llega NaN o Infinite, devolvemos 00.00
            if (isNaN(minutes) || !isFinite(minutes) || minutes < 0) {
                return "00.00";
            }

            const hours = Math.floor(minutes / 60);
            const mins = Math.round(minutes % 60); // Usamos round para evitar decimales en los minutos

            return `${String(hours).padStart(2, '0')}.${String(mins).padStart(2, '0')}`;
        },
        agregarTramo() {
            this.totalActividades++;
            const columns = this.generateColumns(this.totalActividades);
            this.hot.updateSettings({
                columns: columns
            });
        },
        quitarTramo() {
            if (this.totalActividades == 1) {
                return;
            }
            this.totalActividades--;
            const columns = this.generateColumns(this.totalActividades);
            this.hot.updateSettings({
                columns: columns
            });
        },
        generateColumns(totalActividades) {

            const bgHeader = '!bg-muted cursor-not-allowed';


            let columns = [{
                data: "nombres",
                type: 'text',
                readOnly: true,
                className: bgHeader,
                title: 'APELLIDOS Y NOMBRES'
            },
            {
                data: "asistencia",
                type: 'dropdown',
                source: this.tipoAsistenciasCodigos,
                strict: true,
                width: 60,
                title: 'ASIST.',
                className: `text-center`
            },
            {
                data: 'numero_cuadrilleros',
                type: 'numeric',
                width: 50,
                title: 'N° C.',
                readOnly: true,
                className: `text-center ${bgHeader}`
            }
            ];

            for (let indice = 1; indice <= totalActividades; indice++) {
                const intercalado = this.isDark ?
                    (indice % 2 === 0 ? '!bg-neutral-900' : '') :
                    (indice % 2 === 0 ? '!bg-blue-100' : '');

                columns.push({
                    data: "campo_" + indice,
                    type: 'dropdown',
                    width: 50,
                    className: `text-center ${intercalado}`,
                    source: this.campos,
                    title: `CAM. ${indice}`
                }, {
                    data: "labor_" + indice,
                    type: 'text',
                    width: 40,
                    className: `text-center ${intercalado}`,
                    title: `LAB. ${indice}`
                }, {
                    data: "entrada_" + indice,
                    type: 'hora24',
                    width: 50,
                    className: `text-center ${intercalado}`,
                    title: `ENT. ${indice}`
                }, {
                    data: "salida_" + indice,
                    type: 'hora24',
                    width: 50,
                    className: `text-center ${intercalado}`,
                    title: `SAL. ${indice}`
                });
            }

            // Agregar columna final de TOTAL
            columns.push({
                data: 'total_horas',
                width: 70,
                type: 'numeric',
                readOnly: true,
                title: 'TOTAL',
                className: '!text-center font-bold text-lg',
                renderer: function (hotInstance, td, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer.apply(this,
                        arguments); // Render por defecto

                    // Aplicar estilos condicionales
                    if (value === '00:00' || value === '0' || value === '0.00' || value ===
                        '00.00' || value === null) {
                        // Valor es 0 o nulo -> color rojo
                        td.classList.add('!text-red-600', 'font-bold');
                    } else {
                        // Valor diferente a 0 -> color verde
                        td.classList.add('!text-green-600', 'font-bold');
                    }
                }
            }, {
                data: 'total_bono',
                width: 70,
                type: 'numeric',
                numericFormat: {
                    pattern: '##,##0.00', // Formato de miles con al menos dos decimales
                    culture: 'en-US' // Cultura para usar coma como separador de miles y punto para decimales
                },
                readOnly: true,
                title: 'BONO',
                className: `text-center font-bold text-lg !bg-gray-100 ${bgHeader}`,
                renderer: function (hotInstance, td, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer.apply(this,
                        arguments); // Render por defecto


                    td.classList.remove('htDimmed');
                    // Aplicar estilos condicionales
                    if (value > 0) {
                        // Valor es 0 o nulo -> color rojo
                        td.classList.add('!text-green-600', 'font-bold');
                    } else {
                        // Valor diferente a 0 -> color verde
                        td.classList.add('!text-red-600', 'font-bold');
                    }

                    if (typeof value === 'number') {
                        td.innerHTML = new Intl.NumberFormat('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }).format(value);
                    }
                }
            });

            return columns;
        },
        recalcularTotales(data, row) {

            if (this.totalHorasManuales.has(row)) return;

            const indiceTotal = data.length - 2;
            const tipoAsistencia = data[1]; // antes era data[2]

            if (tipoAsistencia !== 'A' && tipoAsistencia !== '' && tipoAsistencia !== null) {
                const totalHoras = this.minutesToTime(this.tipoAsistenciasHoras[tipoAsistencia] * 60);
                this.hot.setDataAtCell(row, indiceTotal, totalHoras, 'recalculado');
                return;
            }

            let totalMinutos = 0;

            // Empieza desde el índice 3, recorre de 4 en 4 hasta antes de los 2 últimos
            for (let i = 3; i <= data.length - 6; i += 4) {
                const dias = parseFloat(data[i]);
                const cantidad = parseFloat(data[i + 1]);
                const horaInicio = data[i + 2];
                const horaFin = data[i + 3];

                if (horaInicio == null || horaFin == null || horaInicio == '' || horaFin == '') {
                    continue;
                }

                const inicioMin = this.timeToMinutes(horaInicio);
                const finMin = this.timeToMinutes(horaFin);

                if (finMin <= inicioMin) {
                    continue;
                }

                const minutos = finMin - inicioMin;
                totalMinutos += minutos;
            }

            const totalHoras = this.minutesToTime(totalMinutos);
            this.hot.setDataAtCell(row, indiceTotal, totalHoras, 'recalculado');
        },

        enviarRegistrosDiariosPlanilla() {
            const datos = this.hot.getSourceData();
            const resultados = [];

            this.modifiedRowIndexes.forEach((rowIndex) => {
                const fila = datos[rowIndex];
                if (fila) {
                    resultados.push(fila);
                }
            });

            $wire.guardarInformacionRegistroPlanilla(resultados);
        }

    }))
</script>
@endscript