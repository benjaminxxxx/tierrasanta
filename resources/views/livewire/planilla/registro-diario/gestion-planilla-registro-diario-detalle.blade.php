<div x-data="registroDiarioActividades">
    @if ($sinLista)
        <div class="mt-4 rounded-[10px] border border-mist bg-paper px-4 py-3 text-sm text-graphite">
            <i class="fa fa-circle-info text-blue-700 dark:text-blue-400"></i>
            Este mes todavía no tiene lista: no hay contratados en planilla agraria o el mes aún no empieza. Se crea sola al
            haber contratados (con el orden del mes anterior).
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

                {{-- Ausencias del día (vacaciones, DM, licencias…): un botón compacto; la lista se abre encima de la tabla --}}
                @if (count($ausencias))
                    @php
                        $estadosAusencia = [
                            'registrado' => ['Registrado', 'text-green-700 bg-green-50 dark:text-green-400 dark:bg-green-400/10', 'fa-check'],
                            'sin_registro' => ['Sin registrar', 'text-amber-700 bg-amber-50 dark:text-amber-400 dark:bg-amber-400/10', 'fa-clock'],
                            'conflicto' => ['Registrado como', 'text-red-700 bg-red-50 dark:text-red-400 dark:bg-red-400/10', 'fa-triangle-exclamation'],
                        ];
                        $fmt = fn($f) => $f ? \Carbon\Carbon::parse($f)->format('d/m/Y') : 'sin fin';
                        $conAviso = collect($ausencias)->where('estado', '!=', 'registrado')->count();
                    @endphp
                    <div class="relative font-['Inter',ui-sans-serif,system-ui,sans-serif]" x-data="{ abierto: false }" @keydown.escape.window="abierto = false">
                        <button type="button" @click="abierto = !abierto"
                            class="inline-flex h-9 items-center gap-2 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25"
                            title="Vacaciones, descansos y permisos que cubren este día">
                            <i class="fa fa-umbrella-beach text-pewter"></i>
                            Ausencias <span class="tabular-nums text-graphite">{{ count($ausencias) }}</span>
                            @if ($conAviso)
                                <span class="rounded-[6px] bg-amber-50 px-1.5 text-xs text-amber-700 dark:bg-amber-400/10 dark:text-amber-400" title="Sin registrar o con otro código">{{ $conAviso }} por revisar</span>
                            @endif
                            <i class="fa fa-chevron-down text-[10px] text-steel transition-transform" :class="abierto && 'rotate-180'"></i>
                        </button>
                        <div x-show="abierto" x-cloak x-transition.origin.top.left @click.outside="abierto = false"
                            class="absolute left-0 top-full z-[300] mt-1 w-[min(46rem,calc(100vw-2rem))] rounded-[10px] border border-mist bg-paper shadow-[0_8px_24px_rgb(16_33_45/0.12)]">
                            <ul class="max-h-80 divide-y divide-hairline overflow-y-auto text-[13px]">
                                @foreach ($ausencias as $a)
                                    @php [$txt, $cls, $ico] = $estadosAusencia[$a['estado']]; @endphp
                                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-3 py-2" wire:key="aus-{{ $a['suspension_id'] }}">
                                        <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $a['nombre'] }}</span>
                                        <span class="flex items-center gap-1.5 text-graphite" title="{{ $a['descripcion'] }}">
                                            <span class="inline-block h-2 w-2 rounded-full" style="background: {{ $a['color'] }}"></span>
                                            {{ $a['codigo_esperado'] ?? $a['codigo'] }}
                                        </span>
                                        <span class="tabular-nums text-graphite">{{ $fmt($a['desde']) }} – {{ $fmt($a['hasta']) }} <span class="text-pewter">· día {{ $a['dia'] }}/{{ $a['dias'] }}</span></span>
                                        <span class="rounded-[6px] px-1.5 py-0.5 text-xs font-medium {{ $cls }}">
                                            <i class="fa {{ $ico }}"></i> {{ $txt }}{{ $a['estado'] === 'conflicto' ? ' ' . $a['asistencia'] : '' }}
                                        </span>
                                        @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                                            {{-- Recarga la tabla: primero se resuelven los cambios sin guardar --}}
                                            <button type="button"
                                                @click="abierto = false; registroDiarioGuardia(() => { if (confirm(@js($a['nombre'] . ' regresó hoy: su ' . mb_strtolower($a['descripcion'] ?? 'suspensión') . ' ' . ($a['dia'] > 1 ? 'terminará el ' . \Carbon\Carbon::parse($fecha)->subDay()->format('d/m/Y') : 'se eliminará') . ' y se quitarán sus ' . ($a['codigo_esperado'] ?? '') . ' desde hoy hasta el ' . $fmt($a['hasta']) . '. ¿Continuar?'))) $wire.regresoAnticipado({{ $a['suspension_id'] }}) })"
                                                class="inline-flex h-7 items-center gap-1 rounded-[10px] border border-mist bg-paper px-2 text-xs font-medium text-ink hover:bg-fog"
                                                title="Termina la suspensión ayer y quita del registro diario los días que sobran">
                                                <i class="fa fa-person-walking-arrow-loop-left"></i> Regresó hoy
                                            </button>
                                        @endcan
                                    </li>
                                @endforeach
                            </ul>
                            <p class="border-t border-hairline px-3 py-2 text-xs text-pewter">
                                Sus días se marcan solos. Si regresó antes, usa "Regresó hoy" o escribe su asistencia en la tabla y al guardar se te preguntará.
                            </p>
                        </div>
                    </div>
                @endif
            </x-flex>
            @can(\App\Constants\Permisos::PLANILLA_ACTIVIDAD_GESTIONAR)
                <x-flex>
                    <span class="text-sm text-graphite tabular-nums" x-text="totalActividades + (totalActividades === 1 ? ' tramo' : ' tramos')"></span>
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
            <div class="fixed bottom-6 right-6 z-40 flex items-center gap-3">
                {{-- Como en Excel se edita libremente, pero nada queda en la base hasta guardar --}}
                <span x-show="pendiente" x-cloak
                    class="rounded-[6px] bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 shadow dark:bg-amber-400/10 dark:text-amber-400">
                    <i class="fa fa-circle-exclamation"></i> Cambios sin guardar
                    <span x-show="filasModificadas.size" x-text="'(' + filasModificadas.size + ' fila' + (filasModificadas.size === 1 ? '' : 's') + ')'"></span>
                </span>
                <x-button @click="guardar()" class="flex items-center gap-2 shadow-lg" ::disabled="guardando">
                    <i class="fa" :class="guardando ? 'fa-spinner fa-spin' : 'fa-save'"></i>
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

    {{-- Cambios sin guardar: antes de cambiar de día, recargar la lista, exportar… --}}
    <div x-show="guardia.abierta" x-cloak class="fixed inset-0 z-[10001] flex items-center justify-center bg-black/40 p-4"
        @keydown.escape.window="guardia.abierta && cancelarGuardia()" @click.self="cancelarGuardia()">
        <div class="w-full max-w-md rounded-[16px] border border-mist bg-paper p-6 shadow-[0_8px_24px_rgb(16_33_45/0.12)] font-['Inter',ui-sans-serif,system-ui,sans-serif]">
            <h3 class="text-lg font-semibold text-ink">Hay cambios sin guardar</h3>
            <p class="mt-2 text-sm text-graphite">
                Modificaste la tabla y todavía no se guardó en el sistema
                (<span x-text="filasModificadas.size"></span> fila(s)<span x-show="columnasCambiaron"> y las columnas de tramos</span>).
                Si continúas sin guardar, esos cambios se pierden.
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button type="button" @click="cancelarGuardia()"
                    class="inline-flex h-9 items-center rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog">
                    Seguir editando
                </button>
                <button type="button" @click="descartarYContinuar()"
                    class="inline-flex h-9 items-center rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-red-700 hover:bg-fog dark:text-red-400">
                    Descartar cambios
                </button>
                @can(\App\Constants\Permisos::PLANILLA_ACTIVIDAD_GESTIONAR)
                    <button type="button" @click="guardarYContinuar()" :disabled="guardando"
                        class="inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover disabled:opacity-50">
                        <i class="fa" :class="guardando ? 'fa-spinner fa-spin' : 'fa-save'"></i> Guardar y continuar
                    </button>
                @endcan
            </div>
        </div>
    </div>
    {{-- ¿Hasta cuándo? Al guardar un código de suspensión (V, DM, LSG…) se pregunta el fin, para marcar solos los días siguientes --}}
    <x-dialog-modal wire:model.live="mostrarPreguntaHasta" maxWidth="2xl">
        <x-slot name="title">¿Hasta cuándo?</x-slot>
        <x-slot name="content">
            <div class="font-['Inter',ui-sans-serif,system-ui,sans-serif]">
                <p class="text-sm text-graphite">
                    Registraste estas ausencias el {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}. Si duran más días,
                    pon la fecha de fin (la del certificado o el permiso): los días siguientes aparecerán ya marcados al abrir el
                    registro, y desde el día después del fin quedarán en blanco para A o F.
                </p>
                <div class="mt-4 divide-y divide-hairline rounded-[10px] border border-mist">
                    @foreach ($preguntasHasta as $i => $p)
                        <div class="flex flex-wrap items-center gap-3 px-3 py-2.5" wire:key="hasta-{{ $p['suspension_id'] }}">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ $p['nombre'] }}</p>
                                <p class="text-xs text-pewter">
                                    <span class="rounded-[6px] bg-fog px-1.5 py-0.5 font-medium text-graphite">{{ $p['codigo'] }}</span>
                                    {{ $p['tipo'] }} · desde {{ \Illuminate\Support\Carbon::parse($p['desde'])->format('d/m/Y') }}
                                </p>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-graphite">
                                hasta
                                <x-input type="date" wire:model="preguntasHasta.{{ $i }}.hasta" class="!h-9 !w-auto"
                                    min="{{ $fecha }}" />
                            </label>
                            @error("preguntasHasta.{$i}.hasta") <p class="w-full text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-pewter">Si es solo hoy, deja la fecha como está. Después se puede cambiar con clic derecho sobre el trabajador.</p>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarPreguntaHasta', false)">Solo hoy</x-button>
            @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                <x-button wire:click="confirmarHasta" wire:loading.attr="disabled" wire:target="confirmarHasta">
                    <i class="fa fa-calendar-check"></i> Guardar fechas
                </x-button>
            @endcan
        </x-slot>
    </x-dialog-modal>

    {{-- ¿Regresó? Se puso otro código (A, F…) sobre un día de una suspensión que sigue después de hoy --}}
    <x-dialog-modal wire:model.live="mostrarPreguntaRegreso" maxWidth="2xl">
        <x-slot name="title">¿Regresó antes?</x-slot>
        <x-slot name="content">
            <div class="font-['Inter',ui-sans-serif,system-ui,sans-serif]">
                <p class="text-sm text-graphite">
                    Estos trabajadores tenían una ausencia programada que sigue después del
                    {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }} y hoy se les registró otra cosa. ¿Qué pasó?
                </p>
                <div class="mt-4 divide-y divide-hairline rounded-[10px] border border-mist">
                    @foreach ($preguntasRegreso as $i => $p)
                        @php
                            $fin = $p['hasta'] ? \Illuminate\Support\Carbon::parse($p['hasta'])->format('d/m/Y') : 'sin fin';
                            $ayer = \Illuminate\Support\Carbon::parse($fecha)->subDay()->format('d/m/Y');
                            $opciones = [
                                'regreso' => ['Regresó hoy', "La {$p['codigo']} termina el {$ayer}; se quitan sus {$p['codigo']} hasta el {$fin}."],
                                'solo_hoy' => ['Solo hoy', "Hoy queda " . ($p['nuevo'] ?: 'vacío') . " y la {$p['codigo']} sigue mañana hasta el {$fin}."],
                                'error' => ['Fue un error', "Hoy vuelve a {$p['codigo']} (se borran los tramos de hoy) y la ausencia queda como estaba."],
                            ];
                        @endphp
                        <div class="px-3 py-3" wire:key="regreso-{{ $p['plan_det_men_id'] }}">
                            <p class="text-sm font-medium text-ink">{{ $p['nombre'] }}</p>
                            <p class="text-xs text-pewter">
                                <span class="rounded-[6px] bg-fog px-1.5 py-0.5 font-medium text-graphite">{{ $p['codigo'] }}</span>
                                {{ $p['tipo'] }} · {{ \Illuminate\Support\Carbon::parse($p['desde'])->format('d/m/Y') }} al {{ $fin }}
                                · hoy se registró <span class="font-medium text-ink">{{ $p['nuevo'] ?: 'vacío' }}</span>
                            </p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                @foreach ($opciones as $valor => [$titulo, $ayuda])
                                    <label class="flex cursor-pointer gap-2 rounded-[10px] border px-2.5 py-2 has-[:checked]:border-carmin has-[:checked]:bg-carmin/5 border-mist hover:bg-fog">
                                        <input type="radio" value="{{ $valor }}" wire:model="preguntasRegreso.{{ $i }}.decision" class="mt-0.5 text-carmin focus:ring-carmin/25">
                                        <span>
                                            <span class="block text-sm font-medium text-ink">{{ $titulo }}</span>
                                            <span class="block text-xs text-pewter">{{ $ayuda }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="cerrarPreguntaRegreso">Dejar como se guardó</x-button>
            @can(\App\Constants\Permisos::PLANILLA_SUSPENSION_GESTIONAR)
                <x-button wire:click="confirmarRegreso" wire:loading.attr="disabled" wire:target="confirmarRegreso">
                    <i class="fa fa-check"></i> Aplicar
                </x-button>
            @endcan
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="guardarInformacionRegistroPlanilla,regresoAnticipado,recargarTabla,confirmarHasta,confirmarRegreso" />
</div>
@script
<script>
    Alpine.data('registroDiarioActividades', () => ({
        hot: null,
        tableData: @json($empleados),
        totalActividades: @json((int) $totalActividades),
        // Columnas con las que se cargó (para saber si cambiaron)
        totalActividadesGuardado: @json((int) $totalActividades),
        campos: @json($campos),
        tipoAsistenciasHoras: @json($tipoAsistenciasHoras),
        tipoAsistenciasCodigos: @json($tipoAsistenciasCodigos),
        isDark: JSON.parse(localStorage.getItem('darkMode') ?? 'false'),
        // Estado local del navegador (antes viajaba al servidor en cada petición)
        filasModificadas: new Set(),
        totalHorasManuales: new Set(),
        guardando: false,
        guardia: { abierta: false, accion: null, alCancelar: null },
        fechaTexto: @json(\Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y')),
        mes: @json((int) \Illuminate\Support\Carbon::parse($fecha)->month),
        anio: @json((int) \Illuminate\Support\Carbon::parse($fecha)->year),
        codigosRenuncia: @json(array_merge([''], \App\Models\PlanTipoAsistencia::codigosConRol(\App\Models\PlanTipoAsistencia::ROL_RENUNCIA))),
        _alSalir: null,
        _alNavegar: null,
        _quitarListener: null,
        _guardia: null,

        get columnasCambiaron() {
            return this.totalActividades !== this.totalActividadesGuardado;
        },
        get pendiente() {
            return this.filasModificadas.size > 0 || this.columnasCambiaron;
        },

        init() {
            this._quitarListener = Livewire.on('setEmpleados', ({ filas, totalActividades }) => {
                this.tableData = filas;
                this.totalActividades = totalActividades;
                this.totalActividadesGuardado = totalActividades;
                this.limpiarMarcas();
                this.hot.updateSettings({ columns: this.generateColumns(this.totalActividades) });
                this.hot.loadData(this.tableData);
            });
            this.initTable();

            // Para los botones de fuera de la tabla (fecha, Gestionar lista, Enviar reporte…)
            this._guardia = (accion, alCancelar = null) => this.pedirGuardia(accion, alCancelar);
            window.registroDiarioGuardia = this._guardia;

            // Cerrar o recargar la pestaña, o salir por un enlace normal
            this._alSalir = (e) => {
                if (!this.pendiente) return;
                e.preventDefault();
                e.returnValue = '';
            };
            window.addEventListener('beforeunload', this._alSalir);
            // Enlaces con wire:navigate (menú)
            this._alNavegar = (e) => {
                if (this.pendiente && !confirm('Hay cambios sin guardar en el registro diario. ¿Salir y perderlos?')) {
                    e.preventDefault();
                }
            };
            document.addEventListener('livewire:navigate', this._alNavegar);

            if (typeof this.$watch === 'function') {
                try {
                    this.$watch('darkMode', (value) => {
                        this.isDark = value;
                        this.hot?.updateSettings({
                            themeName: value ? 'ht-theme-main-dark' : 'ht-theme-main',
                            columns: this.generateColumns(this.totalActividades),
                        });
                    });
                } catch (e) { }
            }
        },

        destroy() {
            window.removeEventListener('beforeunload', this._alSalir);
            document.removeEventListener('livewire:navigate', this._alNavegar);
            if (typeof this._quitarListener === 'function') this._quitarListener();
            // Al cambiar de día la tabla nueva puede iniciar antes de que esta se destruya: solo quitar la propia
            if (window.registroDiarioGuardia === this._guardia) delete window.registroDiarioGuardia;
            try { this.hot?.destroy(); } catch (e) { }
        },

        limpiarMarcas() {
            this.filasModificadas = new Set();
            this.totalHorasManuales = new Set();
        },

        marcarFila(fisica) {
            if (this.filasModificadas.has(fisica)) return;
            const s = new Set(this.filasModificadas);
            s.add(fisica);
            this.filasModificadas = s; // nuevo Set para que Alpine lo note
        },

        // ---- Menú contextual ----
        filaSeleccionada() {
            const sel = this.hot?.getSelectedLast();
            return sel && sel[0] >= 0 ? this.hot.getSourceDataAtRow(this.hot.toPhysicalRow(sel[0])) : null;
        },
        // Panel del empleado (solo lectura de la tabla: no la recarga, no hace falta guardar antes)
        abrirEmpleado(tab) {
            const fila = this.filaSeleccionada();
            if (fila?.plan_empleado_id) {
                $wire.dispatch('abrirEmpleado', { id: fila.plan_empleado_id, tab });
            }
        },

        // ---- Guardia de cambios sin guardar ----
        pedirGuardia(accion, alCancelar = null) {
            if (!this.pendiente) {
                accion();
                return;
            }
            this.guardia = { abierta: true, accion, alCancelar };
        },
        cancelarGuardia() {
            const alCancelar = this.guardia.alCancelar;
            this.guardia = { abierta: false, accion: null, alCancelar: null };
            if (alCancelar) alCancelar();
        },
        descartarYContinuar() {
            const accion = this.guardia.accion;
            this.guardia = { abierta: false, accion: null, alCancelar: null };
            this.limpiarMarcas();
            this.totalActividadesGuardado = this.totalActividades;
            if (accion) accion();
        },
        async guardarYContinuar() {
            const ok = await this.guardar();
            if (!ok) return; // el error ya se mostró; el modal queda abierto para decidir
            const accion = this.guardia.accion;
            this.guardia = { abierta: false, accion: null, alCancelar: null };
            if (accion) accion();
        },

        initTable() {
            if (this.hot) {
                try { this.hot.destroy(); } catch (e) { }
                this.hot = null;
            }
            const self = this;
            const container = this.$refs.tableContainer;
            this.hot = new Handsontable(container, {
                data: this.tableData,
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                colHeaders: true,
                rowHeaders: true,
                columns: this.generateColumns(this.totalActividades),
                width: '100%',
                // Alto fijo con scroll propio: la barra horizontal queda siempre a la vista, abajo de la tabla
                height: Math.max(320, window.innerHeight - 260),
                manualColumnResize: true,
                manualRowResize: true,
                // Columnas a su ancho (se desborda en horizontal) y filas de una sola línea
                stretchH: 'none',
                wordWrap: false,
                rowHeights: 30,
                // Los anchos ya vienen en las columnas: medir cada celda hacía lenta la tabla
                autoColumnSize: false,
                autoRowSize: false,
                filters: true,
                dropdownMenu: false,
                fixedColumnsLeft: 3,
                licenseKey: 'non-commercial-and-evaluation',
                // Clic derecho sobre un trabajador: sus suspensiones (vacaciones, DM, licencias) en el mismo modal de Permisos
                // Clic derecho sobre un trabajador: su relación con el registro diario (contrato del día, ficha, suspensiones)
                contextMenu: {
                    items: {
                        contrato_info: {
                            // Solo informativo: el contrato respecto a la fecha del registro
                            name: () => {
                                const f = self.filaSeleccionada();
                                if (!f?.plan_empleado_id) return '<span class="text-pewter">Fila sin trabajador</span>';
                                const estados = { vigente: 'vigente', terminado: 'ya terminó', por_iniciar: 'aún no empieza', ninguno: '' };
                                const color = f.contrato_estado === 'vigente' ? 'text-green-700' : 'text-amber-700';
                                return `<span class="${color}"><i class="fa fa-file-signature"></i>&nbsp; Contrato ${f.contrato_texto}`
                                    + (estados[f.contrato_estado] ? ` · ${estados[f.contrato_estado]} el ${self.fechaTexto}` : '') + '</span>';
                            },
                            disabled: true,
                        },
                        sep0: '---------',
                        ver_contrato: {
                            name: '<i class="fa fa-briefcase"></i>&nbsp; Ver historial laboral (contratos, cargo, sueldo)…',
                            disabled: () => !self.filaSeleccionada()?.plan_empleado_id,
                            callback: () => self.abrirEmpleado('contratos'),
                        },
                        ver_perfil: {
                            name: '<i class="fa fa-id-card"></i>&nbsp; Ver ficha del trabajador…',
                            disabled: () => !self.filaSeleccionada()?.plan_empleado_id,
                            callback: () => self.abrirEmpleado('perfil'),
                        },
                        ver_pension: {
                            name: '<i class="fa fa-piggy-bank"></i>&nbsp; Ver pensión y forma de pago…',
                            disabled: () => !self.filaSeleccionada()?.plan_empleado_id,
                            callback: () => self.abrirEmpleado('pension_pago'),
                        },
                        suspensiones: {
                            name: '<i class="fa fa-umbrella-beach"></i>&nbsp; Suspensiones / vacaciones del trabajador…',
                            disabled: () => !self.filaSeleccionada()?.plan_empleado_id,
                            callback: () => {
                                const fila = self.filaSeleccionada();
                                if (!fila?.plan_empleado_id) return;
                                // Al guardar el modal se recarga la tabla: primero los cambios sin guardar
                                self.pedirGuardia(() => $wire.dispatch('editarSuspensionesTrabajador', {
                                    planEmpleadoId: fila.plan_empleado_id, mes: self.mes, anio: self.anio,
                                }));
                            },
                        },
                        sep1: '---------',
                        undo: {}, redo: {}, sep2: '---------', copy: {}, cut: {},
                    },
                },
                cells: function (row, col) {
                    const cellProperties = {};
                    const rowData = this.instance.getSourceDataAtRow(row);
                    if (!rowData) return cellProperties;

                    // Filas de cuadrilla: solo lectura
                    if (rowData.numero_cuadrilleros > 0) {
                        cellProperties.readOnly = true;
                        cellProperties.className = '!bg-muted !text-center';
                    }

                    // Día fuera de su contrato (cesó o aún no empieza): solo se puede dejar vacío o con R
                    if (rowData.sin_contrato) {
                        const prop = this.instance.colToProp(col);
                        cellProperties.readOnly = prop !== 'asistencia';
                        cellProperties.className = '!bg-muted !text-pewter' + (prop === 'nombres' ? '' : ' !text-center');
                        if (prop === 'asistencia') {
                            cellProperties.source = self.codigosRenuncia;
                        }
                        return cellProperties;
                    }

                    if (this.instance.colToProp(col) === 'total_horas') {
                        const bloqueado = ['A', 'LSG', 'F'].includes(rowData.asistencia) || rowData.nombres == 'CUADRILLA';
                        cellProperties.readOnly = bloqueado;
                        cellProperties.className = bloqueado ? '!bg-muted !text-center font-bold' : '!text-center font-bold';
                    }
                    return cellProperties;
                },
                afterChange: (changes, source) => {
                    if (!changes || source === 'recalculado' || source === 'loadData') return;

                    const filas = new Map();
                    changes.forEach(([row, prop, antes, despues]) => {
                        if (antes === despues || (antes == null && despues === '')) return;
                        const fisica = this.hot.toPhysicalRow(row);
                        this.marcarFila(fisica);

                        if (prop === 'total_horas') {
                            this.totalHorasManuales.add(fisica);
                            return;
                        }
                        if (prop === 'asistencia') {
                            this.totalHorasManuales.delete(fisica);
                        }
                        if (!filas.has(row)) filas.set(row, fisica);
                    });
                    filas.forEach((fisica, row) => this.recalcularTotales(row, fisica));
                },
            });
        },

        aplicarFiltros() {
            const nombreQuery = this.$refs.filterNombre.value.toLowerCase();
            const asistenciaQuery = this.$refs.filterAsistencia.value;
            const filtersPlugin = this.hot.getPlugin('filters');
            filtersPlugin.clearConditions();
            if (nombreQuery) {
                filtersPlugin.addCondition(0, 'contains', [nombreQuery]);
            }
            if (asistenciaQuery) {
                filtersPlugin.addCondition(1, 'eq', [asistenciaQuery]);
            }
            filtersPlugin.filter();
        },

        timeToMinutes(time) {
            if (!time || typeof time !== 'string') return 0;
            const partes = time.replace(':', '.').split('.');
            const hours = parseInt(partes[0], 10) || 0;
            const minutes = parseInt(partes[1], 10) || 0;
            return (hours * 60) + minutes;
        },

        minutesToTime(minutes) {
            if (isNaN(minutes) || !isFinite(minutes) || minutes < 0) {
                return "00.00";
            }
            const hours = Math.floor(minutes / 60);
            const mins = Math.round(minutes % 60);
            return `${String(hours).padStart(2, '0')}.${String(mins).padStart(2, '0')}`;
        },

        agregarTramo() {
            this.totalActividades++;
            this.hot.updateSettings({ columns: this.generateColumns(this.totalActividades) });
        },

        quitarTramo() {
            if (this.totalActividades <= 1) return;
            // No ocultar un tramo con datos: al guardar se borraría
            const n = this.totalActividades;
            const conDatos = this.hot.getSourceData().filter(f =>
                ['campo', 'labor', 'entrada', 'salida'].some(p => f[`${p}_${n}`] != null && f[`${p}_${n}`] !== ''));
            if (conDatos.length) {
                const nombres = conDatos.slice(0, 5).map(f => f.nombres).join(', ');
                alert(`El tramo ${n} tiene datos en ${conDatos.length} fila(s) (${nombres}${conDatos.length > 5 ? '…' : ''}). Bórralos antes de quitar la columna.`);
                return;
            }
            this.totalActividades--;
            this.hot.updateSettings({ columns: this.generateColumns(this.totalActividades) });
        },

        generateColumns(totalActividades) {
            const bgHeader = '!bg-muted cursor-not-allowed';
            const columns = [{
                data: "nombres",
                type: 'text',
                readOnly: true,
                className: bgHeader,
                width: 300,
                title: 'APELLIDOS Y NOMBRES',
                renderer: function (hotInstance, td, row, col, prop, value) {
                    Handsontable.renderers.TextRenderer.apply(this, arguments);
                    const fila = hotInstance.getSourceDataAtRow(hotInstance.toPhysicalRow(row));
                    if (fila?.sin_contrato) {
                        td.innerHTML = '';
                        td.append(document.createTextNode(value ?? ''));
                        const marca = document.createElement('span');
                        marca.className = 'ml-1 text-[11px] text-amber-700 dark:text-amber-400';
                        marca.textContent = '· sin contrato';
                        td.append(marca);
                        td.title = 'Este día está fuera de su contrato: solo se puede dejar vacío o con R';
                    }
                },
            }, {
                data: "asistencia",
                type: 'dropdown',
                source: this.tipoAsistenciasCodigos,
                strict: true,
                width: 64,
                title: 'ASIST.',
                className: 'text-center'
            }, {
                data: 'numero_cuadrilleros',
                type: 'numeric',
                width: 54,
                title: 'N° C.',
                readOnly: true,
                className: `text-center ${bgHeader}`
            }];

            for (let indice = 1; indice <= totalActividades; indice++) {
                const intercalado = this.isDark ?
                    (indice % 2 === 0 ? '!bg-neutral-900' : '') :
                    (indice % 2 === 0 ? '!bg-blue-100' : '');

                columns.push({
                    data: "campo_" + indice,
                    type: 'dropdown',
                    width: 72,
                    className: `text-center ${intercalado}`,
                    source: this.campos,
                    title: `CAM. ${indice}`
                }, {
                    data: "labor_" + indice,
                    type: 'text',
                    width: 52,
                    className: `text-center ${intercalado}`,
                    title: `LAB. ${indice}`
                }, {
                    data: "entrada_" + indice,
                    type: 'hora24',
                    width: 58,
                    className: `text-center ${intercalado}`,
                    title: `ENT. ${indice}`
                }, {
                    data: "salida_" + indice,
                    type: 'hora24',
                    width: 58,
                    className: `text-center ${intercalado}`,
                    title: `SAL. ${indice}`
                });
            }

            columns.push({
                data: 'total_horas',
                width: 70,
                type: 'numeric',
                readOnly: true,
                title: 'TOTAL',
                className: '!text-center font-bold text-lg',
                renderer: function (hotInstance, td, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer.apply(this, arguments);
                    const cero = value === '00:00' || value === '0' || value === '0.00' || value === '00.00' || value === null || value === 0;
                    td.classList.add(cero ? '!text-red-600' : '!text-green-600', 'font-bold');
                }
            }, {
                data: 'total_bono',
                width: 80,
                type: 'numeric',
                readOnly: true,
                title: 'BONO',
                className: `text-center font-bold text-lg !bg-gray-100 ${bgHeader}`,
                renderer: function (hotInstance, td, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer.apply(this, arguments);
                    td.classList.remove('htDimmed');
                    td.classList.add(value > 0 ? '!text-green-600' : '!text-red-600', 'font-bold');
                    if (typeof value === 'number') {
                        td.innerHTML = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
                    }
                }
            });

            return columns;
        },

        // Total de horas de la fila: por tipo de asistencia (DM, V…) o sumando los tramos si es A
        recalcularTotales(row, fisica) {
            if (this.totalHorasManuales.has(fisica)) return;

            const fila = this.hot.getSourceDataAtRow(fisica);
            const tipoAsistencia = fila.asistencia;

            if (tipoAsistencia !== 'A' && tipoAsistencia !== '' && tipoAsistencia != null) {
                const horas = this.tipoAsistenciasHoras[tipoAsistencia] ?? 0;
                this.hot.setDataAtRowProp(row, 'total_horas', this.minutesToTime(horas * 60), 'recalculado');
                return;
            }

            let totalMinutos = 0;
            for (let n = 1; n <= this.totalActividades; n++) {
                const inicio = fila[`entrada_${n}`];
                const fin = fila[`salida_${n}`];
                if (!inicio || !fin) continue;
                const minutos = this.timeToMinutes(fin) - this.timeToMinutes(inicio);
                if (minutos > 0) totalMinutos += minutos;
            }
            this.hot.setDataAtRowProp(row, 'total_horas', this.minutesToTime(totalMinutos), 'recalculado');
        },

        // Envía las filas modificadas y las columnas. Devuelve true si se guardó.
        async guardar() {
            if (this.guardando) return false;
            this.hot.getActiveEditor()?.finishEditing?.(); // celda que se estaba escribiendo
            const datos = this.hot.getSourceData();
            const filas = [...this.filasModificadas].map(i => datos[i]).filter(Boolean);

            this.guardando = true;
            try {
                const ok = await $wire.guardarInformacionRegistroPlanilla(filas, this.totalActividades);
                if (ok) {
                    this.limpiarMarcas();
                    this.totalActividadesGuardado = this.totalActividades;
                }
                return !!ok;
            } finally {
                this.guardando = false;
            }
        },
    }))
</script>
@endscript
