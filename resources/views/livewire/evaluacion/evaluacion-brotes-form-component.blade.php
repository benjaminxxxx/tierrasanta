<div>
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Evaluación de Brotes x Piso
        </x-slot>

        <x-slot name="content">
            @php
                $n0 = fn($v) => number_format((float) $v, 0);
                $abierta = collect($evaluaciones)->firstWhere('id', $editando);
            @endphp

            {{-- 1. Campo, campaña y metros de cama (una sola línea) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <x-select-campo wire:model.live="campoSeleccionado" />
                @if ($campoSeleccionado)
                    <div class="md:col-span-2">
                        <x-select label="Campaña" wire:model.live="campaniaSeleccionada" fullWidth="true">
                            <option value="">-- Seleccione --</option>
                            @foreach ($campaniasDisponibles as $campaniaItem)
                                <option value="{{ $campaniaItem->id }}">
                                    {{ $campaniaItem->nombre_campania }} - {{ $campaniaItem->variedad_tuna }}
                                </option>
                            @endforeach
                        </x-select>
                    </div>
                @endif
                @if ($campania)
                    <div>
                        <x-input type="number" step="0.001" wire:model.live.debounce.500ms="metros_cama_ha" label="Metros de cama/ha (toda la campaña)"
                            error="metros_cama_ha" />
                    </div>
                @endif
            </div>
            @if ($campania)
                <p class="text-xs text-muted-foreground mt-1">Los metros de cama/ha son los mismos para todas las evaluaciones de la campaña: si los cambias, al guardar se aplican a todas.</p>
            @endif

            {{-- 2. Todas las evaluaciones de la campaña, por fecha, con su evolución --}}
            @if ($campania)
                <div class="mt-4 rounded-lg border border-border">
                    <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 bg-muted">
                        <span class="text-sm font-semibold">
                            Evaluaciones de {{ $campania->campo }} / {{ $campania->nombre_campania }}
                            <span class="font-normal text-muted-foreground">({{ count($evaluaciones) }})</span>
                        </span>
                        @if ($puedeCrear)
                            <x-button size="sm" wire:click="nuevaEvaluacion" :variant="$editando === 'nueva' ? 'default' : 'secondary'">
                                <i class="fa fa-plus"></i> Nueva evaluación
                            </x-button>
                        @endif
                    </div>
                    @if (count($evaluaciones))
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead class="text-muted-foreground">
                                    <tr class="border-b border-border">
                                        <th class="p-2 text-left">Fecha</th>
                                        <th class="p-2 text-left">Evaluador</th>
                                        <th class="p-2 text-center">Camas</th>
                                        @foreach ($promedios as $etiqueta)
                                            <th class="p-2 text-right">{{ $etiqueta }}</th>
                                        @endforeach
                                        <th class="p-2 text-left">Registró / editó</th>
                                        <th class="p-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($evaluaciones as $e)
                                        <tr class="border-b border-border {{ (string) $editando === (string) $e['id'] ? 'bg-primary/10' : '' }}" wire:key="ev-{{ $e['id'] }}">
                                            <td class="p-2 whitespace-nowrap font-semibold">{{ \Illuminate\Support\Carbon::parse($e['fecha'])->format('d/m/Y') }}</td>
                                            <td class="p-2">{{ $e['evaluador'] }}</td>
                                            <td class="p-2 text-center">{{ $e['camas'] }}</td>
                                            @foreach ($promedios as $clave => $etiqueta)
                                                @php $p = $e['promedios'][$clave]; $total = str_contains($clave, 'total'); @endphp
                                                <td class="p-2 text-right tabular-nums whitespace-nowrap {{ $total ? 'font-bold' : '' }}">
                                                    {{ $n0($p['valor']) }}
                                                    {{-- Evolución contra la evaluación anterior --}}
                                                    @if ($p['diferencia'] !== null)
                                                        <span class="block text-[11px] font-normal {{ $p['diferencia'] > 0 ? 'text-green-700 dark:text-green-400' : ($p['diferencia'] < 0 ? 'text-red-700 dark:text-red-400' : 'text-muted-foreground') }}">
                                                            {{ $p['diferencia'] > 0 ? '+' : '' }}{{ $n0($p['diferencia']) }}
                                                        </span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="p-2 text-muted-foreground">
                                                {{ $e['registrado'] }}
                                                @if ($e['editado'])
                                                    <span class="block">Editó: {{ $e['editado'] }}</span>
                                                @endif
                                            </td>
                                            <td class="p-2 text-right whitespace-nowrap">
                                                <x-button size="xs" variant="secondary" wire:click="abrirEvaluacion({{ $e['id'] }})">
                                                    <i class="fa {{ $puedeEditar ? 'fa-edit' : 'fa-eye' }}"></i> {{ $puedeEditar ? 'Editar' : 'Ver' }}
                                                </x-button>
                                                @if ($puedeEliminar)
                                                    <x-button size="xs" variant="danger" wire:click="eliminarEvaluacion({{ $e['id'] }})"
                                                        wire:confirm="¿Eliminar la evaluación del {{ \Illuminate\Support\Carbon::parse($e['fecha'])->format('d/m/Y') }} con sus {{ $e['camas'] }} camas? Queda registrada en la auditoría.">
                                                        <i class="fa fa-trash"></i>
                                                    </x-button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="px-3 py-1 text-[11px] text-muted-foreground">Debajo de cada promedio: la diferencia con la evaluación anterior. La campaña muestra siempre la última.</p>
                    @else
                        <p class="p-3 text-sm text-muted-foreground">Esta campaña aún no tiene evaluaciones.</p>
                    @endif
                </div>
            @endif

            {{-- 3. Evaluación abierta: fecha, evaluador y tabla de camas (una sola tabla a la vez) --}}
            <div x-data="{{ $idTable }}" class="mt-4" x-show="$wire.editando !== null" x-cloak>
                @if ($editando !== null)
                    @php $soloVer = $editando !== 'nueva' && !$puedeEditar; @endphp
                    <div class="flex flex-wrap items-end gap-3">
                        <x-h3 class="mr-2">
                            {{ $editando === 'nueva' ? 'Nueva evaluación' : 'Evaluación del ' . \Illuminate\Support\Carbon::parse($abierta['fecha'] ?? $fecha)->format('d/m/Y') }}
                        </x-h3>
                        <x-selector-dia wire:model="fecha" error="fecha" label="Fecha de evaluación" :disabled="$soloVer" />
                        <x-group-field>
                            <x-autocomplete wire:model="evaluador" label="Evaluador" :sugerencias="$evaluadoresNombres" placeholder="Buscar evaluador..." />
                            <x-input-error for="evaluador" />
                        </x-group-field>
                        <div class="flex gap-2 ml-auto">
                            <x-button variant="secondary" wire:click="cerrarEditor">Cerrar evaluación</x-button>
                            @if (!$soloVer)
                                <x-button x-on:click="enviar()" wire:loading.attr="disabled" wire:target="guardarEvaluacion">
                                    <i class="fa fa-save"></i> {{ $editando === 'nueva' ? 'Registrar evaluación' : 'Guardar cambios' }}
                                </x-button>
                            @endif
                        </div>
                    </div>
                    <x-input-error for="metros_cama_ha" />
                    <x-input-error for="detalles" />
                    <x-input-error for="campania_id" />
                @endif
                <div wire:ignore class="mt-3">
                    <div x-ref="tableContainer" class="overflow-auto"></div>
                </div>
                <p class="text-[11px] text-muted-foreground mt-1">Las columnas grises son por hectárea: (brotes ÷ longitud de cama) × metros de cama/ha. Se recalculan al escribir.</p>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading wire:target="campoSeleccionado,campaniaSeleccionada,abrirEvaluacion,nuevaEvaluacion,guardarEvaluacion,eliminarEvaluacion" />
</div>
@script
    <script>
        // Nombre propio de este componente: otra tabla de la página no comparte datos ni eventos con esta
        Alpine.data('{{ $idTable }}', () => ({
            hot: null,
            isDark: JSON.parse(localStorage.getItem('darkMode')),
            init() {
                this.initTable();
                Livewire.on('cargarDataBrotesXPiso-{{ $idTable }}', ({ filas }) => {
                    this.hot.loadData(JSON.parse(JSON.stringify(filas || [])));
                    // La tabla pudo estar oculta: se vuelve a medir cuando ya se ve
                    setTimeout(() => { this.hot.refreshDimensions(); this.hot.render(); }, 60);
                });
            },
            porHectarea(valor, longitud) {
                const metros = parseFloat($wire.metros_cama_ha);
                const v = parseFloat(valor), l = parseFloat(longitud);
                return v >= 0 && l > 0 && metros > 0 ? Math.round(v / l * metros * 100) / 100 : null;
            },
            recalcular(row) {
                const f = this.hot.getSourceDataAtRow(row);
                if (!f) return;
                const set = (k, v) => this.hot.setDataAtRowProp(row, k, v, 'calculo');
                const a2 = this.porHectarea(f.brotes_aptos_2p_actual, f.longitud_cama);
                const d2 = this.porHectarea(f.brotes_aptos_2p_despues_n_dias, f.longitud_cama);
                const a3 = this.porHectarea(f.brotes_aptos_3p_actual, f.longitud_cama);
                const d3 = this.porHectarea(f.brotes_aptos_3p_despues_n_dias, f.longitud_cama);
                set('brotes_2p_actual_por_mt', a2);
                set('brotes_2p_despues_por_mt', d2);
                set('brotes_3p_actual_por_mt', a3);
                set('brotes_3p_despues_por_mt', d3);
                set('total_actual_por_mt', a2 === null && a3 === null ? null : (a2 || 0) + (a3 || 0));
                set('total_despues_por_mt', d2 === null && d3 === null ? null : (d2 || 0) + (d3 || 0));
            },
            initTable() {
                const num = { pattern: '0,0', culture: 'en-US' };
                const entrada = (data) => ({ data, type: 'numeric', numericFormat: num, className: '!text-center' });
                const calculada = (data, total = false) => ({
                    data, type: 'numeric', readOnly: true, numericFormat: num,
                    className: total ? (this.isDark ? '!text-center !bg-muted font-bold' : '!text-center !bg-[#FABF8F] htDimmed font-bold !text-black') : '!text-center !bg-muted',
                });

                this.hot = new Handsontable(this.$refs.tableContainer, {
                    data: [],
                    colHeaders: true,
                    themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                    rowHeaders: true,
                    columns: [
                        entrada('numero_cama'),
                        entrada('longitud_cama'),
                        entrada('brotes_aptos_2p_actual'), calculada('brotes_2p_actual_por_mt'),
                        entrada('brotes_aptos_2p_despues_n_dias'), calculada('brotes_2p_despues_por_mt'),
                        entrada('brotes_aptos_3p_actual'), calculada('brotes_3p_actual_por_mt'),
                        entrada('brotes_aptos_3p_despues_n_dias'), calculada('brotes_3p_despues_por_mt'),
                        calculada('total_actual_por_mt', true),
                        calculada('total_despues_por_mt', true),
                    ],
                    nestedHeaders: [
                        [
                            { label: 'N° DE<br/>CAMA<br/>MUESTREADA', colspan: 1 },
                            { label: 'LONGITUD<br/>CAMA<br/>(m)', colspan: 1 },
                            { label: 'N° ACTUAL<br/>BROTES<br/>APTOS<br/>2° PISO', colspan: 2 },
                            { label: 'BROTES<br/>APTOS 2° PISO<br/>DESPUÉS<br/>30 DÍAS', colspan: 2 },
                            { label: 'N° ACTUAL<br/>BROTES<br/>APTOS<br/>3° PISO', colspan: 2 },
                            { label: 'BROTES<br/>APTOS 3° PISO<br/>DESPUÉS<br/>30 DÍAS', colspan: 2 },
                            { label: 'TOTAL<br/>BROTES APTOS<br/>2° Y 3° PISO', colspan: 1 },
                            { label: 'TOTAL BROTES<br/>APTOS 2° Y<br/>3° PISO<br/>DESPUÉS 30 DÍAS', colspan: 1 },
                        ],
                        ['-', '-', '-', '/ha', '-', '/ha', '-', '/ha', '-', '/ha', '/ha', '/ha'],
                    ],
                    width: '100%',
                    height: 'auto',
                    manualColumnResize: false,
                    manualRowResize: true,
                    minSpareRows: 1,
                    stretchH: 'all',
                    autoColumnSize: true,
                    licenseKey: 'non-commercial-and-evaluation',
                    afterChange: (cambios, origen) => {
                        if (!cambios || origen === 'calculo' || origen === 'loadData') return;
                        [...new Set(cambios.map(([row]) => row))].forEach(row => this.recalcular(row));
                    },
                });
            },
            enviar() {
                const campos = ['numero_cama', 'longitud_cama', 'brotes_aptos_2p_actual', 'brotes_aptos_2p_despues_n_dias', 'brotes_aptos_3p_actual', 'brotes_aptos_3p_despues_n_dias'];
                const filas = [];
                for (let row = 0; row < this.hot.countRows(); row++) {
                    const f = this.hot.getSourceDataAtRow(row) || {};
                    if (campos.some(c => f[c] !== null && f[c] !== undefined && f[c] !== '')) {
                        filas.push(Object.fromEntries(campos.map(c => [c, f[c] ?? null])));
                    }
                }
                $wire.guardarEvaluacion(filas);
            },
        }));
    </script>
@endscript
