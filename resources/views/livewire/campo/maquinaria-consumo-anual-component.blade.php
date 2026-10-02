<div x-data="maquinariaConsumoAnual">
    <x-dialog-modal wire:model="mostrar" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Consumo anual de combustible
                    @if ($reporte)
                        — {{ $reporte['maquinaria']['nombre'] }}
                    @endif
                </x-h3>
                <button type="button" wire:click="$set('mostrar', false)" class="focus:outline-none">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>
        </x-slot>
        <x-slot name="content">
            @php
                $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                $num = fn($v, $d = 2) => $v ? number_format($v, $d) : '-';
                $unidad = $reporte['maquinaria']['unidad'] ?? 'GAL';
            @endphp

            <div class="space-y-4">
                <x-flex class="justify-between flex-wrap">
                    <div class="text-xs text-muted-foreground space-y-1">
                        @if ($reporte)
                            <p>
                                Combustible: <b>{{ $reporte['maquinaria']['combustible'] ?? '—' }}</b> ·
                                Estimado de la ficha: <b>{{ $reporte['maquinaria']['consumo_texto'] ?? 'sin definir' }}</b>
                            </p>
                        @endif
                        <p>{{ $unidad }}: salidas del kardex negro por fecha de salida. Horas: distribuciones (campos y FDM) por fecha del trabajo.</p>
                        <p>Mes a mes el rendimiento varía (lo que sobra de una recarga se usa después); el acumulado del año es el consumo real por hora.</p>
                    </div>
                    <x-select-anios label="Año" wire:model.live="anio" class="w-auto" />
                </x-flex>

                <div wire:loading.flex wire:target="anio" class="text-sm text-muted-foreground items-center gap-2">
                    <i class="fa fa-spinner fa-spin"></i> Calculando…
                </div>

                @if ($reporte)
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <x-card class="!p-3">
                            <p class="text-xs text-muted-foreground">{{ $unidad }} recibidos</p>
                            <p class="text-xl font-bold tabular-nums">{{ $num($reporte['totales']['galones']) }}</p>
                            <p class="text-xs text-muted-foreground">{{ $reporte['totales']['salidas'] }} salida(s) · S/ {{ $num($reporte['totales']['costo']) }}</p>
                        </x-card>
                        <x-card class="!p-3">
                            <p class="text-xs text-muted-foreground">Horas distribuidas</p>
                            <p class="text-xl font-bold tabular-nums">{{ $num($reporte['totales']['horas']) }}</p>
                            <p class="text-xs text-muted-foreground">{{ $reporte['totales']['registros'] }} registro(s) · FDM {{ $num($reporte['totales']['horas_fdm']) }} h</p>
                        </x-card>
                        <x-card class="!p-3">
                            <p class="text-xs text-muted-foreground">Consumo real del año</p>
                            <p class="text-xl font-bold tabular-nums">{{ $reporte['promedio'] !== null ? number_format($reporte['promedio'], 3) : '-' }}</p>
                            <p class="text-xs text-muted-foreground">{{ $unidad }} por hora</p>
                        </x-card>
                        <x-card class="!p-3">
                            <p class="text-xs text-muted-foreground">Estimado de la ficha</p>
                            <p class="text-xl font-bold tabular-nums">{{ $reporte['estimado'] !== null ? number_format($reporte['estimado'], 3) : '-' }}</p>
                            <p class="text-xs text-muted-foreground">
                                @if ($reporte['estimado'] && $reporte['promedio'])
                                    Desvío {{ number_format(($reporte['promedio'] - $reporte['estimado']) / $reporte['estimado'] * 100, 1) }}%
                                @else
                                    {{ $unidad }} por hora
                                @endif
                            </p>
                        </x-card>
                    </div>

                    @foreach ($reporte['alertas'] as $alerta)
                        <x-alert :type="$alerta['variante']">{{ $alerta['texto'] }}</x-alert>
                    @endforeach

                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th>Mes</x-th>
                                <x-th class="text-right">Salidas</x-th>
                                <x-th class="text-right">{{ $unidad }}</x-th>
                                <x-th class="text-right">Costo S/</x-th>
                                <x-th class="text-right">Sin distribuir</x-th>
                                <x-th class="text-right">Horas</x-th>
                                <x-th class="text-right">Horas FDM</x-th>
                                <x-th class="text-right">{{ $unidad }}/h mes</x-th>
                                <x-th class="text-right">{{ $unidad }}/h acumulado</x-th>
                                <x-th>Observación</x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($reporte['meses'] as $fila)
                                <x-tr>
                                    <x-td class="!text-left font-semibold">{{ $meses[$fila['mes'] - 1] }}</x-td>
                                    <x-td class="text-right tabular-nums">{{ $fila['salidas'] ?: '-' }}</x-td>
                                    <x-td class="text-right tabular-nums">{{ $num($fila['galones']) }}</x-td>
                                    <x-td class="text-right tabular-nums">{{ $num($fila['costo']) }}</x-td>
                                    <x-td class="text-right tabular-nums {{ $fila['sin_distribuir'] ? 'text-amber-600 dark:text-amber-400' : '' }}">
                                        {{ $fila['sin_distribuir'] ? $fila['sin_distribuir'] . ' (' . $num($fila['galones_sin_distribuir']) . ')' : '-' }}
                                    </x-td>
                                    <x-td class="text-right tabular-nums">{{ $num($fila['horas']) }}</x-td>
                                    <x-td class="text-right tabular-nums">{{ $num($fila['horas_fdm']) }}</x-td>
                                    <x-td class="text-right tabular-nums">{{ $num($fila['rendimiento'], 3) }}</x-td>
                                    <x-td class="text-right tabular-nums font-semibold">{{ $num($fila['rendimiento_acumulado'], 3) }}</x-td>
                                    <x-td class="!text-left text-xs {{ $fila['alerta'] ? 'text-amber-700 dark:text-amber-400' : '' }}">{{ $fila['alerta'] }}</x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                        <x-slot name="tfoot">
                            <x-tr>
                                <x-td class="!text-left">TOTAL</x-td>
                                <x-td class="text-right tabular-nums">{{ $reporte['totales']['salidas'] }}</x-td>
                                <x-td class="text-right tabular-nums">{{ $num($reporte['totales']['galones']) }}</x-td>
                                <x-td class="text-right tabular-nums">{{ $num($reporte['totales']['costo']) }}</x-td>
                                <x-td class="text-right tabular-nums">
                                    {{ $reporte['totales']['sin_distribuir'] ? $reporte['totales']['sin_distribuir'] . ' (' . $num($reporte['totales']['galones_sin_distribuir']) . ')' : '-' }}
                                </x-td>
                                <x-td class="text-right tabular-nums">{{ $num($reporte['totales']['horas']) }}</x-td>
                                <x-td class="text-right tabular-nums">{{ $num($reporte['totales']['horas_fdm']) }}</x-td>
                                <x-td class="text-right tabular-nums" colspan="2">{{ $num($reporte['promedio'], 3) }}</x-td>
                                <x-td></x-td>
                            </x-tr>
                        </x-slot>
                    </x-table>
                @endif

                <div class="relative h-96" wire:ignore>
                    <canvas id="graficoConsumoMaquinaria"></canvas>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="$set('mostrar', false)">Cerrar</x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>

