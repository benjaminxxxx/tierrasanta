<div x-data="distribucionCostosMensuales">
    <x-dialog-modal wire:model="mostrarDistribucionesMensuales" maxWidth="full">
        <x-slot name="title">
            <x-h3>
                Distribución de Costos – {{ $mes>0?$meses[$mes-1]:'-' }} - {{ $anio }}
            </x-h3>
            <x-label>
                Visualización de cómo se distribuyeron los costos entre las campañas
            </x-label>
        </x-slot>

        <x-slot name="content">

            @php
                $maxTotal = collect($distribuciones)->max('total') ?: 1;
            @endphp

            <div class="overflow-x-auto">
                <div class="flex gap-4 min-w-max pb-4">
                    @foreach ($distribuciones as $campana)
                        @php
                            $calcularAltura = fn($v) => ($v / $maxTotal) * 100;
                        @endphp

                        <div class="flex flex-col items-center w-[100px]">

                            {{-- Barra tipo batería --}}
                            <div
                                class="w-full h-[280px] bg-slate-300 rounded-lg p-1 flex flex-col-reverse shadow-inner text-[10px] text-white">

                                @foreach ([
        'administrativo' => 'bg-emerald-500',
        'financiero' => 'bg-blue-500',
        'oficina' => 'bg-purple-500',
        'depreciaciones' => 'bg-amber-500',
        'terreno' => 'bg-orange-500',
        'servicios' => 'bg-cyan-500',
        'mano_obra' => 'bg-rose-500',
    ] as $key => $color)
                                    @php
                                        $valor = $campana['bloques'][$key] ?? 0;
                                    @endphp

                                    @if ($valor > 0)
                                        <div class="w-full {{ $color }} flex items-center justify-center rounded-sm"
                                            style="height: {{ $calcularAltura($valor) }}%"
                                            title="{{ ucfirst(str_replace('_', ' ', $key)) }}: S/ {{ number_format($valor, 2) }}">
                                            S/ {{ number_format($valor, 0) }}
                                        </div>
                                    @endif
                                @endforeach

                            </div>

                            {{-- Info campaña --}}
                            <div class="mt-3 w-full bg-gray-50 rounded-lg p-3 border text-[11px] space-y-1 dark:bg-indigo-600 dark:text-white">
                                <h4 class="font-semibold text-xs leading-tight line-clamp-2">
                                    {{ $campana['nombre'] }}
                                </h4>

                                <div class="flex justify-between">
                                    <span>Inicio:</span>
                                    <span class="font-medium">
                                        {{ \Carbon\Carbon::parse($campana['fecha_inicio'])->format('d/m') }}
                                    </span>
                                </div>

                                <div class="flex justify-between">
                                    <span>Fin:</span>
                                    <span class="font-medium">
                                        {{ \Carbon\Carbon::parse($campana['fecha_fin'])->format('d/m') }}
                                    </span>
                                </div>

                                <div class="flex justify-between">
                                    <span>Días:</span>
                                    <span class="font-medium">
                                        {{ $campana['dias_activos'] }}
                                    </span>
                                </div>

                                <div class="pt-1 mt-1 border-t flex justify-between font-semibold">
                                    <span>Total:</span>
                                    <span>S/ {{ number_format($campana['total'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{--     Leyenda --}}
            <x-flex class="mt-5">
                <x-legend color="bg-emerald-500" label="Administrativo" />
                <x-legend color="bg-blue-500" label="Financiero" />
                <x-legend color="bg-purple-500" label="Gastos Oficina" />
                <x-legend color="bg-amber-500" label="Depreciaciones" />
                <x-legend color="bg-orange-500" label="Costo Terreno" />
                <x-legend color="bg-cyan-500" label="Servicios Fundo" />
                <x-legend color="bg-rose-500" label="Mano de Obra" />
            </x-flex>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarDistribucionesMensuales', false)">
                Cerrar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('distribucionCostosMensuales', () => ({
            init() {

            }
        }));
    </script>
@endscript
