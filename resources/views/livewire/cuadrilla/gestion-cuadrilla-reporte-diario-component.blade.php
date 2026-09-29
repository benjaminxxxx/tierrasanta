<div x-data="actividades_diarias_cuadrilleros">

    <x-flex class="w-full justify-between">
        <x-flex class="my-3">
            <a href="{{ route('cuadrilla.panel') }}" class="font-bold text-lg">
                Gestión de cuadrilleros
            </a>
            <span>/</span>
            <x-h3>
                Registro Diario Cuadrilla
            </x-h3>
        </x-flex>
        <x-selector-dia wire:model.live="fecha" label="Seleccionar Fecha" class="w-auto" />
    </x-flex>

    <x-flex class="justify-center mb-4">
        @if ($tramos && $tramos->count() > 0)
            <x-select wire:model.live="tramoSeleccionadoId" class="w-full text-center lg:w-auto">
                @foreach ($tramos as $tramo)
                    <option value="{{ $tramo->id }}">Tramo: {{ $tramo->fecha_inicio }} - {{ $tramo->fecha_fin }}
                    </option>
                @endforeach
            </x-select>
        @endif
    </x-flex>
    @if ($tramos && $tramos->count() == 0)
        <x-steps-info title="No se ha registrado ningún tramo en esta fecha" class="mb-4">

            <p>Antes de registrar actividades, asegúrate de completar los tramos correspondientes.</p>

            <ul class="list-disc list-inside mt-2 space-y-1">
                <li>
                    Ir al módulo de registro semanal:
                    <a href="{{ route('cuadrilla.reporte_semanal') }}" target="_blank"
                        class="text-blue-700 dark:text-blue-300 underline">
                        abrir aquí
                    </a>.
                </li>

                <li>Crear un nuevo tramo con el rango de fechas correspondiente.</li>
                <li>Registrar grupos, cuadrilleros y sus horas trabajadas.</li>
                <li>Guardar la información y regresar a esta pantalla.</li>
            </ul>

        </x-steps-info>
    @endif
    <x-card>
        <div wire:ignore>
            @can(\App\Constants\Permisos::CUADRILLA_DIARIO_GESTIONAR)
            <x-flex class="justify-between mb-4">
                <x-h3>Detalle de trabajadores</x-h3>
                <x-flex class="lg:justify-end gap-3 space-y-2 md:space-y-0">
                    <x-button @click="agregarGrupo">
                        <i class="fa fa-plus"></i>
                    </x-button>
                    <x-button @click="quitarGrupo" variant="danger">
                        <i class="fa fa-minus"></i>
                    </x-button>
                </x-flex>
            </x-flex>
            @endcan

            <div x-ref="tableReporteContainer"></div>

        </div>
        @can(\App\Constants\Permisos::CUADRILLA_DIARIO_GESTIONAR)
            <x-inferior-derecha>
                <x-button @click="guardarReporteActividadDiaria">
                    <i class="fa fa-sync"></i> Actualizar manualmente
                </x-button>
            </x-inferior-derecha>
        @endcan

    </x-card>

    <livewire:cuadrilla.gestion-cuadrilla-reporte-diario-form-component />

    <x-loading wire:loading />
