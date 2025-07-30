<div x-data="gestion_cuadrilla_resumen_anual">

    <div class="space-y-6">
        {{-- Selector de Año y Métricas --}}
        <div class="flex flex-col lg:flex-row gap-6">
            <x-card2>
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
            </x-card2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1">
                <x-card2>
                    <div class="p-4 flex items-center gap-3">
                        <div class="p-2 bg-blue-600 rounded-lg">
                            <i class="fa fa-dollar-sign text-white h-5 w-5 text-center"></i>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Total Costos Anuales</p>
                            <p class="text-2xl font-bold text-white">S/ {{ number_format($totalCostoAnual) }}</p>
                        </div>
                    </div>
                </x-card2>

                <x-card2>
                    <div class="p-4 flex items-center gap-3">
                        <div class="p-2 bg-green-600 rounded-lg">
                            <i class="fa fa-line-chart text-white h-5 w-5 text-center"></i>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Total Bonos Anuales</p>
                            <p class="text-2xl font-bold text-white">S/ {{ number_format($totalBonoAnual) }}</p>
                        </div>
                    </div>
                </x-card2>

                <x-card2>
                    <div class="p-4 flex items-center gap-3">
                        <div class="p-2 bg-purple-600 rounded-lg">
                            <i class="fa fa-users text-white h-5 w-5 text-center"></i>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Total Sumado Anual</p>
                            <p class="text-2xl font-bold text-white">S/ {{ number_format($totalSumadoAnual) }}</p>
                        </div>
                    </div>
                </x-card2>
            </div>
        </div>

        {{-- Tabla de Estadísticas Mensuales --}}
        <x-card2 class="bg-gray-800 border-gray-700">
            <x-h3 class="text-white">Estadísticas Mensuales - Cuadrilleros {{ $anioSeleccionado }}</x-h3>

            <x-table class="text-sm mt-4">
                <x-slot name="thead">
                    <x-tr class="border-b border-gray-600">
                        <x-th class="text-left p-3 text-gray-300 font-medium">Concepto</x-th>
                        @foreach ($meses as $mes)
                            <x-th class="text-center p-3 text-gray-300 font-medium min-w-[100px]">{{ $mes }}</x-th>
                        @endforeach
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    <x-tr class="border-b border-gray-700 hover:bg-gray-750">
                        <x-td class="p-3 text-white font-medium">Total Costo</x-td>
                        @foreach ($resumenMensual as $mes)
                            <x-td class="text-center p-3 text-blue-400">S/ {{ number_format($mes['totalCosto']) }}</x-td>
                        @endforeach
                    </x-tr>
                    <x-tr class="border-b border-gray-700 hover:bg-gray-750">
                        <x-td class="p-3 text-white font-medium">Total Bono</x-td>
                        @foreach ($resumenMensual as $mes)
                            <x-td class="text-center p-3 text-green-400">S/ {{ number_format($mes['totalBono']) }}</x-td>
                        @endforeach
                    </x-tr>
                    <x-tr class="border-b border-gray-700 hover:bg-gray-750">
                        <x-td class="p-3 text-white font-medium">Total Sumado</x-td>
                        @foreach ($resumenMensual as $mes)
                            <x-td class="text-center p-3 text-purple-400 font-semibold">S/
                                {{ number_format($mes['totalSumado']) }}</x-td>
                        @endforeach
                    </x-tr>
                    <x-tr class="hover:bg-gray-750">
                        <x-td class="p-3 text-white font-medium">Reporte del Mes</x-td>
                        @foreach ($resumenMensual as $mes)
                            <x-td class="text-center p-3 text-gray-400 text-xs">
                                @if ($mes['reporteMes'])
                                    <x-button-a href="{{Storage::disk('public')->url($mes['reporteMes'])}}">
                                        <i class="fa fa-file-excel" aria-hidden="true"></i> Reporte
                                    </x-button-a>
                                @else
                                    -
                                @endif
                            </x-td>
                        @endforeach
                    </x-tr>
                </x-slot>
            </x-table>
        </x-card2>

        {{-- Gráfico de Línea --}}
        <x-card2 class="bg-gray-800 border-gray-700 w-full">
            <x-h3 class="text-white">Evolución Anual - Costos vs Bonos</x-h3>
            <div class="mt-4 w-full">
                <canvas id="graficoCuadrilla" class="!w-full"></canvas>
            </div>
        </x-card2>
    </div>

    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('gestion_cuadrilla_resumen_anual', () => ({
    chart: null,

    init() {
        this.renderChart(@json($resumenMensual));

        Livewire.on('actualizarGraficoCuadrilla',(data)=>{
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
                        label: 'Total Costos',
                        data: resumen.map(m => m.totalCosto),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        fill: false,
                        tension: 0.4
                    },
                    {
                        label: 'Total Bonos',
                        data: resumen.map(m => m.totalBono),
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