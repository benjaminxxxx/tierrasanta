{{-- Evaluación de brotes por piso (DESIGN.md): última evaluación de cada campaña, con el detalle por cama --}}
@php
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $icono = 'inline-flex h-8 w-8 items-center justify-center rounded-[10px] border border-mist bg-paper text-graphite hover:bg-fog hover:text-ink focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $n0 = fn($v) => $v === null ? '—' : number_format((float) $v, 0);
    $n2 = fn($v) => $v === null ? '—' : number_format((float) $v, 2);
    $th = 'px-3 py-2 font-medium';
    $td = 'px-3 py-2';
@endphp

<div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Evaluación de brotes por piso</h1>
            <p class="text-sm text-graphite">Última evaluación de cada campaña. Por ha = brotes ÷ longitud de cama × metros de cama por ha.</p>
        </div>
        @can(\App\Constants\Permisos::BROTE_EVALUACION_CREAR)
            <button type="button" class="{{ $btnPrincipal }}" @click="$wire.dispatch('agregarEvaluacionBrote')">
                <i class="fa fa-plus"></i> Agregar evaluación
            </button>
        @endcan
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap items-end justify-between gap-3 rounded-[10px] border border-mist bg-paper p-4">
        <div class="flex flex-wrap items-end gap-3">
            <x-selector-dia label="Fecha de evaluación" wire:model.live="fechaFiltro" />
            <div class="w-44"><x-select-campo wire:model.live="campoFiltrado" label="Lote" error="false" /></div>
            @if ($campoFiltrado)
                <div class="w-52">
                    <x-select label="Campaña" wire:model.live="campaniaFiltrada">
                        <option value="">Todas las campañas</option>
                        @foreach ($campaniasParaFiltro as $campaniaOption)
                            <option value="{{ $campaniaOption->id }}">{{ $campaniaOption->nombre_campania }}</option>
                        @endforeach
                    </x-select>
                </div>
            @endif
            <div class="w-52"><x-input type="search" label="Evaluador" wire:model.live.debounce.600ms="evaluadorFiltro" /></div>
        </div>
        @can(\App\Constants\Permisos::BROTE_EVALUACION_REPORTE)
            <button type="button" class="{{ $btn2 }}" wire:click="exportarReporteBrotesXPiso">
                <i class="fa fa-file-excel"></i> Exportar Excel
            </button>
        @endcan
    </div>

    @can(\App\Constants\Permisos::BROTE_EVALUACION_VER)
        <div class="overflow-x-auto rounded-[10px] border border-mist bg-paper" x-data="{ abiertos: {} }">
            <table class="w-full min-w-[72rem] text-[13px]">
                <thead class="bg-fog text-left text-xs text-graphite">
                    <tr>
                        <th class="{{ $th }} w-8 text-right">#</th>
                        <th class="{{ $th }}">Campo / campaña</th>
                        <th class="{{ $th }}">Última evaluación</th>
                        <th class="{{ $th }}">Evaluador</th>
                        <th class="{{ $th }} text-right">Mts cama/ha</th>
                        <th class="{{ $th }} text-right">2° piso<br>actual</th>
                        <th class="{{ $th }} text-right">2° piso<br>para eliminar</th>
                        <th class="{{ $th }} text-right">3° piso<br>actual</th>
                        <th class="{{ $th }} text-right">3° piso<br>para eliminar</th>
                        <th class="{{ $th }} text-right">4° piso<br>actual</th>
                        <th class="{{ $th }} text-right">4° piso<br>para eliminar</th>
                        <th class="{{ $th }} text-right text-ink">Total aptos<br>2°+3°+4°</th>
                        <th class="{{ $th }} text-right">Total para<br>eliminar</th>
                        <th class="{{ $th }} text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($evaluacionesBrotes as $index => $e)
                        @php $resaltada = $evaluacionResaltada === $e->id; @endphp
                        <tr wire:key="eval-brotes-{{ $e->id }}"
                            class="border-t border-hairline {{ $resaltada ? 'bg-amber-50 dark:bg-amber-400/10' : 'hover:bg-fog' }}"
                            @if ($resaltada) x-init="$el.scrollIntoView({ block: 'center' })" @endif>
                            <td class="{{ $td }} text-right tabular-nums text-pewter">{{ $evaluacionesBrotes->firstItem() + $index }}</td>
                            <td class="{{ $td }} font-medium text-ink">{{ $e->campania->campo }} · {{ $e->campania->nombre_campania }}</td>
                            <td class="{{ $td }} tabular-nums text-ink">
                                {{ formatear_fecha($e->fecha) }}
                                @if ($e->evaluaciones_campania > 1)
                                    <span class="block text-xs text-pewter">de {{ $e->evaluaciones_campania }} evaluaciones</span>
                                @endif
                            </td>
                            <td class="{{ $td }} text-ink">
                                {{ $e->evaluador }}
                                <span class="block text-xs text-pewter">{{ $e->actualizadoPor?->name ? 'Editó ' . $e->actualizadoPor->name : ($e->creadoPor?->name ? 'Registró ' . $e->creadoPor->name : '') }}</span>
                            </td>
                            <td class="{{ $td }} text-right tabular-nums">{{ $n0($e->metros_cama_ha) }}</td>
                            <td class="{{ $td }} text-right tabular-nums">{{ $n0($e->promedio_actual_brotes_2piso) }}</td>
                            <td class="{{ $td }} text-right tabular-nums text-graphite">{{ $n0($e->promedio_brotes_2piso_n_dias) }}</td>
                            <td class="{{ $td }} text-right tabular-nums">{{ $n0($e->promedio_actual_brotes_3piso) }}</td>
                            <td class="{{ $td }} text-right tabular-nums text-graphite">{{ $n0($e->promedio_brotes_3piso_n_dias) }}</td>
                            {{-- 4° piso: — si la evaluación no lo tiene (no había) --}}
                            <td class="{{ $td }} text-right tabular-nums">{{ $n0($e->promedio_actual_brotes_4piso) }}</td>
                            <td class="{{ $td }} text-right tabular-nums text-graphite">{{ $n0($e->promedio_brotes_4piso_n_dias) }}</td>
                            {{-- El total que sirve: énfasis con peso y Niebla, sin fondos de color --}}
                            <td class="{{ $td }} bg-fog text-right text-sm font-semibold tabular-nums text-ink">{{ $n0($e->promedio_actual_total_brotes_2y3piso) }}</td>
                            <td class="{{ $td }} text-right tabular-nums text-pewter">{{ $n0($e->promedio_total_brotes_2y3piso_n_dias) }}</td>
                            <td class="{{ $td }}">
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" class="{{ $icono }}" title="Ver camas"
                                        @click="abiertos[{{ $e->id }}] = !abiertos[{{ $e->id }}]">
                                        <i class="fa fa-chevron-down text-xs transition-transform duration-200" :class="abiertos[{{ $e->id }}] ? 'rotate-180' : ''"></i>
                                    </button>
                                    @if ($e->reporte_file)
                                        <a href="{{ Storage::disk('public')->url($e->reporte_file) }}" class="{{ $icono }}" title="Archivo">
                                            <i class="fa fa-file-excel text-xs"></i>
                                        </a>
                                    @endif
                                    @can(\App\Constants\Permisos::BROTE_EVALUACION_EDITAR)
                                        <button type="button" class="{{ $icono }}" title="Editar"
                                            @click="$wire.dispatch('editarEvaluacionBrotesPorPiso',{evaluacionBrotesXPisoId:{{ $e->id }}})">
                                            <i class="fa fa-pen text-xs"></i>
                                        </button>
                                    @endcan
                                    @can(\App\Constants\Permisos::BROTE_EVALUACION_ELIMINAR)
                                        <button type="button" class="{{ $icono }} hover:!text-red-700 dark:hover:!text-red-400" title="Eliminar"
                                            wire:click="eliminarBrotesXPiso({{ $e->id }})">
                                            <i class="fa fa-trash text-xs"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>

                        {{-- Detalle por cama --}}
                        <tr x-show="abiertos[{{ $e->id }}]" x-cloak wire:key="eval-brotes-det-{{ $e->id }}">
                            <td colspan="14" class="border-t border-hairline bg-fog/60 p-3">
                                <div class="overflow-x-auto rounded-[10px] border border-hairline bg-paper">
                                    <table class="w-full text-xs">
                                        <thead class="bg-fog text-graphite">
                                            <tr>
                                                <th class="px-2 py-1.5 text-right font-medium" rowspan="2">Cama</th>
                                                <th class="px-2 py-1.5 text-right font-medium" rowspan="2">Longitud (m)</th>
                                                @foreach (['2° piso actual', '2° piso para eliminar', '3° piso actual', '3° piso para eliminar', '4° piso actual', '4° piso para eliminar'] as $grupo)
                                                    <th class="border-l border-hairline px-2 py-1.5 text-center font-medium" colspan="2">{{ $grupo }}</th>
                                                @endforeach
                                                <th class="border-l border-hairline px-2 py-1.5 text-right font-medium text-ink" rowspan="2">Total aptos / ha</th>
                                            </tr>
                                            <tr>
                                                @for ($i = 0; $i < 6; $i++)
                                                    <th class="border-l border-hairline px-2 py-1 text-right font-normal">Cama</th>
                                                    <th class="px-2 py-1 text-right font-normal">/ ha</th>
                                                @endfor
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($e->detalles as $d)
                                                @php
                                                    $pares = [
                                                        [$d->brotes_aptos_2p_actual, $d->brotes_2p_actual_por_mt],
                                                        [$d->brotes_aptos_2p_despues_n_dias, $d->brotes_2p_despues_por_mt],
                                                        [$d->brotes_aptos_3p_actual, $d->brotes_3p_actual_por_mt],
                                                        [$d->brotes_aptos_3p_despues_n_dias, $d->brotes_3p_despues_por_mt],
                                                        [$d->brotes_aptos_4p_actual, $d->brotes_4p_actual_por_mt],
                                                        [$d->brotes_aptos_4p_despues_n_dias, $d->brotes_4p_despues_por_mt],
                                                    ];
                                                @endphp
                                                <tr class="border-t border-hairline">
                                                    <td class="px-2 py-1.5 text-right tabular-nums text-ink">{{ $d->numero_cama }}</td>
                                                    <td class="px-2 py-1.5 text-right tabular-nums">{{ $n2($d->longitud_cama) }}</td>
                                                    @foreach ($pares as [$cama, $porHa])
                                                        <td class="border-l border-hairline px-2 py-1.5 text-right tabular-nums">{{ $cama ?? '—' }}</td>
                                                        <td class="px-2 py-1.5 text-right tabular-nums text-graphite">{{ $cama === null ? '—' : $n2($porHa) }}</td>
                                                    @endforeach
                                                    <td class="border-l border-hairline bg-fog px-2 py-1.5 text-right font-semibold tabular-nums text-ink">{{ $n2($d->total_actual_por_mt) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="14" class="px-3 py-8 text-center text-sm text-pewter">Sin evaluaciones con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $evaluacionesBrotes->links() }}</div>
    @endcan

    <x-loading wire:loading />
</div>
