{{-- reporte-anual-asistencias-component.blade.php --}}
<div x-data="asistenciasAnualChart">
    <x-card>

        {{-- ══ HEADER ══ --}}
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-icon><i class="fa-solid fa-calendar-check"></i></x-icon>
                <div>
                    <x-title>Asistencias del año</x-title>
                    <x-subtitle>Resumen anual {{ $anio }}</x-subtitle>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-button variant="ghost"
                          wire:click="actualizar"
                          wire:target="actualizar"
                          wire:loading.attr="disabled"
                          title="Recalcular todos los meses del año">
                    <i class="fa-solid fa-rotate" wire:loading.class="animate-spin" wire:target="actualizar"></i>
                    <span>Recalcular año</span>
                </x-button>
                <x-button variant="export" title="Exportar PDF" @click="exportar">
                    <i class="fa-solid fa-download"></i>
                    <span>Exportar PDF</span>
                </x-button>
            </div>
        </div>

        {{-- ══ SIN DATOS ══ --}}
        @if($sinDatos)
            <div class="flex flex-col items-center justify-center gap-1.5 px-6 py-14 text-center text-muted-foreground">
                <i class="fa-solid fa-database text-4xl opacity-70"></i>
                <x-subtitle>No hay datos para el año {{ $anio }}.</x-subtitle>
                <span class="text-xs">Presiona "Recalcular año" para generar desde los reportes diarios.</span>
            </div>
        @else

            {{-- ══ MÉTRICAS GLOBALES ══ --}}
            @php
                $pctAusent = $totalBaseAnio > 0
                    ? round(($ausentesAnio / $totalBaseAnio) * 100, 1) : 0;
            @endphp

            <div class="grid grid-cols-1 border-b border-white/[0.07] md:grid-cols-4 rounded-xl overflow-hidden mb-6">
                <x-rda-metric label="Base anual" value-class="text-card-foreground">
                    {{ number_format($totalBaseAnio) }}
                </x-rda-metric>
                <x-rda-metric label="Total asistencias" value-class="text-emerald-400" :pct="$pctAsistAnio">
                    {{ number_format($asistidosAnio) }}
                </x-rda-metric>
                <x-rda-metric label="Sin acumular" value-class="text-red-400" :pct="$pctAusent">
                    {{ number_format($ausentesAnio) }}
                </x-rda-metric>
                <x-rda-metric label="Días hábiles año" value-class="text-indigo-400">
                    {{ $diasHabilesAnio }}
                </x-rda-metric>
            </div>

            {{-- ══ GRÁFICO ANUAL + TABLA DE CÓDIGOS ══ --}}
            <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-4 mb-6">

                <div class="flex items-center justify-center">
                    <div class="relative size-[180px]">
                        <div wire:ignore>
                            <canvas id="chartAnual" class="size-full"></canvas>
                        </div>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-[24px] font-bold leading-none">{{ $pctAsistAnio }}%</span>
                            <span class="mt-0.5 text-[11px] text-muted-foreground">asistencia</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>Código</x-th>
                                <x-th>Descripción</x-th>
                                <x-th>Tipo</x-th>
                                <x-th align="right">Total año</x-th>
                                <x-th align="right">%</x-th>
                                <x-th></x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach($totalesAnio as $item)
                                <x-tr>
                                    <x-td>
                                        <x-rda-badge :color="$item['color']">{{ $item['codigo'] }}</x-rda-badge>
                                    </x-td>
                                    <x-td>
                                        {{ $item['descripcion'] }}
                                        @if(!$item['acumula'])
                                            <span class="text-xs text-red-400">no acumula</span>
                                        @endif
                                    </x-td>
                                    <x-td>{{ $item['tipo'] }}</x-td>
                                    <x-td align="right">{{ number_format($item['total']) }}</x-td>
                                    <x-td align="right">{{ $item['porcentaje'] }}%</x-td>
                                    <x-td>
                                        <x-rda-bar :pct="$item['porcentaje']" :color="$item['color']" />
                                    </x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                </div>
            </div>

            {{-- ══ DETALLE POR MES ══ --}}
            <div class="mt-2">
                <x-subtitle class="mb-3">Detalle por mes</x-subtitle>
                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>Mes</x-th>
                                <x-th align="right">Días háb.</x-th>
                                <x-th align="right">Empleados</x-th>
                                <x-th align="right">Base</x-th>
                                <x-th align="right">Asistencias</x-th>
                                <x-th align="right">Sin acum.</x-th>
                                <x-th align="right">% Asist.</x-th>
                                <x-th></x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach($meses as $m)
                                <x-tr>
                                    <x-td>{{ $m['nombre'] }}</x-td>

                                    @if($m['sinDatos'])
                                        <x-td colspan="7">
                                            <span class="text-xs text-muted-foreground italic">Sin datos</span>
                                        </x-td>
                                    @else
                                        <x-td align="right">{{ $m['diasHabiles'] }}</x-td>
                                        <x-td align="right">{{ $m['empleados'] }}</x-td>
                                        <x-td align="right">{{ number_format($m['totalBase']) }}</x-td>
                                        <x-td align="right" class="text-emerald-400">
                                            {{ number_format($m['asistidos']) }}
                                        </x-td>
                                        <x-td align="right" class="text-red-400">
                                            {{ number_format($m['ausentes']) }}
                                        </x-td>
                                        <x-td align="right">
                                            <span @class([
                                                'font-semibold',
                                                'text-emerald-400' => $m['pctAsist'] >= 85,
                                                'text-amber-400'   => $m['pctAsist'] >= 70 && $m['pctAsist'] < 85,
                                                'text-red-400'     => $m['pctAsist'] < 70,
                                            ])>{{ $m['pctAsist'] }}%</span>
                                        </x-td>
                                        <x-td>
                                            <x-rda-bar :pct="$m['pctAsist']"
                                                       :color="$m['pctAsist'] >= 85 ? '#10b981' : ($m['pctAsist'] >= 70 ? '#f59e0b' : '#ef4444')" />
                                        </x-td>
                                    @endif
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                </div>
            </div>

        @endif
    </x-card>
