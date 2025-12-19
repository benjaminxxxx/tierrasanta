<div class="space-y-4" x-data="costosMensuales">
    <x-card>
        <x-flex class="justify-between">
            <div>
                <x-h3>Costos Mensuales</x-h3>
                <x-label>
                    Gestión y análisis de costos blanco y negro
                </x-label>
            </div>
            <x-button @click="$wire.dispatch('agregarCostoMensual')">
                <i class="fa fa-plus"></i> Agregar Costo
            </x-button>
        </x-flex>
    </x-card>
    <x-card>
        <x-flex>

            {{-- Año --}}
            <x-select label="Año" wire:model.live="filtroAnio">
                <option value="">Todos</option>
                @foreach ($aniosDisponibles as $anio)
                    <option value="{{ $anio }}">{{ $anio }}</option>
                @endforeach
            </x-select>

            {{-- Mes --}}
            <x-select label="Mes" wire:model.live="filtroMes">
                <option value="">Todos</option>
                @foreach ($meses as $index => $mes)
                    <option value="{{ $index + 1 }}">
                        {{ $mes }}
                    </option>
                @endforeach
            </x-select>

            {{-- Tipo de costo --}}
            <x-select label="Tipo de Costo" wire:model.live="selectedType">
                <option value="blanco">Blanco</option>
                <option value="negro">Negro</option>
            </x-select>

            {{-- Categoría --}}
            <x-select label="Categoría" wire:model.live="selectedCategory">
                <option value="todos">Todos</option>
                <option value="fijo">Fijo</option>
                <option value="operativo">Operativo</option>
            </x-select>

        </x-flex>
    </x-card>
    <x-card>
        <x-table>

            {{-- THEAD --}}
            <x-slot name="thead">
                {{-- Fila 1 --}}
                <x-tr class="bg-muted/50">
                    <x-th rowspan="2" class="text-left">
                        Período
                    </x-th>

                    @if ($selectedCategory === 'fijo' || $selectedCategory === 'todos')
                        <x-th colspan="5" class="text-center font-bold bg-blue-500/10">
                            FIJO
                        </x-th>
                    @endif

                    @if ($selectedCategory === 'operativo' || $selectedCategory === 'todos')
                        <x-th colspan="2" class="text-center font-bold bg-purple-500/10">
                            OPERATIVO
                        </x-th>
                    @endif

                    <x-th rowspan="2" class="text-right font-bold">
                        TOTAL
                    </x-th>
                    <x-th rowspan="2" class="text-right font-bold">
                        ACCIONES
                    </x-th>
                </x-tr>

                {{-- Fila 2 --}}
                <x-tr class="bg-muted/50">
                    @if ($selectedCategory === 'fijo' || $selectedCategory === 'todos')
                        <x-th class="text-right">Administrativo</x-th>
                        <x-th class="text-right">Financiero</x-th>
                        <x-th class="text-right">Gastos Oficina</x-th>
                        <x-th class="text-right">Depreciaciones</x-th>
                        <x-th class="text-right">Costo Terreno</x-th>
                    @endif

                    @if ($selectedCategory === 'operativo' || $selectedCategory === 'todos')
                        <x-th class="text-right">Servicios Fundo</x-th>
                        <x-th class="text-right">Mano Obra Indirecta</x-th>
                    @endif
                </x-tr>
            </x-slot>

            {{-- TBODY --}}
            <x-slot name="tbody">
                @foreach ($filteredData as $item)
                    @php
                        $getValue = fn($blanco, $negro) =>
                            $selectedType === 'blanco' ? $blanco : $negro;

                        $totalFijo =
                            $getValue($item->fijo_administrativo_blanco, $item->fijo_administrativo_negro) +
                            $getValue($item->fijo_financiero_blanco, $item->fijo_financiero_negro) +
                            $getValue($item->fijo_gastos_oficina_blanco, $item->fijo_gastos_oficina_negro) +
                            $getValue($item->fijo_depreciaciones_blanco, $item->fijo_depreciaciones_negro) +
                            $getValue($item->fijo_costo_terreno_blanco, $item->fijo_costo_terreno_negro);

                        $totalOperativo =
                            $getValue($item->operativo_servicios_fundo_blanco, $item->operativo_servicios_fundo_negro) +
                            $getValue($item->operativo_mano_obra_indirecta_blanco, $item->operativo_mano_obra_indirecta_negro);

                        $totalGeneral = $totalFijo + $totalOperativo;
                    @endphp

                    <x-tr class="hover:bg-muted/30 transition-colors">

                        {{-- Periodo --}}
                        <x-td class="font-medium">
                            {{ $meses[$item->mes - 1] }} {{ $item->anio }}
                        </x-td>

                        {{-- FIJO --}}
                        @if ($selectedCategory === 'fijo' || $selectedCategory === 'todos')
                            <x-td class="text-right">
                                {{ formatear_numero($getValue($item->fijo_administrativo_blanco, $item->fijo_administrativo_negro)) }}
                            </x-td>
                            <x-td class="text-right">
                                {{ formatear_numero($getValue($item->fijo_financiero_blanco, $item->fijo_financiero_negro)) }}
                            </x-td>
                            <x-td class="text-right">
                                {{ formatear_numero($getValue($item->fijo_gastos_oficina_blanco, $item->fijo_gastos_oficina_negro)) }}
                            </x-td>
                            <x-td class="text-right">
                                {{ formatear_numero($getValue($item->fijo_depreciaciones_blanco, $item->fijo_depreciaciones_negro)) }}
                            </x-td>
                            <x-td class="text-right">
                                {{ formatear_numero($getValue($item->fijo_costo_terreno_blanco, $item->fijo_costo_terreno_negro)) }}
                            </x-td>
                        @endif

                        {{-- OPERATIVO --}}
                        @if ($selectedCategory === 'operativo' || $selectedCategory === 'todos')
                            <x-td class="text-right">
                                {{ formatear_numero($getValue($item->operativo_servicios_fundo_blanco, $item->operativo_servicios_fundo_negro)) }}
                            </x-td>
                            <x-td class="text-right">
                                {{ formatear_numero($getValue($item->operativo_mano_obra_indirecta_blanco, $item->operativo_mano_obra_indirecta_negro)) }}
                            </x-td>
                        @endif

                        {{-- TOTAL --}}
                        <x-td class="text-right font-bold">
                            {{ formatear_numero($totalGeneral) }}
                        </x-td>
                        <x-td>
                            <div class="ms-3 relative">
                                <x-dropdown align="right" width="60">
                                    <x-slot name="trigger">
                                        <span class="inline-flex rounded-md">
                                            <button type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                
                                                Opciones
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                </svg>
                                            </button>
                                        </span>
                                    </x-slot>

                                    <x-slot name="content">
                                        <div class="w-60">
                                            <x-dropdown-link @click="$wire.dispatch('distribuirCostosMensuales',{costoMensualId:{{ $item->id }}})">
                                                Distribuir Costos
                                            </x-dropdown-link>
                                            <x-dropdown-link @click="$wire.dispatch('verDistribucionCostosMensuales',{costoMensualId:{{ $item->id }}})">
                                                Ver Distribución
                                            </x-dropdown-link>
                                        </div>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </x-td>
                    </x-tr>
                @endforeach
            </x-slot>

        </x-table>
    </x-card>

    <x-card>
        <x-h3>Cuadro Estadístico Anual</x-h3>
        <div class="mt-4 w-full" wire:ignore>
            <canvas id="graficoCostosAnual" class="!w-full"></canvas>
        </div>
    </x-card>
    <x-loading wire:loading />
