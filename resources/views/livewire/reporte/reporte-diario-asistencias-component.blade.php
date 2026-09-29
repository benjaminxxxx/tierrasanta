{{-- Vista principal --}}
<div x-data="asistenciasChart">
    <x-card>

        {{-- ══ HEADER ══════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-icon>
                    <i class="fa-solid fa-user-clock"></i>
                </x-icon>
                <div>
                    <x-title>Asistencias del día</x-title>
                    <x-subtitle>
                        {{ \Carbon\Carbon::parse($fecha)->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </x-subtitle>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-button variant="ghost" wire:click="actualizar" wire:target="actualizar" title="Actualizar datos">
                    <i class="fa-solid fa-rotate"></i>
                    <span>Actualizar</span>
                </x-button>

                <x-button variant="export" title="Exportar PDF" @click="exportar">
                    <i class="fa-solid fa-download"></i>
                    <span>Exportar PDF</span>
                </x-button>
            </div>
        </div>

        {{-- ══ SIN DATOS ════════════════════════════════════════════════════ --}}
        @if($sinDatos)
            <div class="flex flex-col items-center justify-center gap-1.5 px-6 py-14 text-center text-muted-foreground">
                <i class="fa-solid fa-database text-4xl opacity-70"></i>
                <x-subtitle>No hay resumen generado para esta fecha.</x-subtitle>
                <span class="text-xs">Genera el reporte desde el módulo de planilla diaria.</span>
            </div>

        @else

            {{-- ══ MÉTRICAS RÁPIDAS ════════════════════════════════════════ --}}
            @php
                $asistidos = collect($totales)->where('acumula', 1)->sum('total');
                $ausentes = $totalPlanilla - $asistidos;
                $pctAsist = $totalPlanilla > 0 ? round(($asistidos / $totalPlanilla) * 100, 1) : 0;
                $pctAusent = $totalPlanilla > 0 ? round(($ausentes / $totalPlanilla) * 100, 1) : 0;
            @endphp

            <div class="grid grid-cols-1 border-b border-white/[0.07] md:grid-cols-4 rounded-xl overflow-hidden mb-6">
                <x-rda-metric label="Total planilla" value-class="text-card-foreground">
                    {{ $totalPlanilla }}
                </x-rda-metric>
                <x-rda-metric label="Acumulan asistencia" value-class="text-emerald-400" :pct="$pctAsist">
                    {{ $asistidos }}
                </x-rda-metric>
                <x-rda-metric label="Sin acumular" value-class="text-red-400" :pct="$pctAusent">
                    {{ $ausentes }}
                </x-rda-metric>
                <x-rda-metric label="Actividades" value-class="text-indigo-400">
                    {{ $resumen->total_actividades ?? 0 }}
                </x-rda-metric>
            </div>

            {{-- ══ CUERPO: GRÁFICO + TABLA ════════════════════════════════ --}}

            <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-4">

                {{-- Gráfico doughnut --}}
                <div class="flex items-center justify-center">
                    <div class="relative size-[180px]">
                        <div wire:ignore>
                            <canvas id="chartAsistencias" class="size-full"></canvas>
                        </div>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-[28px] font-bold leading-none">{{ $totalPlanilla }}</span>
                            <span class="mt-0.5 text-[11px] text-muted-foreground">empleados</span>
                        </div>
                    </div>
                </div>

                {{-- Tabla de desglose --}}
                <div class="overflow-x-auto">
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>Código</x-th>
                                <x-th>Descripción</x-th>
                                <x-th>Tipo</x-th>
                                <x-th align="right">Total</x-th>
                                <x-th align="right">%</x-th>
                                <x-th></x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach($totales as $item)
                                <x-tr>

                                    <x-td>
                                        <x-rda-badge :color="$item['color']">
                                            {{ $item['codigo'] }}
                                        </x-rda-badge>
                                    </x-td>

                                    <x-td>
                                        {{ $item['descripcion'] }}

                                        @if(!$item['acumula'])
                                            <span>no acumula</span>
                                        @endif
                                    </x-td>

                                    <x-td>
                                        {{ $item['tipo'] }}
                                    </x-td>

                                    <x-td align="right">
                                        {{ $item['total'] }}
                                    </x-td>

                                    <x-td align="right">
                                        {{ $item['porcentaje'] }}%
                                    </x-td>

                                    <x-td>
                                        <x-rda-bar :pct="$item['porcentaje']" :color="$item['color']" />
                                    </x-td>

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
    Alpine.data('asistenciasChart', () => ({
        totales: @js($totales),
        total: @js($totalPlanilla),
        chart: null,
        init() {
            this.$nextTick(() => this.buildChart());
            Livewire.on('resumenActualizado', ({ totales }) => {
                
                console.log(totales);
                this.refrescar(totales);
            });
        },
        destroy() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },
        buildChart() {
            const canvas = document.getElementById('chartAsistencias');
            if (!canvas) return;
            if (!window.Chart) {
                setTimeout(() => this.buildChart(), 300);
                return;
            }

            const existing = Chart.getChart(canvas);
            if (existing) existing.destroy();

            const labels = this.totales.map(t => t.descripcion);
            const data = this.totales.map(t => t.total);
            const colors = this.totales.map(t => t.color);
            const borders = this.totales.map(t => t.color);

            this.chart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data,
                        backgroundColor: colors.map(c => c + 'CC'),
                        borderColor: borders,
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
                            titleFont: { size: 13, weight: '600' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            callbacks: {
                                label: ctx => {
                                    const pct = this.total > 0
                                        ? ((ctx.parsed / this.total) * 100).toFixed(1)
                                        : 0;
                                    return `  ${ctx.parsed} empleados (${pct}%)`;
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
            this.total = nuevosTotales.reduce((s, t) => s + t.total, 0);
            this.$nextTick(() => this.buildChart());
        }
    }));
</script>
@endscript