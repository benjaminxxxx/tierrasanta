<div class="space-y-4" x-data="cajaMovimientos">
    @php
        $fmt = fn($v) => number_format((float) $v, 2);
        $puede = $this->puedeGestionar;
    @endphp

    <x-flex class="justify-between flex-wrap gap-3">
        <div>
            <x-title>Caja — {{ ucfirst($nombreMes) }}</x-title>
            <x-subtitle>Registro real de ingresos y salidas. Ningún otro módulo escribe aquí: se cuadra contra ellos.</x-subtitle>
        </div>
        <x-flex class="flex-wrap gap-2">
            <x-button variant="secondary" wire:click="exportar('mes')" :disabled="!$mes">
                <i class="fa fa-file-excel"></i> Excel del mes
            </x-button>
            <x-button variant="secondary" wire:click="exportar('anio')">
                <i class="fa fa-file-excel"></i> Excel del año
            </x-button>
            @if ($puede)
                <x-button variant="secondary" wire:click="$set('modalImportar', true)">
                    <i class="fa fa-upload"></i> Importar Excel
                </x-button>
            @endif
        </x-flex>
    </x-flex>

    @if ($puede && count($mesesSinCerrar))
        <x-warning>
            Falta cerrar la caja de:
            @foreach ($mesesSinCerrar as $p)
                <button type="button" class="underline font-semibold" wire:click="irAMes({{ $p['anio'] }}, {{ $p['mes'] }})">
                    {{ \Illuminate\Support\Carbon::create($p['anio'], $p['mes'], 1)->locale('es')->translatedFormat('F Y') }}</button>{{ $loop->last ? '.' : ',' }}
            @endforeach
        </x-warning>
    @endif

    {{-- Filtros --}}
    <x-card class="space-y-3">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <x-select label="Año" wire:model.live="anio">
                @foreach ($opciones['anios'] as $a)
                    <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </x-select>
            <x-select label="Mes" wire:model.live="mes">
                <option value="">Todo el año</option>
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}">{{ ucfirst(\Illuminate\Support\Carbon::create(2000, $m, 1)->locale('es')->translatedFormat('F')) }}</option>
                @endforeach
            </x-select>
            <x-select label="Semana" wire:model.live="semana">
                <option value="">Todas</option>
                @foreach (range(1, 5) as $s)
                    <option value="{{ $s }}">SEM-{{ $s }}</option>
                @endforeach
            </x-select>
            <x-select label="Tipo" wire:model.live="tipo">
                <option value="">Ingresos y egresos</option>
                <option value="INGRESO">Ingresos</option>
                <option value="EGRESO">Egresos</option>
            </x-select>
            <x-select label="Condición" wire:model.live="condicion">
                <option value="">NEG. y BLA.</option>
                <option value="NEG">NEG.</option>
                <option value="BLA">BLA.</option>
            </x-select>
            <x-select label="Origen" wire:model.live="contable">
                <option value="">Todos</option>
                <option value="no">Caja (N° correlativo)</option>
                <option value="si">Contable</option>
            </x-select>
            @php
                $clasif1 = collect($opciones['clasificadores'])->pluck('clasificador_1')->unique()->sort()->values();
                $clasif2 = collect($opciones['clasificadores'])->when($clasificador1, fn($c) => $c->where('clasificador_1', $clasificador1))
                    ->pluck('clasificador_2')->unique()->sort()->values();
            @endphp
            <div class="col-span-2">
                <x-select label="Clasificador 1" wire:model.live="clasificador1">
                    <option value="">Todos</option>
                    @foreach ($clasif1 as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="col-span-2">
                <x-select label="Clasificador 2" wire:model.live="clasificador2">
                    <option value="">Todos</option>
                    @foreach ($clasif2 as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </x-select>
            </div>
            <x-select label="Sub-grupo (NG o BL)" wire:model.live="subgrupo">
                <option value="">Todos</option>
                @foreach (collect($opciones['subgrupos_ng'])->merge($opciones['subgrupos_bl'])->unique()->sort() as $s)
                    <option value="{{ $s }}">{{ $s }}</option>
                @endforeach
            </x-select>
            <div class="flex items-end gap-2">
                <x-input type="search" label="Buscar" wire:model.live.debounce.400ms="buscar" placeholder="Beneficiario, detalle, N° doc…" />
            </div>
        </div>
        <div class="flex justify-end">
            <x-button size="xs" variant="outline" wire:click="limpiarFiltros"><i class="fa fa-eraser"></i> Quitar filtros</x-button>
        </div>
    </x-card>

    {{-- Totales y estado del mes --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Saldo anterior</span>
            <span class="text-xl font-semibold tabular-nums">S/ {{ $fmt($datos['saldo_anterior']) }}</span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Ingresos</span>
            <span class="text-xl font-semibold tabular-nums text-green-700 dark:text-green-400">S/ {{ $fmt($datos['totales']['ingresos']) }}</span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Egresos</span>
            <span class="text-xl font-semibold tabular-nums text-red-700 dark:text-red-400">S/ {{ $fmt($datos['totales']['egresos']) }}</span>
        </x-card>
        <x-card>
            <span class="text-xs uppercase text-muted-foreground block">Disponible al cierre del periodo</span>
            <span class="text-xl font-bold tabular-nums">S/ {{ $fmt($datos['saldo_final']) }}</span>
        </x-card>
        <x-card class="flex flex-col justify-between gap-2">
            @if (!$mes)
                <span class="text-sm text-muted-foreground">Elige un mes para ver su cierre.</span>
            @elseif ($cierre && $cierre->estado === 'cerrado')
                <div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 text-xs font-semibold">
                        <i class="fa fa-lock"></i> Mes cerrado
                    </span>
                    <span class="block text-xs text-muted-foreground mt-1">
                        {{ $cierre->cerrado_at?->format('d/m/Y H:i') }} · {{ $cierre->cerradoPor?->name }} · saldo S/ {{ $fmt($cierre->saldo_final) }}
                    </span>
                </div>
                @if ($puede)
                    <x-button size="sm" variant="outline" wire:click="$set('modalReabrir', true)"><i class="fa fa-lock-open"></i> Reabrir</x-button>
                @endif
            @else
                <div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 text-xs font-semibold">
                        <i class="fa fa-lock-open"></i> Mes abierto
                    </span>
                    @if ($cierre?->motivo_reapertura)
                        <span class="block text-xs text-muted-foreground mt-1" title="{{ $cierre->motivo_reapertura }}">Reabierto: {{ \Illuminate\Support\Str::limit($cierre->motivo_reapertura, 50) }}</span>
                    @endif
                </div>
                @if ($puede)
                    <x-button size="sm" wire:click="$set('modalCerrar', true)"><i class="fa fa-lock"></i> Cerrar mes</x-button>
                @endif
            @endif
        </x-card>
    </div>

    {{-- Tabla (solo lectura) --}}
    <x-card>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <span class="text-sm text-muted-foreground">
                {{ number_format($datos['totales']['movimientos']) }} movimiento(s).
                @if ($puede && $mesEditable)
                    Clic derecho sobre una fila para editar, duplicar, pintar o eliminar.
                @elseif ($puede && $mes)
                    El mes está cerrado: solo lectura.
                @elseif ($puede)
                    Elige un mes para modificar movimientos.
                @endif
            </span>
            @if ($puede && $mesEditable)
                <x-button wire:click="$dispatch('cajaNuevoMovimiento')"><i class="fa fa-plus"></i> Nuevo movimiento</x-button>
            @endif
        </div>
        <div wire:ignore>
            <div x-ref="tabla"></div>
        </div>
    </x-card>

    {{-- Arqueos --}}
    <x-card class="space-y-2">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-foreground">Arqueos — saldo real por fuente</h3>
                <p class="text-xs text-muted-foreground">El dinero está repartido (oficina AQP, caja naranja, Flavia, cuadrillas…). La suma de lo que queda en cada fuente debe ser igual al disponible de caja de ese día.</p>
            </div>
            @if ($puede && $mesEditable)
                <x-button size="sm" variant="secondary" wire:click="abrirArqueo"><i class="fa fa-plus"></i> Registrar arqueo</x-button>
            @endif
        </div>
        @if (count($arqueos))
            <div class="overflow-x-auto">
                @php $nombresFuentes = collect($arqueos)->flatMap(fn($a) => array_column($a['detalles'], 'fuente'))->unique()->values(); @endphp
                <table class="w-full text-xs">
                    <thead class="bg-muted text-muted-foreground">
                        <tr>
                            <th class="p-2 text-left">Fecha</th>
                            @foreach ($nombresFuentes as $nf)
                                <th class="p-2 text-right">{{ $nf }}</th>
                            @endforeach
                            <th class="p-2 text-right">Total</th>
                            <th class="p-2 text-right">Disponible</th>
                            <th class="p-2 text-right">Diferencia</th>
                            <th class="p-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($arqueos as $a)
                            @php $montos = array_column($a['detalles'], 'monto', 'fuente'); @endphp
                            <tr class="border-t border-border" wire:key="arq-{{ $a['id'] }}">
                                <td class="p-2 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($a['fecha'])->format('d/m/Y') }}</td>
                                @foreach ($nombresFuentes as $nf)
                                    <td class="p-2 text-right tabular-nums">{{ isset($montos[$nf]) ? $fmt($montos[$nf]) : '—' }}</td>
                                @endforeach
                                <td class="p-2 text-right tabular-nums font-semibold">{{ $fmt($a['total']) }}</td>
                                <td class="p-2 text-right tabular-nums">{{ $fmt($a['disponible']) }}</td>
                                <td class="p-2 text-right tabular-nums font-semibold {{ abs($a['diferencia']) < 0.01 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                                    {{ abs($a['diferencia']) < 0.01 ? 'Cuadra' : $fmt($a['diferencia']) }}
                                </td>
                                <td class="p-2 text-right whitespace-nowrap">
                                    @if ($puede && $mesEditable)
                                        <button type="button" class="text-blue-600 hover:underline" wire:click="abrirArqueo({{ $a['id'] }})"><i class="fa fa-edit"></i></button>
                                        <button type="button" class="text-red-600 hover:underline ml-2" wire:click="eliminarArqueo({{ $a['id'] }})"
                                            wire:confirm="¿Eliminar el arqueo del {{ \Illuminate\Support\Carbon::parse($a['fecha'])->format('d/m/Y') }}?"><i class="fa fa-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-muted-foreground">Diferencia = total de las fuentes − disponible al cierre de ese día. Un arqueo hecho a media jornada puede no incluir los movimientos posteriores del mismo día.</p>
        @else
            <p class="text-sm text-muted-foreground">Sin arqueos en este periodo.</p>
        @endif
    </x-card>

    {{-- Modal: color personalizado --}}
    <x-dialog-modal wire:model="modalColor" maxWidth="md">
        <x-slot name="title">Color de {{ count($colorIds) }} fila(s)</x-slot>
        <x-slot name="content">
            @php
                $presets = [
                    ['Venta cochinilla', '#B4C6E7', null], ['Venta naranja', '#FFD966', null], ['Utilidades', '#FFFF00', null],
                    ['Pago SUNAT', null, '#FF9933'], ['Cuadrilla', null, '#0070C0'], ['Fert./Pest./Comb.', null, '#00B050'],
                    ['Rosado', '#E69ACD', null], ['Lila', '#DEC8EE', null], ['Verde claro', '#C6EFCE', null], ['Rojo claro', '#FFC7CE', null],
                ];
            @endphp
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach ($presets as [$nombre, $f, $t])
                    <button type="button" class="px-2 py-1 rounded border border-black/10 text-xs font-semibold"
                        style="background: {{ $f ?? 'transparent' }}; color: {{ $t ?? ($f ? '#111827' : 'inherit') }}"
                        wire:click="$set('colorFondo', @js($f)); $set('colorTexto', @js($t))">{{ $nombre }}</button>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-3 items-end">
                <div>
                    <x-label>Fondo</x-label>
                    <div class="flex items-center gap-2">
                        <input type="color" wire:model.live="colorFondo" class="h-9 w-14 rounded border border-input bg-background">
                        <button type="button" class="text-xs underline text-muted-foreground" wire:click="$set('colorFondo', null)">Sin fondo</button>
                    </div>
                </div>
                <div>
                    <x-label>Letra</x-label>
                    <div class="flex items-center gap-2">
                        <input type="color" wire:model.live="colorTexto" class="h-9 w-14 rounded border border-input bg-background">
                        <button type="button" class="text-xs underline text-muted-foreground" wire:click="$set('colorTexto', null)">Normal</button>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm col-span-2">
                    <input type="checkbox" wire:model.live="colorNegrita" class="rounded"> Negrita
                </label>
            </div>
            <div class="mt-4 p-2 rounded border border-border text-sm"
                style="background: {{ $colorFondo ?? 'transparent' }}; color: {{ $colorTexto ?? ($colorFondo ? '#111827' : 'inherit') }}; font-weight: {{ $colorNegrita ? 700 : 400 }}">
                Vista previa de la fila
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalColor', false)">Cancelar</x-button>
            <x-button wire:click="guardarColor"><i class="fa fa-palette"></i> Aplicar</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: eliminar --}}
    <x-dialog-modal wire:model="modalEliminar" maxWidth="md">
        <x-slot name="title">Eliminar movimiento</x-slot>
        <x-slot name="content">
            <p class="text-sm">{{ $eliminarResumen }}</p>
            <p class="text-xs text-muted-foreground mt-2">Queda registrado en la auditoría. El disponible de las filas siguientes cambia.</p>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalEliminar', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="eliminar"><i class="fa fa-trash"></i> Eliminar</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: cerrar mes --}}
    <x-dialog-modal wire:model="modalCerrar" maxWidth="md">
        <x-slot name="title">Cerrar la caja de {{ $nombreMes }}</x-slot>
        <x-slot name="content">
            <p class="text-sm">Disponible al cierre: <b>S/ {{ $fmt($datos['saldo_final']) }}</b>.</p>
            <p class="text-sm mt-2">Cerrado el mes no se podrá registrar, editar, pintar ni eliminar nada de él (ni sus arqueos). Si luego hay un error, se puede reabrir indicando el motivo.</p>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalCerrar', false)">Cancelar</x-button>
            <x-button wire:click="cerrarMes"><i class="fa fa-lock"></i> Cerrar mes</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: reabrir mes --}}
    <x-dialog-modal wire:model="modalReabrir" maxWidth="md">
        <x-slot name="title">Reabrir la caja de {{ $nombreMes }}</x-slot>
        <x-slot name="content">
            <x-textarea label="Motivo (qué hay que corregir)" wire:model="motivoReapertura" error="motivo" rows="3" />
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalReabrir', false)">Cancelar</x-button>
            <x-button variant="danger" wire:click="reabrirMes"><i class="fa fa-lock-open"></i> Reabrir</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: arqueo --}}
    <x-dialog-modal wire:model="modalArqueo" maxWidth="lg">
        <x-slot name="title">{{ $arqueoId ? 'Editar' : 'Registrar' }} arqueo</x-slot>
        <x-slot name="content">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <x-input type="date" label="Fecha" wire:model="arqueoFecha" error="fecha" />
                <x-input label="Observación" wire:model="arqueoObservacion" />
            </div>
            <div class="mt-3 space-y-2">
                @foreach ($fuentes as $fuente)
                    <div class="flex items-center gap-3" wire:key="fuente-{{ $fuente->id }}">
                        <span class="w-40 text-sm">{{ $fuente->nombre }}</span>
                        <input type="number" step="0.01" wire:model="arqueoMontos.{{ $fuente->id }}" placeholder="Sin registrar"
                            class="flex-1 h-9 px-3 rounded-md border border-input bg-background text-foreground text-sm text-right">
                    </div>
                @endforeach
                @error('montos') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end gap-2 mt-3">
                <x-input label="Nueva fuente" wire:model="nuevaFuente" placeholder="Ej.: Caja chica fundo" />
                <x-button size="sm" variant="secondary" wire:click="agregarFuente"><i class="fa fa-plus"></i></x-button>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalArqueo', false)">Cancelar</x-button>
            <x-button wire:click="guardarArqueo"><i class="fa fa-save"></i> Guardar</x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: importar --}}
    <x-dialog-modal wire:model="modalImportar" maxWidth="lg">
        <x-slot name="title">Importar Excel de caja</x-slot>
        <x-slot name="content">
            <p class="text-sm mb-3">
                Lee la hoja <b>BASE</b> (movimientos), <b>Valida</b> (clasificadores), <b>Tipo de Cambio</b> y las notas de saldos por
                fuente (columnas AB/AE) como arqueos. Es para la carga inicial o para rehacer un periodo: en el día a día se registra aquí.
            </p>
            <input type="file" wire:model="archivoImportar" accept=".xlsx,.xlsm" class="text-sm">
            @error('archivoImportar') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            @error('archivo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            <label class="flex items-center gap-2 text-sm mt-3">
                <input type="checkbox" wire:model="reemplazarImportacion" class="rounded">
                Reemplazar los movimientos y arqueos que ya existen en las fechas del archivo
            </label>
        </x-slot>
        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalImportar', false)">Cancelar</x-button>
            <x-button wire:click="importar"><i class="fa fa-upload"></i> Importar</x-button>
        </x-slot>
    </x-dialog-modal>

    <livewire:caja.caja-movimiento-form-component />
    <x-loading wire:loading wire:target="irAMes,anio,mes,semana,tipo,condicion,contable,clasificador1,clasificador2,subgrupo,buscar,limpiarFiltros,importar,cerrarMes,reabrirMes,guardarColor,quitarColor,eliminar" />
</div>

@script
<script>
    Alpine.data('cajaMovimientos', () => ({
        hot: null,
        filas: @js($datos['filas']),
        editable: @js($puede && $mesEditable),

        init() {
            this.crearTabla();
            // Filtros, guardados, cierre o reapertura: el servidor manda las filas y si el mes se puede modificar
            Livewire.on('cajaFilas', ({ filas, editable }) => {
                this.filas = filas;
                this.editable = editable;
                this.hot?.loadData(this.filas);
            });
        },

        idsSeleccionados() {
            const sel = this.hot.getSelected() || [];
            const ids = new Set();
            sel.forEach(([r1, , r2]) => {
                for (let r = Math.min(r1, r2); r <= Math.max(r1, r2); r++) {
                    const fila = this.hot.getSourceDataAtRow(this.hot.toPhysicalRow(r));
                    if (fila?.id) ids.add(fila.id);
                }
            });
            return [...ids];
        },

        crearTabla() {
            const num = (v, dec = 2) => v === null || v === undefined || v === '' ? '' :
                Number(v).toLocaleString('es-PE', { minimumFractionDigits: dec, maximumFractionDigits: dec });
            const filas = () => this.filas;

            // Pinta la fila completa con el estilo calculado en el servidor (clasificador o color personalizado)
            // El tema de Handsontable fija el color de las celdas: el de la fila va con !important
            const css = (td, prop, valor) => valor ? td.style.setProperty(prop, valor, 'important') : td.style.removeProperty(prop);
            const pintar = (td, row) => {
                const e = filas()[row]?.estilo || {};
                css(td, 'background', e.fondo);
                css(td, 'color', e.texto || (e.fondo ? '#111827' : null));
                css(td, 'font-weight', e.negrita ? '700' : null);
            };
            const texto = function (instance, td, row, col, prop, value, cellProperties) {
                Handsontable.renderers.TextRenderer.apply(this, arguments);
                pintar(td, row);
            };
            const numero = (dec) => function (instance, td, row, col, prop, value, cellProperties) {
                Handsontable.renderers.TextRenderer.apply(this, [instance, td, row, col, prop, num(value, dec), cellProperties]);
                td.classList.add('htRight');
                pintar(td, row);
                if (prop === 'importe' && value < 0 && !filas()[row]?.estilo?.texto) css(td, 'color', filas()[row]?.estilo?.fondo ? '#B91C1C' : '#DC2626');
            };
            const fecha = function (instance, td, row, col, prop, value, cellProperties) {
                const [a, m, d] = (value || '').split('-');
                Handsontable.renderers.TextRenderer.apply(this, [instance, td, row, col, prop, value ? `${d}/${m}/${a}` : '', cellProperties]);
                pintar(td, row);
            };
            const importe = function (instance, td, row, col, prop, value, cellProperties) {
                numero(2).apply(this, arguments);
                const detalle = filas()[row]?.importe_detalle;
                td.title = detalle ? `Operación: ${detalle}` : '';
            };

            const columnas = [
                { data: 'numero_caja', title: 'N° Caja', width: 62, renderer: texto },
                { data: 'condicion', title: 'Cond.', width: 52, renderer: texto },
                { data: 'fecha', title: 'Fecha', width: 82, renderer: fecha },
                { data: 'semana', title: 'Sem.', width: 54, renderer: texto },
                { data: 'beneficiario', title: 'Beneficiario', width: 170, renderer: texto },
                { data: 'descripcion', title: 'Gastos B+N', width: 280, renderer: texto },
                { data: 'importe', title: 'Importe S/', width: 105, renderer: importe },
                { data: 'disponible', title: 'Disponible', width: 110, renderer: numero(2) },
                { data: 'clasificador_1', title: 'Clasificador 1', width: 190, renderer: texto },
                { data: 'clasificador_2', title: 'Clasificador 2', width: 200, renderer: texto },
                { data: 'subgrupo_ng', title: 'Sub-grupo NG', width: 120, renderer: texto },
                { data: 'subgrupo_bl', title: 'Sub-grupo BL', width: 110, renderer: texto },
                { data: 'categoria', title: 'Categoría', width: 95, renderer: texto },
                { data: 'tipo_documento', title: 'T. Doc', width: 110, renderer: texto },
                { data: 'numero_documento', title: 'N° Doc', width: 130, renderer: texto },
                { data: 'situacion_cheque', title: 'Situación cheque', width: 115, renderer: texto },
                { data: 'importe_usd', title: 'Importe $', width: 95, renderer: numero(2) },
                { data: 'tipo_cambio', title: 'TC', width: 60, renderer: numero(3) },
                { data: 'dolarizado', title: 'Dolarizado', width: 95, renderer: numero(2) },
            ];

            const menu = {
                items: {
                    editar: {
                        name: '<i class="fa fa-edit"></i> &nbsp; Editar',
                        hidden: () => !this.editable,
                        callback: () => { const [id] = this.idsSeleccionados(); if (id) $wire.dispatch('cajaEditarMovimiento', { id }); },
                    },
                    duplicar: {
                        name: '<i class="fa fa-copy"></i> &nbsp; Duplicar (nueva fila con estos datos)',
                        hidden: () => !this.editable,
                        callback: () => { const [id] = this.idsSeleccionados(); if (id) $wire.dispatch('cajaEditarMovimiento', { id, duplicar: true }); },
                    },
                    sep1: '---------',
                    color: {
                        name: '<i class="fa fa-palette"></i> &nbsp; Asignar color personalizado',
                        hidden: () => !this.editable,
                        callback: () => { const ids = this.idsSeleccionados(); if (ids.length) $wire.abrirColor(ids); },
                    },
                    quitar_color: {
                        name: '<i class="fa fa-tint-slash"></i> &nbsp; Volver al color del clasificador',
                        hidden: () => !this.editable,
                        callback: () => { const ids = this.idsSeleccionados(); if (ids.length) $wire.quitarColor(ids); },
                    },
                    sep2: '---------',
                    eliminar: {
                        name: '<i class="fa fa-trash text-red-500"></i> &nbsp; Eliminar',
                        hidden: () => !this.editable,
                        callback: () => { const [id] = this.idsSeleccionados(); if (id) $wire.confirmarEliminar(id); },
                    },
                    copy: { name: '<i class="fa fa-clipboard"></i> &nbsp; Copiar' },
                },
            };

            this.hot = new Handsontable(this.$refs.tabla, {
                ...window.HstConfig,
                themeName: JSON.parse(localStorage.getItem('darkMode')) ? 'ht-theme-main-dark' : 'ht-theme-main',
                data: this.filas,
                columns: columnas,
                colHeaders: columnas.map(c => c.title),
                readOnly: true,
                stretchH: 'none',
                height: 'calc(100vh - 260px)',
                fixedColumnsStart: 3,
                contextMenu: menu,
                sumadorSeleccion: true,
                wordWrap: false,
                autoRowSize: false,
                rowHeights: 26,
                afterOnCellMouseDown: (event, coords) => {
                    if (event.detail === 2 && this.editable && coords.row >= 0) {
                        const fila = this.hot.getSourceDataAtRow(this.hot.toPhysicalRow(coords.row));
                        if (fila?.id) $wire.dispatch('cajaEditarMovimiento', { id: fila.id });
                    }
                },
            });
        },
    }));
</script>
@endscript
