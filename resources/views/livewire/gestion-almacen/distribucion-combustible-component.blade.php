{{-- Un solo x-data cubre toda la vista incluyendo el modal --}}
<div x-data="distribucionCombustible()" x-init="init()" class="space-y-4">
    <x-flex class="justify-between">
        <x-title>Distribución de Combustible</x-title>
        <x-button variant="success" @click="$wire.dispatch('descargarReporteDistribuciones')">
            <i class="fas fa-download"></i> Descargar Reporte
        </x-button>
    </x-flex>

    {{-- Filtros --}}
    <x-card>
        <x-flex class="justify-between w-full">
            @include('comun.selector-mes-base')
            <x-flex>
                <div class="mt-4">
                    <x-label value="Maquinaria" />
                    <x-select-dropdown wire:model="filtroMaquinariaId" source="getMaquinarias"
                        placeholder="Filtrar por maquinaria" />
                </div>
            </x-flex>
        </x-flex>
    </x-card>

    {{-- Tabla principal --}}
    <x-card class="overflow-x-auto">
        <table class="w-full text-sm border-separate border-spacing-0">
            <thead>
                <tr class="text-left text-xs uppercase text-muted-foreground">
                    <th class="py-2 px-3 border-b border-border">Fecha</th>
                    <th class="py-2 px-3 border-b border-border">Maquinaria / Campo</th>
                    <th class="py-2 px-3 border-b border-border text-right">Inicio</th>
                    <th class="py-2 px-3 border-b border-border text-right">Fin</th>
                    <th class="py-2 px-3 border-b border-border text-right">Horas</th>
                    <th class="py-2 px-3 border-b border-border text-right">Cant. Comb.</th>
                    <th class="py-2 px-3 border-b border-border text-right">Costo Comb.</th>
                    <th class="py-2 px-3 border-b border-border text-right">Ingreso</th>
                    <th class="py-2 px-3 border-b border-border">Labor</th>
                    <th class="py-2 px-3 border-b border-border text-right">Precio</th>
                    <th class="py-2 px-3 border-b border-border text-right">Ratio</th>
                    <th class="py-2 px-3 border-b border-border text-right">Costo</th>
                    <th class="py-2 px-3 border-b border-border"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($filas as $fila)
                    @if($fila['es_salida'])
                        {{-- ── FILA SALIDA (cabecera de grupo) ─────────── --}}
                        <tr class="bg-blue-50 dark:bg-blue-900/30 font-semibold
                                           text-blue-800 dark:text-blue-200 border-t-2 border-blue-300 dark:border-blue-700">
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 whitespace-nowrap">
                                <span class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-1 align-middle"></span>
                                {{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }}
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800">
                                {{ $fila['maquinaria_nombre'] }}
                                <span class="ml-2 text-xs font-normal text-blue-400">
                                    {{ $fila['n_distribuciones'] }} dist.
                                    · {{ number_format($fila['horas_total'], 1) }}h
                                </span>
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right font-mono">
                                {{ number_format($fila['ingreso_salida'], 2) }}
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right font-mono">
                                S/ {{ number_format($fila['precio'], 4) }}
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right text-blue-400">—</td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right font-mono">
                                S/ {{ number_format($fila['costo'], 2) }}
                            </td>
                            <td class="py-2 px-3 border-b border-blue-200 dark:border-blue-800 text-right">
                                <button wire:click="abrirModalDistribucion({{ $fila['salida_id'] }})"
                                    class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs
                                                   bg-blue-600 hover:bg-blue-700 text-white transition-colors whitespace-nowrap">
                                    <i class="fa fa-sliders"></i> Gestionar
                                </button>
                            </td>
                        </tr>
                    @else
                        {{-- ── FILA DISTRIBUCIÓN (hija) ─────────────────── --}}
                        <tr class="hover:bg-muted/30 text-card-foreground">
                            <td class="py-1.5 px-3 border-b border-border pl-8 text-xs text-muted-foreground whitespace-nowrap">
                                <span class="text-muted-foreground mr-1">↳</span>
                                {{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs">
                                {{ $fila['campo_nombre'] ?? '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['hora_inicio'] ?? '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['hora_fin'] ?? '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['n_horas'] !== null ? number_format($fila['n_horas'], 2) : '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['cant_combustible'] !== null ? number_format($fila['cant_combustible'], 3) : '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['costo_combustible'] !== null ? 'S/ ' . number_format($fila['costo_combustible'], 4) : '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right text-muted-foreground">—</td>
                            <td class="py-1.5 px-3 border-b border-border text-xs max-w-[180px] truncate">
                                {{ $fila['labor_diaria'] ?? '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                S/ {{ number_format($fila['precio'], 4) }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                {{ $fila['ratio'] !== null ? number_format($fila['ratio'] * 100, 2) . '%' : '—' }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border text-xs text-right font-mono">
                                S/ {{ number_format($fila['costo'], 4) }}
                            </td>
                            <td class="py-1.5 px-3 border-b border-border">
                                <x-button wire:click="eliminarDistribucion({{ $fila['id'] }})" size="xs" variant="danger">
                                    <i class="fa fa-remove"></i>
                                </x-button>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="13" class="py-10 text-center text-sm text-muted-foreground">
                            No hay salidas de combustible para el período seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-card>

    {{-- Modal gestión distribuciones --}}
    <x-dialog-modal wire:model.live="modalDistribucion" maxWidth="full">
        <x-slot name="title">
            Gestionar Distribuciones
            @php
                $salidaActiva = collect($filas)->first(
                    fn($f) => $f['es_salida'] && $f['salida_id'] === $salidaActivaId
                );
            @endphp
            @if($salidaActiva)
                <span class="ml-2 text-sm font-normal text-muted-foreground">
                    — {{ $salidaActiva['maquinaria_nombre'] }}
                    · {{ \Carbon\Carbon::parse($salidaActiva['fecha'])->format('d/m/Y') }}
                    · {{ number_format($salidaActiva['ingreso_salida'], 2) }} L
                </span>
            @endif
        </x-slot>

        <x-slot name="content">
            {{-- wire:ignore: Livewire no debe tocar el DOM del Handsontable --}}
            <div wire:ignore class="w-full h-[300px] overflow-auto">
                <div id="modalTableContainer"></div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('modalDistribucion', false)">
                Cancelar
            </x-button>
            <x-button class="ms-3" @click="guardarModal()">
                <i class="fa fa-save mr-1"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('distribucionCombustible', () => ({
        // Entangle con propiedades Livewire
        modalAbierto: @entangle('modalDistribucion'),
        distribucionesActivas: @entangle('distribucionesActivas'),

        // Datos de listas (solo lectura, no necesitan entangle)
        listaCampos: @js($listaCampos),
        listaMaquinarias: @js($listaMaquinarias),

        isDark: JSON.parse(localStorage.getItem('darkMode') ?? 'false'),
        hotModal: null,
        filasModificadasModal: [],

        init() {
            // Cuando el modal pasa a true → esperar que el DOM esté pintado → montar Handsontable
            /*this.$watch('modalAbierto', (abierto) => {
                if (abierto) {
                    // $nextTick espera el re-render de Alpine/Livewire,
                    // setTimeout da margen para la transición CSS del dialog
                    this.$nextTick(() => {
                        setTimeout(() => {
                            this.initModalTable(this.distribucionesActivas);
                        }, 100);
                    });
                } else {
                    this.destruirModalTable();
                }
            });*/

            // Si los datos del modal cambian con el modal ya abierto (recarga tras error)
            /*
            this.$watch('distribucionesActivas', (datos) => {
                if (this.modalAbierto && this.hotModal) {
                    this.hotModal.loadData(JSON.parse(JSON.stringify(datos)));
                }
            });*/

            // Sincronizar tema dark/light con el Handsontable del modal
            this.$watch('isDark', (val) => {
                if (this.hotModal) {
                    this.hotModal.updateSettings({
                        themeName: val ? 'ht-theme-main-dark' : 'ht-theme-main',
                        columns: this.getModalColumns(),
                    });
                }
            });

            Livewire.on('cargarDistribuciones', ({ distribuciones }) => {
                this.initModalTable(distribuciones);
            });
        },

        // ── Handsontable ────────────────────────────────────────────────────────

        initModalTable(data) {
            this.destruirModalTable();

            const container = document.getElementById('modalTableContainer');
            console.log(container);
            if (!container) return;

            this.filasModificadasModal = [];

            const hot = new Handsontable(container, {
                ...window.HstConfig,
                data: JSON.parse(JSON.stringify(data)), // copia para no mutar el entangle
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                colHeaders: true,
                rowHeaders: true,
                columns: this.getModalColumns(),
                stretchH: 'all',
                minSpareRows: 1,
                autoColumnSize: false,
                licenseKey: 'non-commercial-and-evaluation',

                afterChange: (changes, source) => {
                    if (source === 'loadData' || source === 'recalculado') return;
                    if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(source)) return;

                    changes?.forEach(([row, prop]) => {
                        if (prop === 'hora_inicio' || prop === 'hora_fin') {
                            this.recalcularHoras(row);
                        }
                        if (!this.filasModificadasModal.includes(row)) {
                            this.filasModificadasModal = [...this.filasModificadasModal, row];
                        }
                    });
                },
            });

            this.hotModal = hot;
            this.hotModal.render();
        },

        destruirModalTable() {
            if (this.hotModal) {
                try { this.hotModal.destroy(); } catch (e) { }
                this.hotModal = null;
            }
            this.filasModificadasModal = [];
        },

        recalcularHoras(row) {
            const inicio = this.hotModal.getDataAtRowProp(row, 'hora_inicio');
            const fin = this.hotModal.getDataAtRowProp(row, 'hora_fin');
            if (!inicio || !fin) return;

            const [h1, m1] = inicio.split(':').map(Number);
            const [h2, m2] = fin.split(':').map(Number);
            const horas = ((h2 * 60 + m2) - (h1 * 60 + m1)) / 60;

            if (horas > 0) {
                this.hotModal.setDataAtRowProp(
                    row, 'n_horas',
                    Math.round(horas * 100) / 100,
                    'recalculado'
                );
            }
        },

        getModalColumns() {
            const camposLabels = this.listaCampos.map(c => c.label);
            const camposRevMap = Object.fromEntries(this.listaCampos.map(c => [c.label, c.label]));

            /*
    
            const maquinasLabels = this.listaMaquinarias.map(m => m.label);
            const maquinasMap    = Object.fromEntries(this.listaMaquinarias.map(m => [m.label, m.id]));
            const maquinasRevMap = Object.fromEntries(this.listaMaquinarias.map(m => [m.id, m.label]));*/

            const autocompleteCol = (labels, map, revMap, prop, title, width) => ({
                data: prop, title, type: 'autocomplete',
                source: labels, strict: false, allowInvalid: false, filter: true, width,
                renderer(instance, td, row, col, p, value) {
                    td.classList.remove('!text-gray-400', 'italic', '!text-red-500');
                    if (!value && value !== 0) {
                        td.classList.add('!text-gray-400', 'italic');
                        td.innerText = 'Buscar...';
                        return;
                    }
                    const label = revMap[value] ?? revMap[String(value)];
                    td.innerText = label ?? ('⚠️ ' + value);
                    if (!label) td.classList.add('!text-red-500');
                },
                validator(value, callback) {
                    if (!value || value === '') return callback(true);
                    if (revMap[value] || revMap[String(value)]) return callback(true);
                    if (typeof value === 'string' && map[value]) {
                        setTimeout(() => {
                            this.instance.setDataAtCell(this.row, this.col, map[value], 'validator');
                        }, 0);
                        return callback(true);
                    }
                    callback(false);
                },
            });

            const T = Handsontable.renderers;

            return [
                {
                    data: 'fecha', title: 'FECHA', width: 100,
                    type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true,
                    renderer: T.TextRenderer,
                },
                {
                    data: 'hora_inicio', title: 'INICIO', width: 75,
                    type: 'time', timeFormat: 'HH:mm', correctFormat: true,
                    renderer: T.TextRenderer,
                },
                {
                    data: 'hora_fin', title: 'FIN', width: 75,
                    type: 'time', timeFormat: 'HH:mm', correctFormat: true,
                    renderer: T.TextRenderer,
                },
                {
                    data: 'n_horas', title: 'HORAS', width: 65,
                    type: 'numeric', numericFormat: { pattern: '0.00' },
                    readOnly: true, className: '!bg-muted',
                    renderer: T.NumericRenderer,
                },
                autocompleteCol(camposLabels, camposRevMap, camposRevMap, 'campo_nombre', 'CAMPO', 120),
                {
                    data: 'labor_diaria', title: 'LABOR DIARIA', width: 200,
                    type: 'text', renderer: T.TextRenderer,
                },
                //autocompleteCol(maquinasLabels, maquinasMap, maquinasRevMap, 'maquinaria_id', 'MAQUINARIA', 130),
            ];
        },

        // ── Guardar desde footer del modal ──────────────────────────────────────

        guardarModal() {
            if (!this.hotModal || this.filasModificadasModal.length === 0) {
                alert('Ninguna fila modificada.');
                return;
            }

            const data = [...this.filasModificadasModal]
                .map(i => this.hotModal.getSourceDataAtRow(i))
                .filter(f => f && Object.values(f).some(v => v !== null && v !== ''));

            $wire.guardarDistribuciones(data);
        },
    }));
</script>
@endscript