</div>

@script
<script>
    Alpine.data('costosMensuales', () => ({
        chart: null,

        init() {
            // Renderizar gráfico con datos iniciales del año actual
            this.renderChart(@json($estadisticaData));

            // Escuchar evento de Livewire para actualizar tabla y gráfico
            Livewire.on('refrescarTablaCostosMensuales', (estadisticaActualizada) => {
                console.log(estadisticaActualizada[0]);
                if (this.chart) this.chart.destroy();
                this.renderChart(estadisticaActualizada[0]);
            });
        },

        renderChart(data) {
            const anioSeleccionado = @json($filtroAnio ?? date('Y'));
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

            // Etiquetas: siempre 12 meses
            const labels = meses;

            // Mapear datos del año seleccionado; si no hay datos en un mes, usar 0
            const blancoData = data.map(item => item.blanco || 0);
            const negroData = data.map(item => item.negro || 0);
            const totalData = data.map(item => item.total || 0);

            const ctx = document.getElementById('graficoCostosAnual').getContext('2d');
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Costos Blanco',
                            data: blancoData,
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                        },
                        {
                            label: 'Costos Negro',
                            data: negroData,
                            borderColor: 'rgb(245, 158, 11)',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.3,
                        },
                        {
                            label: 'Total',
                            data: totalData,
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            tension: 0.3,
                            borderWidth: 2,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return 'S/ ' + (context.raw || 0).toLocaleString();
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: `Evolución de Costos - Año ${anioSeleccionado}`,
                            font: { size: 16 }
                        }
                    },
                    scales: {
                        x: { ticks: { color: '#9CA3AF' }, grid: { color: '#374151' } },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => 'S/ ' + value.toLocaleString(),
                                color: '#9CA3AF'
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