</div>
@script
<script>
    Alpine.data('actividades_diarias_cuadrilleros', () => ({
        listeners: [],
        ingresos: [],
        seleccionados: [],
        totalVenta: '0.00',
        fechaVenta: null,
        selectedRows: [],
        trabajadores: @json($trabajadores),
        totalColumnas: @json($totalColumnas),
        labores: @json($labores),
        campos: @json($campos),
        hasUnsavedChanges: false,
        hot: null,
        hotFuente: null,
        isDark: JSON.parse(localStorage.getItem('darkMode')),
        init() {
            if (!this.horarios || this.horarios.length === 0) {
                this.horarios = [{
                    inicio: '',
                    fin: '',
                    horas: 0
                }];
            }
            this.$nextTick(() => {
                this.initTable();
            });
            Livewire.on('actualizarTablaCuadrilleros', (data) => {

                this.trabajadores = data[0];
                this.totalColumnas = data[1];
                this.$nextTick(() => {
                    this.initTable();
                });
            });
            $watch('darkMode', value => {

                this.isDark = value;
                const columns = this.generarColumnasDinamicas();
                this.hot.updateSettings({
                    themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                    columns: columns
                });
                this.ajustarAncho();

            });
        },
        initTable() {

            if (this.hot != null) {
                this.hot.destroy();
                this.hot = null;
            }

            const tareas = this.tareas;

            const container = this.$refs.tableReporteContainer;
            const hot = new Handsontable(container, {
                data: this.trabajadores,
                colHeaders: true,
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                rowHeaders: true,
                columns: this.generarColumnasDinamicas(),
                // Sin scroll propio: la tabla toma el ancho de todas sus columnas (ajustarAncho) y
                // se mueve con el scroll horizontal de la página, pegado a la base.
                width: '100%',
                height: 'auto',
                renderAllColumns: true,
                manualColumnResize: false,
                manualRowResize: true,
                autoColumnSize: true,
                afterChange: (changes, source) => {
                    if (source === 'recalculado' || source === 'loadData') {
                        return; // evitar loops infinitos
                    }

                    if (source === 'edit' ||
                        source === 'CopyPaste.paste' ||
                        source === 'timeValidator' ||
                        source === 'Autofill.fill') {

                        this.hasUnsavedChanges = true;

                        const filasMap = new Map();
                        changes.forEach(([row]) => {

                            if (!filasMap.has(row)) {
                                filasMap.set(row, this.hot.getDataAtRow(row));
                            }
                        });
                        filasMap.forEach((data, row) => {
                            this.recalcularTotales(data, row);
                        });

                    }
                },
                licenseKey: 'non-commercial-and-evaluation',
            });

            this.hot = hot;
            this.hot.render();
            this.ajustarAncho();

            window.addEventListener('beforeunload', (event) => {
                if (this.hasUnsavedChanges) {
                    const confirmationMessage =
                        'Tienes cambios no guardados. ¿Estás seguro de que deseas salir?';
                    event.returnValue = confirmationMessage; // Mostrar el mensaje de advertencia
                    return confirmationMessage;
                }
            });
        },
        /**
         * Ancho de la tabla = suma de sus columnas (+ encabezado de filas). Así Handsontable no
         * crea scroll horizontal propio y la tabla desborda la tarjeta: la mueve el scroll de la página.
         */
        ajustarAncho() {
            requestAnimationFrame(() => {
                if (!this.hot) return;
                let ancho = 0;
                for (let c = 0; c < this.hot.countCols(); c++) {
                    ancho += this.hot.getColWidth(c) || 0;
                }
                const encabezadoFilas = this.hot.rootElement.querySelector('.ht_clone_inline_start .htCore th, .ht_clone_left .htCore th');
                ancho += encabezadoFilas ? encabezadoFilas.offsetWidth : 50;
                this.hot.updateSettings({ width: Math.ceil(ancho) + 2 });
            });
        },
        agregarGrupo() {
            this.totalColumnas++;
            const columns = this.generarColumnasDinamicas();
            this.hot.updateSettings({
                columns: columns
            });
            this.ajustarAncho();
        },
        quitarGrupo() {
            this.totalColumnas--;
            const columns = this.generarColumnasDinamicas();
            this.hot.updateSettings({
                columns: columns
            });
            this.ajustarAncho();
        },
        /**
         * Minutos del detalle de una fila (suma de cada hora_inicio_N → hora_fin_N válida).
         * Por nombre de propiedad, no por posición de columna: así se pueden agregar columnas.
         */
        minutosDetalleFila(fila) {
            let total = 0;
            for (let n = 1; n <= this.totalColumnas; n++) {
                const inicio = fila[`hora_inicio_${n}`];
                const fin = fila[`hora_fin_${n}`];
                if (!inicio || !fin) continue;
                const minutos = this.timeToMinutes(fin) - this.timeToMinutes(inicio);
                if (minutos > 0) total += minutos;
            }
            return total;
        },
        /** ¿La fila cuadra con las horas registradas del día? null si no hay registro. */
        detalleCuadra(fila) {
            const registrado = parseFloat(fila.total_horas_registrado);
            if (isNaN(registrado)) return null;
            return Math.abs(this.minutosDetalleFila(fila) - registrado * 60) <= 1;
        },
        recalcularTotales(data, row) {
            const fila = this.hot.getSourceDataAtRow(row) || {};
            const totalHoras = this.minutesToTime(this.minutosDetalleFila(fila));
            this.hot.setDataAtRowProp(row, 'total_horas', totalHoras, 'recalculado');
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
        generarColumnasDinamicas() {
            const cols = [{
                data: 'codigo_grupo',
                type: 'text',
                readOnly: true,
                className: '!bg-muted',
                title: 'GRUPO'
            },
            {
                data: 'cuadrillero_nombres',
                type: 'text',
                readOnly: true,
                className: '!bg-muted',
                title: 'Trabajador'
            }
            ];
            const classBgDark = this.isDark ? '!bg-blue-600' : '!bg-blue-300';
            // Agregar columnas dinámicas para cada actividad
            for (let i = 1; i <= this.totalColumnas; i++) {

                const bgClass = (i % 2 === 0) ? '' : classBgDark;

                cols.push({
                    data: `campo_${i}`,
                    type: 'dropdown',
                    source: this.campos,
                    strict: true, // solo permite valores de la lista
                    allowInvalid: false,
                    className: `text-center ${bgClass}`,
                    title: `Camp. ${i}`
                }, {
                    data: `labor_${i}`,
                    type: 'dropdown',
                    source: this.labores,
                    strict: true, // solo permite valores de la lista
                    allowInvalid: true,
                    className: `text-center ${bgClass}`,
                    title: `Lab. ${i}`
                }, {
                    data: `hora_inicio_${i}`,
                    type: 'time',
                    width: 60,
                    timeFormat: 'H.mm',
                    correctFormat: true,
                    allowInvalid: false,
                    strict: true,
                    className: `text-center ${bgClass}`,
                    title: `Hora<br/>Ini. ${i}`
                }, {
                    data: `hora_fin_${i}`,
                    type: 'time',
                    width: 60,
                    timeFormat: 'H.mm',
                    correctFormat: true,
                    allowInvalid: false,
                    strict: true,
                    className: `text-center ${bgClass}`,
                    title: `Hora<br/>Fin ${i}`
                });
            }

            cols.push({
                data: 'total_horas',
                type: 'time',
                width: 60,
                timeFormat: 'HH.mm',
                correctFormat: true,
                readOnly: true,
                className: this.isDark ? '!font-bold !bg-stone-700 !text-center' :
                    '!font-bold !bg-yellow-300 !text-center',
                title: 'TOTAL',
                // Rojo = la suma del detalle (esta fila) no coincide con las horas registradas del día.
                // Se calcula desde las horas de la fila, así también avisa mientras se edita.
                renderer: (instance, td, row, col, prop, value, cellProperties) => {
                    // Renderer base del tipo "time" (mantiene el formato HH.mm de la columna)
                    Handsontable.renderers.getRenderer('time')(instance, td, row, col, prop, value, cellProperties);
                    const fila = instance.getSourceDataAtRow(row) || {};
                    td.style.setProperty('color', '', '');
                    if (this.detalleCuadra(fila) === false) {
                        td.style.setProperty('color', '#dc2626', 'important');
                    }
                }
            });

            // DEBE: "OK" (verde) si el detalle coincide con las horas registradas del día;
            // si no, la hora a la que debe llegar el detalle. Solo lectura y no se guarda
            // (guardarReporteActividadDiaria arma cada fila con sus propias claves).
            cols.push({
                data: 'total_horas_registrado',
                readOnly: true,
                width: 60,
                title: 'DEBE',
                className: '!text-center !font-bold',
                renderer: (instance, td, row, col, prop, value, cellProperties) => {
                    const fila = instance.getSourceDataAtRow(row) || {};
                    const cuadra = this.detalleCuadra(fila);
                    const texto = cuadra === null ? '' : (cuadra ? 'OK' : this.minutesToTime(Math.round(parseFloat(fila.total_horas_registrado) * 60)));
                    Handsontable.renderers.TextRenderer(instance, td, row, col, prop, texto, cellProperties);
                    td.style.setProperty('color', cuadra ? '#16a34a' : '#dc2626', 'important');
                    td.title = cuadra === false
                        ? `Registradas: ${fila.total_horas_registrado} h — el detalle debe sumar ${texto}`
                        : '';
                }
            });

            return cols;
        },
        guardarReporteActividadDiaria() {
            let allData = [];

            // Recorre todas las filas de la tabla y obtiene los datos completos
            for (let row = 0; row < this.hot.countRows(); row++) {
                const rowData = this.hot.getSourceDataAtRow(row);

                // Crear una copia limpia con solo columnas activas
                let cleanedRow = {
                    codigo_grupo: rowData.codigo_grupo ?? null,
                    cuadrillero_id: rowData.cuadrillero_id ?? null,
                    cuadrillero_nombres: rowData.cuadrillero_nombres ?? '',
                    cuadrillero_dni: rowData.cuadrillero_dni ?? null,
                    asistencia: rowData.asistencia ?? true,
                };

                for (let i = 1; i <= this.totalColumnas; i++) {
                    cleanedRow[`campo_${i}`] = rowData[`campo_${i}`] ?? null;
                    cleanedRow[`labor_${i}`] = rowData[`labor_${i}`] ?? null;
                    cleanedRow[`hora_inicio_${i}`] = rowData[`hora_inicio_${i}`] ?? null;
                    cleanedRow[`hora_fin_${i}`] = rowData[`hora_fin_${i}`] ?? null;
                }

                allData.push(cleanedRow);
            }

            // Filtrar filas vacías
            const filteredData = allData.filter(row => {
                const tramos = Array.from({
                    length: this.totalColumnas
                }).some((_, i) => {
                    return row[`hora_inicio_${i + 1}`] || row[`hora_fin_${i + 1}`] || row[
                        `labor_${i + 1}`];
                });
                return row.cuadrillero_nombres !== '' || tramos;
            });


            $wire.storeTableDataGuardarActividadDiaria(filteredData);
            this.hasUnsavedChanges = false;
        }

    }));
</script>
@endscript