@script
<script>
    Alpine.data('maquinariaConsumoAnual', () => ({
        init() {
            $wire.on('graficoConsumoMaquinaria', ({ reporte }) => setTimeout(() => this.renderChart(reporte), 150));
        },

        renderChart(reporte) {
            const canvas = document.getElementById('graficoConsumoMaquinaria');
            if (!canvas || !reporte?.meses) return;
            if (!window.Chart) { setTimeout(() => this.renderChart(reporte), 300); return; }

            const existing = Chart.getChart(canvas);
            if (existing) existing.destroy();

            const unidad = reporte.maquinaria.unidad || 'GAL';
            const meses = reporte.meses;
            const fmt = (v, d = 2) => (v ?? 0).toLocaleString('es-PE', { maximumFractionDigits: d });
            const datasets = [
                {
                    type: 'bar', label: `${unidad} recibidos`, data: meses.map(m => m.galones),
                    backgroundColor: 'rgba(245, 158, 11, 0.75)', yAxisID: 'yGalones', order: 2,
                },
                {
                    type: 'bar', label: 'Horas distribuidas', data: meses.map(m => m.horas),
                    backgroundColor: 'rgba(59, 130, 246, 0.75)', yAxisID: 'yHoras', order: 3,
                },
                {
                    type: 'line', label: `${unidad}/h acumulado`, data: meses.map(m => m.rendimiento_acumulado),
                    borderColor: '#10B981', backgroundColor: '#10B981', yAxisID: 'yRendimiento',
                    tension: 0.3, spanGaps: true, order: 1,
                },
                {
                    type: 'line', label: `${unidad}/h del mes`, data: meses.map(m => m.rendimiento),
                    borderColor: '#9CA3AF', backgroundColor: '#9CA3AF', borderDash: [4, 4], yAxisID: 'yRendimiento',
                    pointStyle: 'rectRot', pointRadius: 4, showLine: false, order: 1,
                },
            ];
            if (reporte.estimado) {
                datasets.push({
                    type: 'line', label: `Estimado (${fmt(reporte.estimado, 3)} ${unidad}/h)`, data: meses.map(() => reporte.estimado),
                    borderColor: '#EF4444', borderDash: [6, 6], pointRadius: 0, yAxisID: 'yRendimiento', order: 0,
                });
            }

            new Chart(canvas, {
                data: {
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { labels: { color: '#9CA3AF' } },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ctx.raw === null ? null : `${ctx.dataset.label}: ${fmt(ctx.raw, 3)}`,
                            },
                        },
                    },
                    scales: {
                        x: { ticks: { color: '#9CA3AF' }, grid: { color: 'rgba(156, 163, 175, 0.2)' } },
                        yGalones: {
                            position: 'left', beginAtZero: true,
                            title: { display: true, text: unidad, color: '#F59E0B' },
                            ticks: { color: '#9CA3AF' }, grid: { color: 'rgba(156, 163, 175, 0.2)' },
                        },
                        yHoras: {
                            position: 'right', beginAtZero: true,
                            title: { display: true, text: 'Horas', color: '#3B82F6' },
                            ticks: { color: '#9CA3AF' }, grid: { drawOnChartArea: false },
                        },
                        yRendimiento: {
                            position: 'right', beginAtZero: true,
                            title: { display: true, text: `${unidad}/h`, color: '#10B981' },
                            ticks: { color: '#9CA3AF' }, grid: { drawOnChartArea: false },
                        },
                    },
                },
            });
        },
    }));
</script>
@endscript
