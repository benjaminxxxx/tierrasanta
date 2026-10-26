{{-- Costos → Mensual (DESIGN.md): campañas del mes con tarjeta y cierre, costos en Handsontable, distribución y Excel. --}}
@php
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:opacity-50';
    $btnChico = 'inline-flex h-7 items-center gap-1 rounded-[10px] border border-mist bg-paper px-2 text-xs font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:opacity-50';
    $chip = 'inline-flex items-center gap-1 rounded-[6px] px-1.5 py-0.5 text-[11px] font-medium';
    $chipAbierta = $chip . ' bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400';
    $chipCerrada = $chip . ' bg-fog text-graphite';
    $chipAviso = $chip . ' bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400';
    $chipError = $chip . ' bg-red-50 text-red-700 dark:bg-red-400/10 dark:text-red-400';
    $puedeGestionar = auth()->user()?->can(\App\Constants\Permisos::CONTABILIDAD_COSTO_MENSUAL_GESTIONAR);
    $filas = collect($campanias['filas']);
@endphp
<div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">

    {{-- Acciones del mes --}}
    <div class="flex flex-wrap items-center justify-end gap-2">
        <button type="button" class="{{ $btn2 }}" wire:click="exportar" title="Campañas con sus fechas, costos del mes y la distribución (si ya se aprobó)">
            <i class="fa fa-file-excel text-steel"></i> Exportar Excel
        </button>
        @if ($puedeGestionar)
            <button type="button" class="{{ $btnPrincipal }}" @disabled(!$costos['costo_mensual_id'])
                @if ($costos['costo_mensual_id']) @click="$wire.dispatch('distribuirCostosMensuales', {costoMensualId: {{ $costos['costo_mensual_id'] }}})" @endif
                title="{{ $costos['costo_mensual_id'] ? 'Prorratea los costos del mes entre las campañas por sus días activos' : 'Guarda primero los costos del mes' }}">
                <i class="fa fa-diagram-project"></i> Distribuir costos
            </button>
        @endif
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-[10px] border border-mist bg-paper p-4">
            <p class="text-xs text-pewter">Campañas en el mes</p>
            <p class="text-2xl font-semibold tabular-nums text-ink">{{ $resumen['campanias'] }}</p>
            <p class="text-xs text-graphite">{{ $resumen['abiertas'] }} abiertas</p>
        </div>
        <div class="rounded-[10px] border border-mist bg-paper p-4">
            <p class="text-xs text-pewter">Área en producción</p>
            <p class="text-2xl font-semibold tabular-nums text-ink">{{ number_format($resumen['area'], 2) }} <span class="text-sm font-medium text-graphite">ha</span></p>
        </div>
        <div class="rounded-[10px] border border-mist bg-paper p-4">
            <p class="text-xs text-pewter">Por cerrar</p>
            <p class="text-2xl font-semibold tabular-nums {{ $resumen['por_cerrar'] ? 'text-amber-700 dark:text-amber-400' : 'text-ink' }}">{{ $resumen['por_cerrar'] }}</p>
            <p class="text-xs text-graphite">{{ $resumen['con_avisos'] ? $resumen['con_avisos'] . ' con avisos de fechas' : 'Sin avisos de fechas' }}</p>
        </div>
        <div class="rounded-[10px] border border-mist bg-paper p-4">
            <p class="text-xs text-pewter">Distribución</p>
            @if ($costos['distribuido_en'])
                <p class="mt-1"><span class="{{ $chip }} bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400"><i class="fa fa-check"></i> Aprobada</span></p>
                <p class="mt-1 text-xs tabular-nums text-graphite">{{ $costos['distribuido_en'] }}</p>
            @else
                <p class="mt-1"><span class="{{ $chipAviso }}">Pendiente</span></p>
                <p class="mt-1 text-xs text-graphite">{{ $costos['existe'] ? 'Revisa los costos y distribuye.' : 'Aún no hay costos del mes.' }}</p>
            @endif
        </div>
    </div>

    {{-- Avisos de fechas de campañas (no dejan distribuir si hay superposición o campañas abiertas con otra posterior) --}}
    @if ($campanias['avisos'])
        <div class="rounded-[10px] border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-400/10 dark:text-amber-300">
            <p class="font-medium"><i class="fa fa-triangle-exclamation"></i> Revisa las fechas de estas campañas</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5 text-[13px]">
                @foreach ($campanias['avisos'] as $campo)
                    @foreach ($campo['errores'] as $error)
                        <li><span class="font-medium">{{ $campo['campo_nombre'] }}:</span> {{ $error['mensaje'] }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-5">

        {{-- Campañas del mes --}}
        <section class="rounded-[10px] border border-mist bg-paper xl:col-span-2">
            <header class="flex items-center justify-between border-b border-hairline px-4 py-3">
                <div>
                    <h2 class="text-base font-semibold text-ink">Campañas del mes</h2>
                    <p class="text-xs text-pewter">Clic en la campaña para ver sus datos o cerrarla.</p>
                </div>
            </header>
            <div class="max-h-[32rem] overflow-auto">
                <table class="w-full text-[13px]">
                    <thead class="sticky top-0 z-10 bg-fog text-xs font-medium text-graphite">
                        <tr>
                            <th class="px-3 py-2 text-left">Campo</th>
                            <th class="px-3 py-2 text-left">Campaña</th>
                            <th class="px-3 py-2 text-right" title="Días de la campaña dentro del mes">Días</th>
                            <th class="px-3 py-2 text-left">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($filas as $f)
                            <tr class="border-t border-hairline hover:bg-fog/60" wire:key="cm-{{ $f['campo'] }}-{{ $f['campania_id'] ?? 'x' }}">
                                <td class="px-3 py-2">
                                    <span class="font-medium text-ink">{{ $f['campo'] }}</span>
                                    <span class="block text-[11px] tabular-nums text-pewter">{{ rtrim(rtrim(number_format($f['area'], 4), '0'), '.') }} ha</span>
                                </td>
                                @if ($f['campania_id'])
                                    <td class="px-3 py-2">
                                        <button type="button" class="text-left font-medium text-ink underline-offset-2 hover:underline focus:outline-none focus:ring-[3px] focus:ring-carmin/25 rounded"
                                            wire:click="verCampania({{ $f['campania_id'] }})">{{ $f['campania'] }}</button>
                                        <span class="block text-[11px] tabular-nums text-pewter">
                                            {{ formatear_fecha($f['fecha_inicio']) }} → {{ $f['fecha_fin'] ? formatear_fecha($f['fecha_fin']) : 'abierta' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums text-ink">{{ $f['dias'] }}<span class="text-pewter">/{{ $f['dias_mes'] }}</span></td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-1">
                                            @if ($f['fecha_fin'])
                                                <span class="{{ $chipCerrada }}">Cerrada</span>
                                            @else
                                                <span class="{{ $chipAbierta }}">Abierta</span>
                                                @if ($f['por_cerrar'])
                                                    <span class="{{ $chipAviso }}" title="La cosecha terminó: conviene cerrar la campaña"><i class="fa fa-flag-checkered"></i> Por cerrar</span>
                                                @endif
                                            @endif
                                            @if ($f['avisos'])
                                                <span class="{{ $chipError }}" title="{{ collect($f['avisos'])->pluck('mensaje')->implode(' · ') }}"><i class="fa fa-triangle-exclamation"></i> Fechas</span>
                                            @endif
                                            @if ($f['bloqueado'])
                                                <span class="{{ $chipCerrada }}" title="Campo bloqueado: aparece porque tiene campaña en el mes"><i class="fa fa-lock"></i></span>
                                            @endif
                                        </div>
                                    </td>
                                @else
                                    <td class="px-3 py-2 text-pewter" colspan="2">Sin campaña en el mes</td>
                                    <td class="px-3 py-2">
                                        @if ($puedeGestionar)
                                            <button type="button" class="{{ $btnChico }}" wire:click="bloquearCampo(@js($f['campo']))"
                                                wire:confirm="¿Bloquear el campo {{ $f['campo'] }}? Ya no aparecerá en las listas ni selectores; su historia se conserva. Se puede desbloquear después."
                                                title="El campo ya no se usa: se oculta y se conserva su historia">
                                                <i class="fa fa-lock text-steel"></i> Bloquear
                                            </button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-6 text-center text-pewter">No hay campos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($campanias['bloqueados'])
                <footer class="border-t border-hairline px-4 py-2.5">
                    <button type="button" class="text-xs font-medium text-graphite hover:underline" wire:click="$toggle('verBloqueados')">
                        <i class="fa {{ $verBloqueados ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
                        {{ $verBloqueados ? 'Ocultar' : 'Ver' }} campos bloqueados ({{ count($campanias['bloqueados']) }})
                    </button>
                    @if ($verBloqueados)
                        <ul class="mt-2 divide-y divide-hairline text-[13px]">
                            @foreach ($campanias['bloqueados'] as $b)
                                <li class="flex items-center justify-between py-1.5" wire:key="bl-{{ $b['campo'] }}">
                                    <span class="text-graphite"><i class="fa fa-lock text-steel"></i> {{ $b['campo'] }}</span>
                                    @if ($puedeGestionar)
                                        <button type="button" class="{{ $btnChico }}" wire:click="bloquearCampo(@js($b['campo']), false)">Desbloquear</button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </footer>
            @endif
        </section>

        {{-- Costos del mes --}}
        <section class="rounded-[10px] border border-mist bg-paper xl:col-span-3" x-data="costosMensualesHst">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline px-4 py-3">
                <div>
                    <h2 class="text-base font-semibold text-ink">Costos del mes</h2>
                    <p class="text-xs text-pewter">{{ $puedeGestionar ? 'Escribe o pega los montos (S/) y guarda.' : 'Solo lectura.' }} Se reparten entre las campañas por sus días activos.</p>
                </div>
                @if ($puedeGestionar)
                    <button type="button" class="{{ $btnPrincipal }}" @click="guardar" wire:loading.attr="disabled" wire:target="guardarCostos">
                        <i class="fa fa-save"></i> Guardar costos
                    </button>
                @endif
            </header>
            <div class="p-4">
                <div wire:ignore>
                    <div x-ref="tabla"></div>
                </div>
                <div class="mt-3 space-y-1 text-xs text-graphite">
                    <p>
                        <i class="fa fa-circle-info text-steel"></i>
                        Mano de obra indirecta:
                        @if ($costos['moi_calculado'])
                            calculada el {{ $costos['moi_calculado']['en'] }}
                            @unless ($costos['moi_calculado']['concluyente'])
                                <span class="{{ $chipError }}">no concluyente</span>
                            @endunless
                            (no se edita aquí).
                        @else
                            se escribe a mano hasta que se calcule.
                        @endif
                        <a class="font-medium text-ink underline underline-offset-2" href="{{ route('costos.mano_obra_indirecta', ['anio' => $anio, 'mes' => $mes]) }}">
                            {{ $costos['moi_calculado'] ? 'Ver de dónde sale / recalcular' : 'Calcular con lo gastado en FDM' }}
                        </a>
                    </p>
                    @unless ($costos['existe'])
                        <p class="text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> Aún no hay costos guardados para este mes.</p>
                    @endunless
                </div>
            </div>
        </section>
    </div>

    {{-- Tarjeta de la campaña --}}
    <x-dialog-modal wire:model.live="mostrarTarjeta" maxWidth="lg">
        <x-slot name="title">
            @if ($tarjeta)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-lg font-semibold text-ink">{{ $tarjeta['campo'] }} · {{ $tarjeta['nombre'] }}</span>
                    @if ($tarjeta['fecha_fin'])
                        <span class="{{ $chipCerrada }}">Cerrada</span>
                    @else
                        <span class="{{ $chipAbierta }}">Abierta</span>
                    @endif
                </div>
            @endif
        </x-slot>
        <x-slot name="content">
            @if ($tarjeta)
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-pewter">Desde</dt>
                        <dd class="font-medium tabular-nums text-ink">{{ formatear_fecha($tarjeta['fecha_inicio']) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-pewter">Hasta</dt>
                        <dd class="font-medium tabular-nums text-ink">{{ $tarjeta['fecha_fin'] ? formatear_fecha($tarjeta['fecha_fin']) : 'Vigente (sin cierre)' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-pewter">Duración</dt>
                        <dd class="tabular-nums text-ink">{{ $tarjeta['dias_campania'] }} días{{ $tarjeta['fecha_fin'] ? '' : ' a hoy' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-pewter">Días en {{ sprintf('%02d/%d', $mes, $anio) }}</dt>
                        <dd class="tabular-nums text-ink">{{ $tarjeta['dias_mes'] }} de {{ $tarjeta['total_dias_mes'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-pewter">Área</dt>
                        <dd class="tabular-nums text-ink">{{ $tarjeta['area'] !== null ? number_format($tarjeta['area'], 2) . ' ha' : 'Sin área' }}</dd>
                    </div>
                </dl>

                <div class="mt-4 rounded-[10px] border border-hairline bg-fog/50 p-3">
                    <p class="mb-1 text-xs font-medium text-graphite">Cosecha</p>
                    @include('livewire.campania.partials.campanias-estado-cosecha', ['estado' => $tarjeta['fecha_fin'] ? array_merge($tarjeta['cosecha'], ['recomendar_cierre' => null]) : $tarjeta['cosecha']])
                </div>

                @foreach ($tarjeta['avisos'] as $aviso)
                    <p class="mt-3 rounded-[10px] bg-red-50 p-2.5 text-[13px] text-red-700 dark:bg-red-400/10 dark:text-red-400">
                        <i class="fa fa-triangle-exclamation"></i> {{ $aviso['mensaje'] }}
                    </p>
                @endforeach
            @endif
        </x-slot>
        <x-slot name="footer">
            <div class="flex w-full flex-wrap justify-end gap-2">
                <button type="button" class="{{ $btn2 }}" wire:click="$set('mostrarTarjeta', false)">Cerrar</button>
                @if ($tarjeta)
                    @can(\App\Constants\Permisos::CAMPAÑA_POR_CAMPO)
                        <a class="{{ $btn2 }}" href="{{ route('campania.por_campo', ['campania' => $tarjeta['id']]) }}" target="_blank">
                            <i class="fa fa-arrow-up-right-from-square text-steel"></i> Ver campaña
                        </a>
                    @endcan
                    @if (!$tarjeta['fecha_fin'])
                        @can(\App\Constants\Permisos::CAMPAÑA_GESTIONAR)
                            <button type="button" class="{{ $btnPrincipal }}" wire:click="cerrarCampania">
                                <i class="fa fa-flag-checkered"></i> Cerrar campaña
                            </button>
                        @endcan
                    @endif
                @endif
            </div>
        </x-slot>
    </x-dialog-modal>
</div>

@script
<script>
    Alpine.data('costosMensualesHst', () => ({
        hot: null,
        filas: @json($costos['filas']),
        puedeEditar: @json((bool) $puedeGestionar),
        isDark: JSON.parse(localStorage.getItem('darkMode') ?? 'false'),

        init() {
            this.crear();
            Livewire.on('costosMensualesRecargar', ({ filas }) => {
                this.filas = filas;
                this.hot?.loadData(this.conTotal(filas));
            });
        },

        // Fila TOTAL al final (solo lectura, no se guarda)
        conTotal(filas) {
            const datos = filas.map(f => ({ ...f }));
            const suma = (k) => datos.reduce((s, f) => s + (parseFloat(f[k]) || 0), 0);
            datos.push({ concepto: null, tipo: '', nombre: 'TOTAL', blanco: suma('blanco'), negro: suma('negro'), total: suma('blanco') + suma('negro'), esTotal: true });
            return datos;
        },

        crear() {
            const formato = { pattern: '0,0.00' };
            const puedeEditar = this.puedeEditar;
            this.hot = new Handsontable(this.$refs.tabla, {
                ...window.HstConfig,
                data: this.conTotal(this.filas),
                themeName: this.isDark ? 'ht-theme-main-dark' : 'ht-theme-main',
                rowHeaders: false,
                height: 'auto',
                columns: [
                    { data: 'tipo', title: 'Tipo', readOnly: true, width: 110 },
                    { data: 'nombre', title: 'Concepto', readOnly: true, width: 190 },
                    { data: 'blanco', title: 'Blanco (S/)', type: 'numeric', numericFormat: formato, width: 120 },
                    { data: 'negro', title: 'Negro (S/)', type: 'numeric', numericFormat: formato, width: 120 },
                    { data: 'total', title: 'Total (S/)', type: 'numeric', numericFormat: formato, readOnly: true, width: 120 },
                ],
                cells: function (row) {
                    const fila = this.instance.getSourceDataAtRow(row);
                    const props = {};
                    if (!puedeEditar || fila?.esTotal || fila?.bloqueado) {
                        props.readOnly = true;
                    }
                    if (fila?.esTotal) {
                        props.className = 'font-semibold';
                    } else if (fila?.bloqueado) {
                        props.className = 'text-pewter';
                    }
                    return props;
                },
                // Recalcula el total de la fila y la fila TOTAL sin recargar la tabla (no se pierde la celda activa)
                afterChange: (cambios, origen) => {
                    if (!cambios || origen === 'loadData' || origen === 'recalculo') return;
                    const hot = this.hot;
                    const ultima = hot.countSourceRows() - 1;
                    const num = (v) => parseFloat(v) || 0;
                    new Set(cambios.map(c => c[0])).forEach(r => {
                        if (r === ultima) return;
                        const f = hot.getSourceDataAtRow(r);
                        hot.setDataAtRowProp(r, 'total', num(f.blanco) + num(f.negro), 'recalculo');
                    });
                    const datos = hot.getSourceData().slice(0, ultima);
                    const b = datos.reduce((s, f) => s + num(f.blanco), 0);
                    const n = datos.reduce((s, f) => s + num(f.negro), 0);
                    hot.setDataAtRowProp([[ultima, 'blanco', b], [ultima, 'negro', n], [ultima, 'total', b + n]], 'recalculo');
                },
                licenseKey: 'non-commercial-and-evaluation',
            });
        },

        guardar() {
            const filas = this.hot.getSourceData()
                .filter(f => !f.esTotal && f.concepto)
                .map(f => ({ concepto: f.concepto, blanco: f.blanco, negro: f.negro }));
            $wire.guardarCostos(filas);
        },
    }));
</script>
@endscript
