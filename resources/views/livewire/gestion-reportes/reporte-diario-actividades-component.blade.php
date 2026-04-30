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

                {{-- Toggle agrupación --}}
                <div class="flex items-center rounded-lg border border-white/10 overflow-hidden text-xs">
                    <button wire:click="cambiarAgrupacion('actividad')"
                        @class([
                            'px-3 py-1.5 transition-colors',
                            'bg-white/10 text-white'                  => $agruparPor === 'actividad',
                            'text-muted-foreground hover:text-white'  => $agruparPor !== 'actividad',
                        ])>
                        <i class="fa-solid fa-list-check mr-1"></i>Por actividad
                    </button>
                    <button wire:click="cambiarAgrupacion('campo')"
                        @class([
                            'px-3 py-1.5 transition-colors',
                            'bg-white/10 text-white'                  => $agruparPor === 'campo',
                            'text-muted-foreground hover:text-white'  => $agruparPor !== 'campo',
                        ])>
                        <i class="fa-solid fa-map mr-1"></i>Por campo
                    </button>
                    <button wire:click="cambiarAgrupacion('sin_agrupar')"
                        @class([
                            'px-3 py-1.5 transition-colors',
                            'bg-white/10 text-white'                  => $agruparPor === 'sin_agrupar',
                            'text-muted-foreground hover:text-white'  => $agruparPor !== 'sin_agrupar',
                        ])>
                        <i class="fa-solid fa-table mr-1"></i>Sin agrupar
                    </button>
                </div>

                <x-button variant="ghost" wire:click="actualizar" wire:target="actualizar">
                    <i class="fa-solid fa-rotate"></i>
                    <span>Recalcular</span>
                </x-button>
                <x-button variant="export" wire:click="exportarPdf">
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

            {{-- ══ TABLA: POR ACTIVIDAD ══ --}}
            @if($vistaAgrupada['tipo'] === 'por_actividad')
                @php $maxPersonas = collect($vistaAgrupada['grupos'])->max('total_personas') ?: 1; @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10 text-xs text-muted-foreground uppercase tracking-wide">
                                <th class="text-left py-2 px-3">Actividad</th>
                                <th class="text-left py-2 px-3">Campos</th>
                                <th class="text-right py-2 px-3">Métodos</th>
                                <th class="text-right py-2 px-3">Planilla</th>
                                <th class="text-right py-2 px-3">Cuadrilla</th>
                                <th class="text-right py-2 px-3">Total</th>
                                <th class="py-2 px-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($vistaAgrupada['grupos'] as $grupo)
                                <tr class="hover:bg-white/[0.02]">
                                    {{-- Actividad: código + nombre --}}
                                    <td class="py-2.5 px-3">
                                        <div class="flex items-center gap-2">
                                            <x-rda-badge color="#e8c2d8">{{ $grupo['codigo_labor'] }}</x-rda-badge>
                                            <span>{{ $grupo['nombre_labor'] }}</span>
                                        </div>
                                    </td>

                                    {{-- Campos: chips uno al lado del otro --}}
                                    <td class="py-2.5 px-3">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($grupo['campos'] as $campo)
                                                <span class="inline-block rounded px-1.5 py-0.5 text-xs font-mono bg-white/10 text-white/80">
                                                    {{ $campo }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="py-2.5 px-3 text-right">
                                        @if($grupo['total_metodos'] > 0)
                                            <span class="text-indigo-400 font-semibold">{{ $grupo['total_metodos'] }}</span>
                                        @else
                                            <span class="text-muted-foreground">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <span class="text-sky-400">{{ $grupo['total_planilla'] }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <span class="text-amber-400">{{ $grupo['total_cuadrilla'] }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-semibold">
                                        {{ $grupo['total_personas'] }}
                                    </td>
                                    <td class="py-2.5 px-3 w-20">
                                        <x-rda-bar
                                            :pct="round(($grupo['total_personas'] / $maxPersonas) * 100)"
                                            color="#6366f1" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            {{-- ══ TABLA: POR CAMPO ══ --}}
            @elseif($vistaAgrupada['tipo'] === 'por_campo')
                @php $maxPersonas = collect($vistaAgrupada['grupos'])->max('total_personas') ?: 1; @endphp
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/10 text-xs text-muted-foreground uppercase tracking-wide">
                                <th class="text-left py-2 px-3">Campo</th>
                                <th class="text-left py-2 px-3">Código</th>
                                <th class="text-left py-2 px-3">Labor</th>
                                <th class="text-right py-2 px-3">Métodos</th>
                                <th class="text-right py-2 px-3">Planilla</th>
                                <th class="text-right py-2 px-3">Cuadrilla</th>
                                <th class="text-right py-2 px-3">Total</th>
                                <th class="py-2 px-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vistaAgrupada['grupos'] as $grupo)
                                @foreach($grupo['actividades'] as $i => $act)
                                    <tr @class([
                                        'hover:bg-white/[0.02]',
                                        'border-t border-white/10' => $i === 0,
                                        'border-t border-white/5'  => $i > 0,
                                    ])>
                                        {{-- Campo: solo en la primera fila del grupo, con fondo sutil --}}
                                        @if($i === 0)
                                            <td class="py-2.5 px-3 font-mono font-bold text-base align-top"
                                                rowspan="{{ count($grupo['actividades']) }}">
                                                <div class="flex flex-col">
                                                    <span>{{ $grupo['campo'] }}</span>
                                                    <span class="text-xs font-normal text-muted-foreground mt-0.5">
                                                        {{ count($grupo['actividades']) }} {{ Str::plural('actividad', count($grupo['actividades'])) }}
                                                    </span>
                                                </div>
                                            </td>
                                        @endif

                                        <td class="py-2.5 px-3">
                                            <x-rda-badge color="#a0abde">{{ $act['codigo_labor'] }}</x-rda-badge>
                                        </td>
                                        <td class="py-2.5 px-3">{{ $act['nombre_labor'] }}</td>
                                        <td class="py-2.5 px-3 text-right">
                                            @if($act['total_metodos'] > 0)
                                                <span class="text-indigo-400 font-semibold">{{ $act['total_metodos'] }}</span>
                                            @else
                                                <span class="text-muted-foreground">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            <span class="text-sky-400">{{ $act['total_planilla'] }}</span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right">
                                            <span class="text-amber-400">{{ $act['total_cuadrilla'] }}</span>
                                        </td>
                                        <td class="py-2.5 px-3 text-right font-semibold">
                                            {{ $act['total_personas'] }}
                                        </td>
                                        <td class="py-2.5 px-3 w-20">
                                            <x-rda-bar
                                                :pct="round(($act['total_personas'] / $maxPersonas) * 100)"
                                                color="#6366f1" />
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- Subtotal del campo --}}
                                <tr class="border-t border-white/10 bg-white/[0.02] text-xs text-muted-foreground">
                                    <td class="py-1.5 px-3 font-mono font-bold text-white/60">{{ $grupo['campo'] }}</td>
                                    <td colspan="4" class="py-1.5 px-3 italic">Subtotal campo</td>
                                    <td class="py-1.5 px-3 text-right text-sky-400/70">{{ $grupo['total_planilla'] }}</td>
                                    <td class="py-1.5 px-3 text-right text-amber-400/70">{{ $grupo['total_cuadrilla'] }}</td>
                                    <td class="py-1.5 px-3 text-right font-semibold text-white/60">{{ $grupo['total_personas'] }}</td>
                                    <td class="py-1.5 px-3">
                                        <x-rda-bar
                                            :pct="round(($grupo['total_personas'] / $maxPersonas) * 100)"
                                            color="#f59e0b" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            {{-- ══ TABLA: SIN AGRUPAR ══ --}}
            @else
                @php $maxPersonas = collect($vistaAgrupada['filas'])->max('total_personas') ?: 1; @endphp
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
                            @foreach($vistaAgrupada['filas'] as $item)
                                <x-tr>
                                    <x-td>
                                        <span class="font-mono font-semibold">{{ $item['campo'] }}</span>
                                    </x-td>
                                    <x-td>
                                        <x-rda-badge color="#f4f6bd">{{ $item['codigo_labor'] }}</x-rda-badge>
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
                                        <x-rda-bar
                                            :pct="round(($item['total_personas'] / $maxPersonas) * 100)"
                                            color="#6366f1" />
                                    </x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                </div>
            @endif

        @endif
    </x-card>
</div>