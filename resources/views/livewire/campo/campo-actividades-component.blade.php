{{-- Actividades por campo (DESIGN.md): por día (códigos por campo y día) o por campaña (un campo, agrupado por campaña
     en vertical y por grupo de hileras en horizontal según la etapa de cada labor). --}}
@php
    $dia = fn($d) => \Illuminate\Support\Carbon::parse($d)->locale('es')->isoFormat('ddd DD/MM/YYYY');
    $diaCorto = fn($d) => \Illuminate\Support\Carbon::parse($d)->locale('es')->isoFormat('ddd DD/MM/YY');
    $R = \App\Services\Campania\Sector\CampaniaSectorReglas::class;
    $fmt = fn($d) => \Illuminate\Support\Carbon::parse($d)->format('d/m/Y');
@endphp

<div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Actividades por campo</h1>
            <p class="text-sm text-graphite">
                Qué labores se hicieron en cada campo (planilla y cuadrilla), con la etapa de cada una. Al registrar horas, una labor
                de etapa debe caer en la etapa de algún grupo de hileras del campo (Campañas → Mapa de etapas).
            </p>
        </div>
        {{-- Vista --}}
        <div class="inline-flex shrink-0 rounded-[10px] border border-mist bg-paper p-1 text-sm">
            <button type="button" wire:click="$set('vista', 'dias')"
                class="rounded-[8px] px-3 py-1.5 font-medium {{ $vista === 'dias' ? 'bg-carmin text-white' : 'text-graphite hover:text-ink' }}">
                <i class="fa fa-calendar-day"></i> Por día
            </button>
            <button type="button" wire:click="$set('vista', 'campania')"
                class="rounded-[8px] px-3 py-1.5 font-medium {{ $vista === 'campania' ? 'bg-carmin text-white' : 'text-graphite hover:text-ink' }}">
                <i class="fa fa-layer-group"></i> Por campaña
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap items-end gap-3 rounded-[10px] border border-mist bg-paper p-4">
        <div class="w-48"><x-select-campo wire:model.live="campo" label="Campo" /></div>
        @if ($vista === 'campania')
            <div class="w-60">
                <x-select wire:model.live="campania" label="Campaña" :disabled="!$campo">
                    <option value="">{{ $campo ? 'Elige una campaña…' : 'Primero elige el campo' }}</option>
                    @foreach ($campanias as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->nombre_campania }} · {{ $fmt($c->fecha_inicio) }} – {{ $c->fecha_fin ? $fmt($c->fecha_fin) : 'hoy' }}
                        </option>
                    @endforeach
                </x-select>
            </div>
        @endif
        <div class="w-40"><x-fecha label="Desde" wire:model.live="desde" /></div>
        <div class="w-40"><x-fecha label="Hasta" wire:model.live="hasta" /></div>
        @if ($vista === 'dias')
            <label class="flex h-9 items-center gap-2 text-sm text-ink">
                <input type="checkbox" wire:model.live="soloVarias" class="rounded border-mist text-carmin focus:ring-carmin/25">
                Solo campos con más de una actividad
            </label>
        @endif
    </div>

    @if ($recortado)
        <p class="rounded-[10px] bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
            <i class="fa fa-triangle-exclamation"></i> Se muestran como máximo {{ $maximo }} días:
            desde el {{ $fmt($desdeEfectivo) }}.
        </p>
    @endif

    <div wire:loading.class="opacity-60" class="space-y-4">
        @if ($vista === 'campania')
            {{-- ===================== Por campaña ===================== --}}
            @if (!$campo)
                <div class="rounded-[10px] border border-mist bg-paper p-8 text-center text-sm text-graphite">
                    Elige un campo para ver sus labores por campaña y grupo de hileras.
                </div>
            @endif

            @if ($campo && !$bloques)
                <div class="rounded-[10px] border border-mist bg-paper p-8 text-center text-sm text-graphite">
                    No hay horas registradas en el campo {{ $campo }} en esas fechas.
                </div>
            @endif

            @foreach ($bloques as $b)
                @php
                    $grupos = collect($b['columnas'])->where('tipo', 'grupo')->count();
                @endphp
                <div class="flex overflow-hidden rounded-[10px] border border-mist bg-paper">
                    {{-- Encabezado vertical: la campaña --}}
                    <div class="flex w-11 shrink-0 items-center justify-center border-r border-hairline {{ $b['campania'] ? 'bg-carmin text-white' : 'bg-fog text-graphite' }}">
                        <div class="rotate-180 whitespace-nowrap py-3 text-xs font-semibold tracking-wide [writing-mode:vertical-rl]">
                            @if ($b['campania'])
                                {{ $b['campania']['nombre'] }}
                                <span class="font-normal opacity-80">· {{ $fmt($b['campania']['inicio']) }} – {{ $b['campania']['fin'] ? $fmt($b['campania']['fin']) : 'hoy' }}</span>
                            @else
                                Sin campaña
                            @endif
                        </div>
                    </div>

                    <div class="min-w-0 flex-1 overflow-x-auto">
                        <table class="w-full border-collapse text-xs">
                            {{-- Encabezado horizontal: grupos de hileras --}}
                            <thead>
                                <tr class="border-b border-hairline bg-fog text-left">
                                    <th class="sticky left-0 z-10 w-28 bg-fog px-3 py-2 font-semibold text-ink">Fecha</th>
                                    @foreach ($b['columnas'] as $col)
                                        <th class="min-w-[190px] px-3 py-2 align-bottom">
                                            <span class="block font-semibold {{ $col['tipo'] === 'fuera' ? 'text-red-700 dark:text-red-400' : 'text-ink' }}">
                                                @if ($col['tipo'] === 'fuera')<i class="fa fa-triangle-exclamation"></i>@endif
                                                {{ $col['nombre'] }}
                                            </span>
                                            <span class="block font-normal text-pewter">{{ $col['detalle'] }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline">
                                @foreach ($b['dias'] as $d)
                                    <tr class="align-top">
                                        <td class="sticky left-0 z-10 whitespace-nowrap bg-paper px-3 py-2 font-medium capitalize text-ink">{{ $diaCorto($d['fecha']) }}</td>
                                        @foreach ($b['columnas'] as $col)
                                            @php
                                                $etapaGrupo = $d['etapas'][$col['clave']] ?? null;
                                                $lista = $d['celdas'][$col['clave']] ?? [];
                                            @endphp
                                            <td class="px-2 py-1.5 {{ $col['tipo'] === 'fuera' && $lista ? 'bg-red-50 dark:bg-red-400/10' : '' }}"
                                                @if ($col['tipo'] === 'grupo') style="box-shadow: inset 3px 0 0 {{ $R::color($etapaGrupo) }}" title="{{ $col['nombre'] }}: {{ $R::nombre($etapaGrupo) }}" @endif>
                                                <div class="flex flex-col gap-1">
                                                    @foreach ($lista as $a)
                                                        <div class="rounded-[6px] border border-hairline px-2 py-1">
                                                            <p class="font-semibold text-ink">
                                                                {{ $a['codigo'] }} <span class="font-normal text-graphite">· {{ \Illuminate\Support\Str::limit($a['labor'], 30) }}</span>
                                                                @if ($a['compartida'])
                                                                    <i class="fa fa-arrows-left-right ml-0.5 text-pewter" title="Varios grupos estaban en esta etapa: puede ser de cualquiera de ellos"></i>
                                                                @endif
                                                            </p>
                                                            <p class="tabular-nums text-pewter">
                                                                {{ $a['planilla'] + $a['cuadrilla'] }} trab.
                                                                @if ($a['cuadrilla']) ({{ $a['cuadrilla'] }} cuad.) @endif
                                                                · {{ number_format($a['horas'], 1) }} h
                                                                @if ($a['etapa'] && $col['tipo'] !== 'grupo')
                                                                    · <span class="font-medium" style="color: {{ $R::color($a['etapa']) }}">{{ $R::nombre($a['etapa']) }}</span>
                                                                @endif
                                                            </p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-mist bg-fog font-semibold text-ink">
                                    <td class="sticky left-0 z-10 bg-fog px-3 py-2">Total horas</td>
                                    @foreach ($b['columnas'] as $col)
                                        <td class="px-3 py-2 tabular-nums">{{ number_format($b['totales'][$col['clave']] ?? 0, 1) }} h</td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                        @if ($grupos)
                            <p class="border-t border-hairline px-3 py-2 text-[11px] text-pewter">
                                La raya de color a la izquierda de cada celda es la etapa del grupo ese día.
                                <i class="fa fa-arrows-left-right"></i> = varios grupos estaban en esa etapa (sus horas se reparten en el total).
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            {{-- ===================== Por día ===================== --}}
            @forelse ($dias as $fecha => $campos)
                <div class="overflow-hidden rounded-[10px] border border-mist bg-paper">
                    <div class="flex items-center justify-between border-b border-hairline bg-fog px-4 py-2">
                        <h2 class="text-sm font-semibold capitalize text-ink">{{ $dia($fecha) }}</h2>
                        <span class="text-xs text-graphite">
                            {{ $campos->count() }} campo(s) ·
                            {{ $campos->filter(fn($c) => count($c['actividades']) > 1)->count() }} con más de una actividad
                        </span>
                    </div>
                    <div class="divide-y divide-hairline">
                        @foreach ($campos as $c)
                            <div class="flex flex-col gap-2 px-4 py-2.5 md:flex-row md:items-start">
                                <div class="w-32 shrink-0">
                                    <p class="text-sm font-semibold text-ink">Campo {{ $c['campo'] }}</p>
                                </div>
                                <div class="flex flex-1 flex-wrap gap-2">
                                    @foreach ($c['actividades'] as $a)
                                        <div class="min-w-[170px] rounded-[8px] border px-2.5 py-1.5 text-xs border-hairline">
                                            <p class="font-semibold text-ink">{{ $a['codigo'] }} <span class="font-normal text-graphite">· {{ \Illuminate\Support\Str::limit($a['labor'], 38) }}</span></p>
                                            @if ($a['etapa'])
                                                <p class="mt-0.5"><span class="rounded-[6px] px-1.5 py-0.5 text-[10px] font-semibold text-white" style="background: {{ $R::color($a['etapa']) }}">{{ $R::nombre($a['etapa']) }}</span></p>
                                            @endif
                                            <p class="mt-0.5 tabular-nums text-pewter">
                                                {{ $a['planilla'] + $a['cuadrilla'] }} trab.
                                                @if ($a['planilla']) · {{ $a['planilla'] }} planilla @endif
                                                @if ($a['cuadrilla']) · {{ $a['cuadrilla'] }} cuadrilla @endif
                                                · {{ number_format($a['horas'], 1) }} h
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-[10px] border border-mist bg-paper p-8 text-center text-sm text-graphite">
                    No hay horas registradas en esas fechas{{ $campo ? " para el campo {$campo}" : '' }}.
                </div>
            @endforelse
        @endif
    </div>
</div>
