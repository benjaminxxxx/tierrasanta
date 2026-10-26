<div x-data="gestion_cuadrilla_resumen_anual">

    <div class="space-y-6">
        {{-- Selector de Año y Métricas --}}
        <div class="flex flex-col lg:flex-row gap-6">
            <x-card>
                <div class="pb-3">
                    <x-h3>
                        <i class="fa fa-calendar"></i> Seleccionar Año
                    </x-h3>
                </div>
                <div>
                    <x-select tab name="anioSeleccionado" wire:model.live="anioSeleccionado">
                        @foreach ($aniosDisponibles as $anio)
                            <option value="{{ $anio }}">{{ $anio }}</option>
                        @endforeach
                    </x-select>
                </div>
            </x-card>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1">
                <x-card>
                    <div class="p-4 flex items-center gap-3">
                        <div class="p-2 bg-blue-600 rounded-lg">
                            <i class="fa fa-dollar-sign text-card-foreground h-5 w-5 text-center"></i>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Total Jornales Anuales</p>
                            <p class="text-2xl font-bold text-card-foreground">S/ {{ number_format($totales['jornal']) }}
                            </p>
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="p-4 flex items-center gap-3">
                        <div class="p-2 bg-green-600 rounded-lg">
                            <i class="fa fa-line-chart text-card-foreground h-5 w-5 text-center"></i>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Total Bonos Anuales</p>
                            <p class="text-2xl font-bold text-card-foreground">S/ {{ number_format($totales['bono']) }}
                            </p>
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="p-4 flex items-center gap-3">
                        <div class="p-2 bg-purple-600 rounded-lg">
                            <i class="fa fa-users text-card-foreground h-5 w-5 text-center"></i>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Total Sumado Anual</p>
                            <p class="text-2xl font-bold text-card-foreground">S/ {{ number_format($totales['total']) }}
                            </p>
                        </div>
                    </div>
                </x-card>
            </div>
        </div>

        {{-- Tabla de Estadísticas Mensuales --}}
        <x-card>
            <x-h3 class="text-card-foreground">Estadísticas Mensuales - Cuadrilleros {{ $anioSeleccionado }}</x-h3>

            <x-table class="text-sm mt-4">
                <x-slot name="thead">
                    <x-tr>
                        <x-th>Concepto</x-th>
                        @foreach ($meses as $mes)
                            <x-th>{{ $mes }}</x-th>
                        @endforeach
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    <x-tr>
                        <x-td>Jornales</x-td>
                        @foreach ($resumenMensual as $mes)
                            <x-td>S/ {{ number_format($mes['jornal']) }}</x-td>
                        @endforeach
                    </x-tr>

                    <x-tr>
                        <x-td>Bonos</x-td>
                        @foreach ($resumenMensual as $mes)
                            <x-td>S/ {{ number_format($mes['bono']) }}</x-td>
                        @endforeach
                    </x-tr>

                    <x-tr>
                        <x-td class="font-semibold">Total</x-td>
                        @foreach ($resumenMensual as $mes)
                            <x-td class="font-semibold">S/ {{ number_format($mes['total']) }}</x-td>
                        @endforeach
                    </x-tr>

                    <x-tr>
                        <x-td>Días trabajados</x-td>
                        @foreach ($resumenMensual as $mes)
                            <x-td>{{ $mes['dias'] ?: '-' }}</x-td>
                        @endforeach
                    </x-tr>

                    <x-tr>
                        <x-td>Cuadrilleros</x-td>
                        @foreach ($resumenMensual as $mes)
                            <x-td>{{ $mes['cuadrilleros'] ?: '-' }}</x-td>
                        @endforeach
                    </x-tr>
                </x-slot>
            </x-table>
        </x-card>

        {{-- Gráfico de Línea --}}
        <x-card>
            <x-h3 class="text-card-foreground">Evolución Anual - Costos vs Bonos</x-h3>
            <div class="mt-4 w-full">
                <canvas id="graficoCuadrilla" class="!w-full"></canvas>
            </div>
        </x-card>
    </div>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('gestion_cuadrilla_resumen_anual', () => ({
        chart: null,

        init() {
            this.renderChart(@json($resumenMensual));

            Livewire.on('actualizarGraficoCuadrilla', (data) => {
                if (this.chart) {
                    this.chart.destroy();
                }
                this.renderChart(data[0]);
            });
        },

        renderChart(resumen) {
            const ctx = document.getElementById('graficoCuadrilla').getContext('2d');

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                    datasets: [
                        {
                            label: 'Jornales',
                            data: resumen.map(m => m.jornal),
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            fill: false,
                            tension: 0.4
                        },
                        {
                            label: 'Bonos',
                            data: resumen.map(m => m.bono),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.2)',
                            fill: false,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { labels: { color: 'white' } },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let value = context.raw || 0;
                                    return 'S/ ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { color: '#9CA3AF' }, grid: { color: '#374151' } },
                        y: {
                            ticks: {
                                color: '#9CA3AF',
                                callback: function (value) { return 'S/ ' + value.toLocaleString(); }
                            },
                            grid: { color: '#374151' }
                        }
                    }
                }
            });
        }
    }));
</script>
@endscript