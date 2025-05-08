<div>
    <div style="width: 100%; overflow: auto;" x-data="processChart" wire:ignore>
        <canvas x-ref="canvasChart" width="700" height="450"></canvas>
    </div>
</div>
@script
    <script>
        Alpine.data('processChart', () => ({
            listeners: [],
            totalKilos: {{ $resumen->total_kilos }},
            venteadoKilos: {{ $resumen->total_venteado_kilos_ingresados ?? 0 }},
            filtradoKilos: {{ $resumen->total_filtrado_kilos_ingresados ?? 0 }},
            mermas: {
                ingresoAVenteado: {{ $resumen->merma_ingreso_venteado ?? 0 }},
                venteadoAFiltrado: {{ $resumen->merma_venteado_filtrado ?? 0 }},
                ingresoAFiltrado: {{ $resumen->merma_ingreso_filtrado ?? 0 }}
            },
            materialUtil: {
                venteado: {{ $resumen->material_util_venteado ?? 0 }},
                filtrado: {{ $resumen->material_util_filtrado ?? 0 }}
            },
            chartInstance: null,

            init() {
                this.iniciarGraficoChart();
                this.listeners.push(
                    Livewire.on('cargarDataMapaChart', (data) => {
                        console.log('Data recibida', data);
                        const resumen = data[0] ?? {};

                        this.totalKilos = parseFloat(resumen.total_kilos) ?? 0;
                        this.venteadoKilos = parseFloat(resumen.total_venteado_kilos_ingresados) ?? 0;
                        this.filtradoKilos = parseFloat(resumen.total_filtrado_kilos_ingresados) ?? 0;

                        this.mermas = {
                            ingresoAVenteado: parseFloat(resumen.merma_ingreso_venteado) ?? 0,
                            venteadoAFiltrado: parseFloat(resumen.merma_venteado_filtrado) ?? 0,
                            ingresoAFiltrado: parseFloat(resumen.merma_ingreso_filtrado) ?? 0
                        };

                        this.materialUtil = {
                            venteado: parseFloat(resumen.material_util_venteado) ?? 0,
                            filtrado: parseFloat(resumen.material_util_filtrado) ?? 0
                        };

                        if (this.chartInstance) {
                            this.chartInstance.destroy();
                        }
                        this.iniciarGraficoChart();
                    })
                );
            },

            iniciarGraficoChart() {
                const ctx = this.$refs.canvasChart.getContext('2d');

                const data = {
                    labels: ['Ingreso', 'Venteado', 'Filtrado'],
                    datasets: [{
                            label: 'Material útil',
                            data: [this.totalKilos, this.materialUtil.venteado, this.materialUtil
                                .filtrado
                            ],
                            backgroundColor: '#42A5F5', // Azul
                        },
                        {
                            label: 'Basura',
                            data: [0, this.venteadoKilos - this.materialUtil.venteado, this
                                .filtradoKilos - this.materialUtil.filtrado
                            ],
                            backgroundColor: '#B71C1C', // Rojo oscuro
                        },
                        {
                            label: 'Merma',
                            data: [0, this.mermas.ingresoAVenteado, this.mermas.venteadoAFiltrado],
                            backgroundColor: '#FFA726', // Naranja
                        }

                    ]

                };

                const config = {
                    type: 'bar',
                    data,
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Resumen del proceso de cochinilla'
                            },
                            legend: {
                                position: 'bottom'
                            },
                            datalabels: {
                                color: '#000',
                                anchor: 'end',
                                align: 'top',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        scales: {
                            x: {
                                stacked: true
                            },
                            y: {
                                stacked: true,
                                title: {
                                    display: true,
                                    text: 'Kilos'
                                }
                            }
                        }
                    }
                };

                this.chartInstance = new Chart(ctx, config);
            }
        }));
    </script>
@endscript
