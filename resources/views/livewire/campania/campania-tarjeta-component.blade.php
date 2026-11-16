{{-- Tarjeta de campaña (DESIGN.md): vista rápida y gráfica (datos, avance, cosecha, siembras con su etapa) y accesos
     a los modales de la campaña. Se abre con <x-campania.nombre> (evento verTarjetaCampania). --}}
@php
    $R = \App\Services\Campania\Sector\CampaniaSectorReglas::class;
    $f = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/Y') : '—';
    $fc = fn($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d/m/y') : '—';
    $num = fn($v, $dec = 0) => rtrim(rtrim(number_format((float) $v, $dec), '0'), '.');
    $btn = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $kpi = 'rounded-[10px] border border-mist bg-paper p-3';
@endphp
<div>
    <x-dialog-modal wire:model="mostrar" maxWidth="4xl">
        <x-slot name="title">
            @if ($t)
                <div class="flex items-center gap-3">
                    <div class="flex h-12 min-w-14 shrink-0 items-center justify-center rounded-[10px] border border-mist bg-fog px-2 text-lg font-semibold text-ink">{{ $t['campo'] }}</div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-ink">{{ $t['nombre'] }}</span>
                            @if ($t['cerrada'])
                                <span class="rounded-[6px] bg-fog px-1.5 py-0.5 text-[11px] font-medium text-graphite">Cerrada</span>
                            @else
                                <span class="rounded-[6px] bg-green-50 px-1.5 py-0.5 text-[11px] font-medium text-green-700 dark:bg-green-400/10 dark:text-green-400">Vigente</span>
                            @endif
                            @if ($t['etapa'])
                                <span class="inline-flex items-center gap-1 rounded-[6px] border border-mist px-1.5 py-0.5 text-[11px] font-medium text-ink">
                                    <span class="h-2 w-2 rounded-full" style="background: {{ $t['etapa']['color'] }}"></span>{{ $t['etapa']['nombre'] }}
                                </span>
                            @endif
                        </div>
                        <p class="text-xs font-normal text-graphite">
                            {{ $t['area'] ? $num($t['area'], 2) . ' ha' : 'Sin área' }}
                            @if ($t['variedad']) · {{ $t['variedad'] }} @endif
                            @if ($t['sistema']) · {{ $t['sistema'] }} @endif
                            · {{ $f($t['inicio']) }} → {{ $t['fin'] ? $f($t['fin']) : 'hoy' }} ({{ $t['dias'] }} días)
                        </p>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="content">
            @if ($t)
                <div class="space-y-4 text-sm">
                    {{-- Siembras de la campaña (lo primero que se ve: cada una con su fecha, etapa y línea de tiempo) --}}
                    <div class="overflow-hidden rounded-[10px] border border-mist">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline bg-fog px-3 py-2">
                            <p class="text-xs font-semibold text-ink">Siembras y etapas
                                @if ($t['linea'])
                                    <span class="ml-1 text-[11px] font-normal tabular-nums text-pewter">{{ $fc($t['linea']['inicio']) }} → {{ $fc($t['linea']['fin']) }}</span>
                                @endif
                            </p>
                            @if ($puedeGestionar && !$agregandoSiembra)
                                <button type="button" wire:click="$set('agregandoSiembra', true)"
                                    class="inline-flex h-7 items-center gap-1 rounded-[8px] border border-mist bg-paper px-2 text-xs font-medium text-ink hover:bg-paper/70 focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                                    <i class="fa fa-plus text-steel"></i> Siembra (relleno)
                                </button>
                            @endif
                        </div>
                        {{-- Agregar una siembra: parte del campo sembrada en otra fecha (ej. relleno a 2 m meses después) --}}
                        @if ($puedeGestionar && $agregandoSiembra)
                            <div class="border-b border-hairline bg-paper px-3 py-3">
                                <p class="mb-2 text-xs text-graphite">
                                    Parte del campo se sembró en otra fecha (por ejemplo, el relleno a 2 m meses después): se agrega como
                                    <b>{{ \App\Services\Campania\Sector\CampaniaSectorReglas::nombreSiembra(count($t['grupos']) + 1) }}</b>, en siembra desde esa fecha,
                                    con su propia infestación y cosecha. La campaña se cierra con la cosecha de la última siembra.
                                </p>
                                <div class="flex flex-wrap items-end gap-2">
                                    <label class="block"><span class="mb-1 block text-xs font-medium text-graphite">Fecha de siembra</span>
                                        <input type="date" wire:model="fechaNuevaSiembra" min="{{ $t['inicio'] }}" max="{{ $t['fin'] ?? now()->toDateString() }}"
                                            class="h-9 rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25" /></label>
                                    <button type="button" wire:click="agregarSiembra" wire:loading.attr="disabled"
                                        class="inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                                        <i class="fa fa-check"></i> Agregar siembra
                                    </button>
                                    <button type="button" wire:click="$set('agregandoSiembra', false)" class="{{ $btn }}">Cancelar</button>
                                </div>
                            </div>
                        @endif
                        @forelse ($t['grupos'] as $g)
                            <div class="grid grid-cols-1 gap-2 border-b border-hairline px-3 py-2.5 last:border-0 md:grid-cols-[13rem_1fr] md:items-center">
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink">{{ $g['nombre'] }} <span class="font-normal text-pewter">· hileras {{ $g['hileras'] }}</span>@if ($g['cosechada']) <i class="fa fa-check text-green-700 dark:text-green-400" title="Ya cosechada"></i>@endif</p>
                                    <p class="text-[11px] text-graphite">
                                        @if ($g['siembra'])
                                            Sembrada el {{ $fc($g['siembra']['fecha']) }} ({{ $num($g['siembra']['meses'], 1) }} meses)
                                        @else
                                            Sin fecha de siembra
                                        @endif
                                        @if ($t['variedad']) · {{ $t['variedad'] }} @endif
                                    </p>
                                    <p class="mt-0.5">
                                        <span class="inline-flex items-center rounded-[6px] px-1.5 py-0.5 text-[11px] font-medium text-white" style="background: {{ $g['color'] }}">{{ $g['etapa_nombre'] }}</span>
                                        @if ($g['desde'])
                                            <span class="text-[11px] text-pewter">desde {{ $fc($g['desde']) }} · {{ $g['dias_en_etapa'] }} días</span>
                                        @endif
                                    </p>
                                </div>
                                {{-- Línea de tiempo de la siembra --}}
                                <div class="relative h-6 overflow-hidden rounded-[6px] bg-fog">
                                    @foreach ($g['tramos'] as $tr)
                                        <div class="absolute inset-y-0" style="left: {{ $tr['izq'] }}%; width: {{ $tr['ancho'] }}%; background: {{ $R::color($tr['etapa']) }}"
                                            title="{{ $R::nombre($tr['etapa']) }} desde {{ $fc($tr['desde']) }}"></div>
                                    @endforeach
                                    @if ($t['linea'] && !$t['cerrada'])
                                        <div class="absolute inset-y-0 w-0.5 bg-ink" style="left: {{ $t['linea']['marca'] }}%" title="Hoy"></div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="px-3 py-3 text-xs text-graphite">La campaña no tiene mapa de etapas.</p>
                        @endforelse
                        @if ($t['aviso_siembras'])
                            <p class="border-t border-hairline bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                                <i class="fa fa-triangle-exclamation"></i> {{ $t['aviso_siembras'] }}
                            </p>
                        @endif
                    </div>

                    {{-- Hileras pintadas con la etapa de su siembra hoy (o al cierre) --}}
                    @if ($t['filas'])
                        <div>
                            <p class="mb-1 text-xs font-medium text-graphite">Hileras {{ $t['cerrada'] ? 'al cierre' : 'hoy' }}</p>
                            <div class="flex h-7 gap-px overflow-hidden rounded-[8px] border border-mist">
                                @foreach ($t['filas'] as $fila)
                                    <div class="flex-1" style="background: {{ $fila['color'] }}"
                                        title="Hilera {{ $fila['hilera'] }} · {{ $fila['sector'] !== null ? $t['grupos'][$fila['sector']]['nombre'] . ' · ' : '' }}{{ $R::nombre($fila['etapa']) }}"></div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Indicadores --}}
                    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <div class="{{ $kpi }}">
                            <p class="text-xs text-graphite">Avance vegetativo</p>
                            @if ($t['avance'] && $t['avance']['avance_pct'] !== null)
                                @php $lleno = $t['avance']['avance_pct'] >= 100; @endphp
                                <p class="text-xl font-semibold tabular-nums {{ $lleno ? 'text-red-700 dark:text-red-400' : 'text-ink' }}">{{ number_format($t['avance']['avance_pct'], 1) }} %</p>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-fog">
                                    <div class="h-1.5 rounded-full {{ $lleno ? 'bg-red-600 dark:bg-red-400' : 'bg-[#2a78d6] dark:bg-[#3987e5]' }}" style="width: {{ min(100, $t['avance']['avance_pct']) }}%"></div>
                                </div>
                                <p class="mt-1 text-[11px] text-pewter">{{ $num($t['avance']['meses_acumulados'], 1) }} de {{ $num($t['avance']['meses_proyectados_total'], 1) }} meses</p>
                            @else
                                <p class="text-xl font-semibold text-pewter">—</p>
                            @endif
                        </div>
                        <div class="{{ $kpi }}">
                            <p class="text-xs text-graphite">Cosecha</p>
                            @if (($t['cosecha']['estado'] ?? 'sin_cosecha') === 'sin_cosecha')
                                <p class="text-xl font-semibold text-pewter">—</p>
                                <p class="mt-1 text-[11px] text-pewter">Sin cosecha registrada</p>
                            @else
                                <p class="text-xl font-semibold tabular-nums text-ink">{{ number_format($t['cosecha']['kg'], 0) }} <span class="text-sm font-normal text-graphite">kg</span></p>
                                <p class="mt-1 text-[11px] text-pewter">
                                    {{ $t['cosecha']['estado'] === 'cosechando' ? 'Cosechando' : 'Cosechada' }} ·
                                    {{ $fc($t['cosecha']['primera_cosecha']) }}{{ $t['cosecha']['dias_cosecha'] > 1 ? ' – ' . $fc($t['cosecha']['ultima_cosecha']) : '' }}
                                </p>
                            @endif
                        </div>
                        <div class="{{ $kpi }}">
                            <p class="text-xs text-graphite">Siembras</p>
                            <p class="text-xl font-semibold tabular-nums text-ink">{{ max(count($t['grupos']), 0) ?: '—' }}</p>
                            <p class="mt-1 text-[11px] text-pewter">
                                @if ($t['siembras'])
                                    Registro del campo: {{ collect($t['siembras'])->map(fn($s) => $fc($s['fecha']))->implode(', ') }}
                                @else
                                    Sin siembra registrada
                                @endif
                            </p>
                        </div>
                        <div class="{{ $kpi }}">
                            <p class="text-xs text-graphite">Hileras del campo</p>
                            <p class="text-xl font-semibold tabular-nums text-ink">{{ $t['hileras'] }}</p>
                            @if (($t['cosecha']['recomendar_cierre'] ?? null) && !$t['cerrada'])
                                <p class="mt-1 text-[11px] font-medium text-amber-700 dark:text-amber-400">
                                    <i class="fa fa-flag-checkered"></i> Conviene cerrar ({{ $fc($t['cosecha']['fecha_cierre_sugerida']) }})
                                </p>
                            @else
                                <p class="mt-1 text-[11px] text-pewter">Repartidas entre las siembras</p>
                            @endif
                        </div>
                    </div>

                    {{-- Leyenda --}}
                    <div class="flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-graphite">
                        @foreach ($R::ETAPAS as $clave => [$nombre, $color])
                            <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $color }}"></span>{{ $nombre }}</span>
                        @endforeach
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $R::SIN_ETAPA[1] }}"></span>{{ $R::SIN_ETAPA[0] }}</span>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            @if ($t)
                {{-- Accesos rápidos: cierran la tarjeta y abren el modal de siempre --}}
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <a href="{{ route('campania.por_campo', ['campania' => $t['id']]) }}" class="{{ $btn }}"><i class="fa fa-folder-open text-steel"></i> Detalle</a>
                    <button type="button" class="{{ $btn }}" @click="$wire.set('mostrar', false); $wire.dispatch('verHistoriaCampania', {campaniaId: {{ $t['id'] }}})">
                        <i class="fa fa-book-open text-steel"></i> Historia
                    </button>
                    <button type="button" class="{{ $btn }}" @click="$wire.set('mostrar', false); $wire.dispatch('verMapaEtapas', {campaniaId: {{ $t['id'] }}})">
                        <i class="fa fa-layer-group text-steel"></i> Editar siembras y etapas
                    </button>
                    <a href="{{ route('campo.actividades', ['vista' => 'campania', 'campania' => $t['id'], 'campo' => $t['campo']]) }}" class="{{ $btn }}">
                        <i class="fa fa-list-check text-steel"></i> Labores
                    </a>
                    @if ($puedeGestionar)
                        <button type="button" class="{{ $btn }}" @click="$wire.set('mostrar', false); $wire.dispatch('editarCampania', {campaniaId: {{ $t['id'] }}})">
                            <i class="fa fa-pen text-steel"></i> Editar
                        </button>
                        <button type="button" class="{{ $btn }}" @click="$wire.set('mostrar', false); $wire.dispatch('corregirFechasCampania', {campaniaId: {{ $t['id'] }}})">
                            <i class="fa fa-calendar-days text-steel"></i> Fechas
                        </button>
                        @if (!$t['cerrada'])
                            @php
                                $sugerida = ($t['cosecha']['recomendar_cierre'] ?? null) ? $t['cosecha']['fecha_cierre_sugerida'] : null;
                                $paramsCierre = ['campaniaId' => $t['id']] + ($sugerida ? ['fecha' => $sugerida->toDateString()] : []);
                            @endphp
                            <button type="button" class="{{ $btn }}"
                                @click="$wire.set('mostrar', false); $wire.dispatch('cerrarCampania', @js($paramsCierre))">
                                <i class="fa fa-flag-checkered {{ $sugerida ? 'text-amber-700 dark:text-amber-400' : 'text-steel' }}"></i> Cerrar campaña
                            </button>
                        @endif
                        <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-red-200 bg-paper px-3 text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none dark:border-red-400/30 dark:text-red-400 dark:hover:bg-red-400/10"
                            wire:confirm="¿Eliminar la campaña {{ $t['nombre'] }} del campo {{ $t['campo'] }}?" wire:click="eliminarCampania">
                            <i class="fa fa-trash"></i> Eliminar
                        </button>
                    @endif
                    <button type="button" class="{{ $btn }}" wire:click="$set('mostrar', false)">Cerrar ventana</button>
                </div>
            @endif
        </x-slot>
    </x-dialog-modal>
</div>
