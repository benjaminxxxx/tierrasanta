{{-- reporte-diario-actividades-component.blade.php --}}
<div>
    <x-card>

        {{-- ══ HEADER ══ --}}
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-icon><i class="fa-solid fa-seedling"></i></x-icon>
                <div>
                    <x-title>Actividades del día</x-title>
                    <x-subtitle>
                        {{ \Carbon\Carbon::parse($fecha)->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                    </x-subtitle>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-button variant="ghost" wire:click="actualizar" wire:target="actualizar" title="Actualizar">
                    <i class="fa-solid fa-rotate"></i>
                    <span>Actualizar</span>
                </x-button>
                <x-button variant="export" wire:click="exportarPdf" title="Exportar PDF">
                    <i class="fa-solid fa-download"></i>
                    <span>Exportar PDF</span>
                </x-button>
            </div>
        </div>

        {{-- ══ SIN DATOS ══ --}}
        @if($sinDatos)
            <div class="flex flex-col items-center justify-center gap-1.5 px-6 py-14 text-center text-muted-foreground">
                <i class="fa-solid fa-seedling text-4xl opacity-70"></i>
                <x-subtitle>No hay actividades registradas para esta fecha.</x-subtitle>
            </div>
        @else

            {{-- ══ MÉTRICAS ══ --}}
            <div class="grid grid-cols-1 border-b border-white/[0.07] md:grid-cols-4 rounded-xl overflow-hidden mb-6">
                <x-rda-metric label="Actividades" value-class="text-card-foreground">
                    {{ $totalActividades }}
                </x-rda-metric>
                <x-rda-metric label="Planilla" value-class="text-sky-400">
                    {{ $totalPlanilla }}
                </x-rda-metric>
                <x-rda-metric label="Cuadrilla" value-class="text-amber-400">
                    {{ $totalCuadrilla }}
                </x-rda-metric>
                <x-rda-metric label="Métodos bonif." value-class="text-indigo-400">
                    {{ $totalMetodos }}
                </x-rda-metric>
            </div>

            {{-- ══ TABLA ══ --}}
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th>Campo</x-th>
                            <x-th>Código</x-th>
                            <x-th>Labor</x-th>
                            <x-th align="right">Unid.</x-th>
                            <x-th align="right">Métodos</x-th>
                            <x-th align="right">Planilla</x-th>
                            <x-th align="right">Cuadrilla</x-th>
                            <x-th align="right">Total</x-th>
                            <x-th></x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach($actividades as $item)
                            <x-tr>
                                <x-td>
                                    <span class="font-mono font-semibold">{{ $item['campo'] }}</span>
                                </x-td>
                                <x-td>
                                    <x-rda-badge color="#b18cce">{{ $item['codigo_labor'] }}</x-rda-badge>
                                </x-td>
                                <x-td>{{ $item['nombre_labor'] }}</x-td>
                                <x-td align="right">{{ $item['unidades'] ?? '—' }}</x-td>
                                <x-td align="right">
                                    @if($item['total_metodos'] > 0)
                                        <span class="text-indigo-400 font-semibold">{{ $item['total_metodos'] }}</span>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </x-td>
                                <x-td align="right">
                                    <span class="text-sky-400">{{ $item['total_planilla'] }}</span>
                                    <span class="ml-1 text-xs text-muted-foreground">({{ $item['pct_planilla'] }}%)</span>
                                </x-td>
                                <x-td align="right">
                                    <span class="text-amber-400">{{ $item['total_cuadrilla'] }}</span>
                                    <span class="ml-1 text-xs text-muted-foreground">({{ $item['pct_cuadrilla'] }}%)</span>
                                </x-td>
                                <x-td align="right" class="font-semibold">
                                    {{ $item['total_personas'] }}
                                </x-td>
                                <x-td>
                                    {{-- barra proporcional al total de personas --}}
                                    <x-rda-bar
                                        :pct="$item['total_personas'] > 0 ? min(($item['total_personas'] / max(array_column($actividades, 'total_personas'))) * 100, 100) : 0"
                                        color="#6366f1" />
                                </x-td>
                            </x-tr>
                        @endforeach
                    </x-slot>
                </x-table>
            </div>
        @endif
    </x-card>
</div>