</div>

@script
<script>
    Alpine.data('asistenciasAnualChart', () => ({
        totales: @js($totalesAnio),
        total: @js($totalBaseAnio),
        chart: null,
        init() {
            this.$nextTick(() => this.buildChart());
            Livewire.on('resumenAnualActualizado', ({ totalesAnio }) => {
                this.refrescar(totalesAnio);
            });
        },
        destroy() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
        },
        buildChart() {
            const canvas = document.getElementById('chartAnual');
            if (!canvas) return;
            if (!window.Chart) { setTimeout(() => this.buildChart(), 300); return; }
            const existing = Chart.getChart(canvas);
            if (existing) existing.destroy();

            const labels = this.totales.map(t => t.descripcion);
            const data   = this.totales.map(t => t.total);
            const colors = this.totales.map(t => t.color);

            this.chart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data,
                        backgroundColor: colors.map(c => c + 'CC'),
                        borderColor: colors,
                        borderWidth: 1.5,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    cutout: '70%',
                    responsive: true,
                    animation: { duration: 600, easing: 'easeInOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e2233',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 10,
                            callbacks: {
                                label: ctx => {
                                    const pct = this.total > 0
                                        ? ((ctx.parsed / this.total) * 100).toFixed(1) : 0;
                                    return `  ${ctx.parsed.toLocaleString()} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        },
        exportar() {
            const img = this.chart ? this.chart.toBase64Image('image/png', 1) : '';
            $wire.exportarPdf(img);
        },
        refrescar(nuevosTotales) {
            this.totales = nuevosTotales;
            this.total   = nuevosTotales.reduce((s, t) => s + t.total, 0);
            this.$nextTick(() => this.buildChart());
        }
    }));
</script>
@endscript