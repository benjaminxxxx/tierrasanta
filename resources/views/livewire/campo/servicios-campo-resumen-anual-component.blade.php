<div x-data="serviciosCampoResumenAnual">
    <x-card class="space-y-4">
        <x-flex class="justify-between flex-wrap">
            <div>
                <x-h3>Resumen anual de servicios por labor</x-h3>
                <p class="text-xs text-muted-foreground">Costo asignado a campos (total pagado, IGV incluido), según la fecha del trabajo.</p>
            </div>
            <x-flex>
                <x-select-anios label="Año" wire:model.live="anio" class="w-auto" />
                <x-select label="Tipo de costo" wire:model.live="tipoCosto" class="w-auto">
                    <option value="">Todos</option>
                    @foreach (\App\Models\ServicioCampo::TIPOS_COSTO as $valor => $texto)
                        <option value="{{ $valor }}">{{ $texto }}</option>
                    @endforeach
                </x-select>
            </x-flex>
        </x-flex>

        @php
            $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            $fmt = fn($v) => $v ? number_format($v, 2) : '-';
        @endphp

        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th>Labor</x-th>
                    @foreach ($meses as $mes)
                        <x-th class="text-right">{{ $mes }}</x-th>
                    @endforeach
                    <x-th class="text-right">Total</x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @forelse ($resumen['labores'] ?? [] as $fila)
                    <x-tr>
                        <x-td class="!text-left font-semibold whitespace-nowrap">{{ $fila['labor'] }}</x-td>
                        @foreach ($fila['meses'] as $valor)
                            <x-td class="text-right tabular-nums">{{ $fmt($valor) }}</x-td>
                        @endforeach
                        <x-td class="text-right font-semibold tabular-nums">{{ $fmt($fila['total']) }}</x-td>
                    </x-tr>
                @empty
                    <x-tr>
                        <x-td colspan="14" class="text-center text-muted-foreground">Sin servicios registrados en {{ $anio }}.</x-td>
                    </x-tr>
                @endforelse
            </x-slot>
            <x-slot name="tfoot">
                <x-tr>
                    <x-td class="!text-left">TOTAL</x-td>
                    @foreach ($resumen['totales_mes'] ?? [] as $valor)
                        <x-td class="text-right tabular-nums">{{ $fmt($valor) }}</x-td>
                    @endforeach
                    <x-td class="text-right tabular-nums">{{ $fmt($resumen['total_anual'] ?? 0) }}</x-td>
                </x-tr>
            </x-slot>
        </x-table>

        <div class="relative h-80" wire:ignore>
            <canvas id="graficoServiciosCampo"></canvas>
        </div>
    </x-card>
</div>

@script
<script>
    Alpine.data('serviciosCampoResumenAnual', () => ({
        chart: null,
        colores: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#EC4899', '#84CC16', '#F97316', '#6366F1'],

        init() {
            this.renderChart(@json($resumen));
            $wire.on('actualizarGraficoServiciosCampo', ({ resumen }) => this.renderChart(resumen));
        },

        renderChart(resumen) {
            const canvas = document.getElementById('graficoServiciosCampo');
            if (!canvas) return;
            if (!window.Chart) { setTimeout(() => this.renderChart(resumen), 300); return; }

            const existing = Chart.getChart(canvas);
            if (existing) existing.destroy();

            const datasets = (resumen.labores || []).map((fila, i) => ({
                label: fila.labor,
                data: fila.meses,
                backgroundColor: this.colores[i % this.colores.length],
                stack: 'labores',
            }));

            this.chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: '#9CA3AF' } },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.dataset.label}: S/ ${(ctx.raw || 0).toLocaleString('es-PE', { minimumFractionDigits: 2 })}`,
                            },
                        },
                    },
                    scales: {
                        x: { stacked: true, ticks: { color: '#9CA3AF' }, grid: { color: '#374151' } },
                        y: {
                            stacked: true,
                            ticks: { color: '#9CA3AF', callback: (v) => 'S/ ' + v.toLocaleString('es-PE') },
                            grid: { color: '#374151' },
                        },
                    },
                },
            });
        },
    }));
</script>
@endscript
