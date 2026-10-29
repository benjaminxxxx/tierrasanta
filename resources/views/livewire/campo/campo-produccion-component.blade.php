{{-- Producción de campo (DESIGN.md): cantidades por día / campo / labor, totales por campo y cuadre con el ingreso. --}}
@php
    $f = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
    $n = fn($v, $dec = 2) => $v === null ? '—' : number_format((float) $v, $dec);
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $tab = fn($clave) => $pestania === $clave ? 'border-carmin text-ink' : 'border-transparent text-graphite hover:text-ink';
    $estados = [
        'ok' => ['Cuadra', 'text-green-700 bg-green-50 dark:text-green-400 dark:bg-green-400/10', 'fa-check'],
        'aviso' => ['Revisar', 'text-amber-700 bg-amber-50 dark:text-amber-400 dark:bg-amber-400/10', 'fa-triangle-exclamation'],
        'anomalia' => ['Anomalía', 'text-red-700 bg-red-50 dark:text-red-400 dark:bg-red-400/10', 'fa-circle-exclamation'],
        'sin_ingreso' => ['Sin ingreso', 'text-red-700 bg-red-50 dark:text-red-400 dark:bg-red-400/10', 'fa-circle-exclamation'],
        'sin_produccion' => ['Sin registro en campo', 'text-amber-700 bg-amber-50 dark:text-amber-400 dark:bg-amber-400/10', 'fa-triangle-exclamation'],
    ];
@endphp

<div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Producción de campo</h1>
            <p class="text-sm text-graphite">Cantidades por labor (kilos, lavadores…), con bono y sin bono, y su cuadre con el ingreso de cochinilla.</p>
        </div>
        @if ($puedeRegistrar)
            <button type="button" wire:click="abrirNuevoDia" class="{{ $btnPrincipal }}"><i class="fa fa-plus"></i> Registrar sin bono</button>
        @endif
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap items-end gap-3 rounded-[10px] border border-mist bg-paper p-4">
        <div class="w-40"><x-fecha label="Desde" wire:model.live="desde" /></div>
        <div class="w-40"><x-fecha label="Hasta" wire:model.live="hasta" /></div>
        <div class="w-48"><x-select-campo wire:model.live="campo" label="Campo" /></div>
        @if ($pestania === 'registro')
            <div class="w-64">
                <x-select label="Labor" wire:model.live="labor">
                    <option value="">Todas</option>
                    @foreach ($labores as $l)
                        <option value="{{ $l->codigo }}">{{ $l->codigo }} · {{ $l->nombre_labor }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="w-56"><x-input label="Buscar" wire:model.live.debounce.400ms="buscar" placeholder="Labor o campo" /></div>
        @endif
    </div>

    {{-- Pestañas --}}
    <div class="flex gap-6 border-b border-hairline text-sm font-medium">
        <button type="button" wire:click="$set('pestania', 'registro')" class="-mb-px border-b-2 pb-2 {{ $tab('registro') }}">Registro</button>
        <button type="button" wire:click="$set('pestania', 'campos')" class="-mb-px border-b-2 pb-2 {{ $tab('campos') }}">Por campo</button>
        <button type="button" wire:click="$set('pestania', 'cuadre')" class="-mb-px border-b-2 pb-2 {{ $tab('cuadre') }}">Cuadre con ingreso</button>
        <button type="button" wire:click="$set('pestania', 'infestacion')" class="-mb-px border-b-2 pb-2 {{ $tab('infestacion') }}">Cuadre de infestación</button>
    </div>

    <div class="grid grid-cols-1 gap-6 {{ ($actividad || $nuevoDia) && $pestania === 'registro' ? 'xl:grid-cols-[minmax(0,1fr)_34rem]' : '' }}">
        <div class="min-w-0">
            @if ($pestania === 'registro')
                <div class="overflow-x-auto rounded-[10px] border border-mist bg-paper">
                    <table class="w-full text-[13px]">
                        <thead class="bg-fog text-left text-xs text-graphite">
                            <tr>
                                <th class="px-3 py-2 font-medium">Fecha</th>
                                <th class="px-3 py-2 font-medium">Campo</th>
                                <th class="px-3 py-2 font-medium">Labor</th>
                                <th class="px-3 py-2 text-right font-medium">Con bono</th>
                                <th class="px-3 py-2 text-right font-medium">Sin bono</th>
                                <th class="px-3 py-2 text-right font-medium">Total</th>
                                <th class="px-3 py-2 font-medium">Unidad</th>
                                <th class="px-3 py-2 text-right font-medium">Trabajadores</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($resumen as $r)
                                <tr wire:key="act-{{ $r->actividad_id }}" wire:click="abrir({{ $r->actividad_id }})"
                                    class="cursor-pointer border-t border-hairline hover:bg-fog {{ $actividadId === $r->actividad_id ? 'bg-fog' : '' }}">
                                    <td class="px-3 py-2 tabular-nums">{{ $f($r->fecha) }}</td>
                                    <td class="px-3 py-2">{{ $r->campo }}</td>
                                    <td class="px-3 py-2">{{ $r->codigo_labor }} · {{ $r->nombre_labor }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $n($r->con_bono) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ (float) $r->sin_bono ? $n($r->sin_bono) : '—' }}</td>
                                    <td class="px-3 py-2 text-right font-medium tabular-nums">{{ $n($r->total) }}</td>
                                    <td class="px-3 py-2 text-graphite">{{ $r->unidad }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $r->trabajadores }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-3 py-6 text-center text-sm text-pewter">Sin producción registrada en el periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($pestania === 'campos')
                @php $porCampo = $totales->groupBy('campo'); @endphp
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @forelse ($porCampo as $campoNombre => $filas)
                        <div class="rounded-[10px] border border-mist bg-paper">
                            <div class="border-b border-hairline px-4 py-2 text-sm font-semibold text-ink">Campo {{ $campoNombre }}</div>
                            <table class="w-full text-[13px]">
                                <thead class="bg-fog text-left text-xs text-graphite">
                                    <tr><th class="px-3 py-1.5 font-medium">Labor</th><th class="px-3 py-1.5 text-right font-medium">Con bono</th><th class="px-3 py-1.5 text-right font-medium">Sin bono</th><th class="px-3 py-1.5 text-right font-medium">Total</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($filas as $t)
                                        <tr class="border-t border-hairline">
                                            <td class="px-3 py-1.5">{{ $t->codigo_labor }} · {{ $t->nombre_labor }}</td>
                                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $n($t->con_bono) }}</td>
                                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $n($t->sin_bono) }}</td>
                                            <td class="px-3 py-1.5 text-right font-medium tabular-nums">{{ $n($t->total) }} <span class="font-normal text-pewter">{{ $t->unidad }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <p class="text-sm text-pewter">Sin producción registrada en el periodo.</p>
                    @endforelse
                </div>
            @elseif ($pestania === 'infestacion')
                <p class="mb-3 text-xs text-pewter">
                    Por día y campo infestado: kg de mamá y envases (lavadores) del módulo de infestación, contra lo que registró el campo
                    en sus labores ese día (con y sin bono). La diferencia de cada labor es contra los envases: si en campo figuran más
                    lavadores que los que salieron, revisa a quién se le anotó de más. Aún sin semáforo: la proporción esperada se configurará después.
                </p>
                @php
                    $tot = ['kg' => collect($infestacion['dias'])->sum('kg_madres'), 'env' => collect($infestacion['dias'])->sum('envases')];
                    $totLabor = [];
                    foreach ($infestacion['dias'] as $d) {
                        foreach ($d['labores'] as $l) {
                            $totLabor[$l['codigo']] = ($totLabor[$l['codigo']] ?? 0) + $l['cantidad'];
                        }
                    }
                    $dif = fn($v) => $v == 0 ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400';
                @endphp
                <div class="overflow-x-auto rounded-[10px] border border-mist bg-paper">
                    <table class="w-full text-[13px]">
                        <thead class="bg-fog text-left text-xs text-graphite">
                            <tr>
                                <th class="px-3 py-2 font-medium">Fecha</th>
                                <th class="px-3 py-2 font-medium">Campo</th>
                                <th class="px-3 py-2 font-medium">Mamá de</th>
                                <th class="px-3 py-2 text-right font-medium">Kg mamá</th>
                                <th class="px-3 py-2 text-right font-medium">Envases</th>
                                <th class="px-3 py-2 text-right font-medium">Kg / envase</th>
                                @foreach ($infestacion['labores'] as $codigo => $nombre)
                                    <th class="px-3 py-2 text-right font-medium" title="{{ $nombre }}">{{ $codigo }} · {{ \Illuminate\Support\Str::limit($nombre, 22) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($infestacion['dias'] as $d)
                                @php $porLabor = collect($d['labores'])->keyBy('codigo'); @endphp
                                <tr class="border-t border-hairline">
                                    <td class="px-3 py-2 tabular-nums">{{ $f($d['fecha']) }}</td>
                                    <td class="px-3 py-2 font-medium">{{ $d['campo'] }} <span class="text-xs font-normal text-pewter">{{ $d['tipos'] }}</span></td>
                                    <td class="px-3 py-2 text-xs text-graphite">{{ $d['origenes'] }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $n($d['kg_madres']) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $n($d['envases'], 0) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $n($d['kg_por_envase']) }}</td>
                                    @foreach ($infestacion['labores'] as $codigo => $nombre)
                                        @php $l = $porLabor->get($codigo); @endphp
                                        <td class="px-3 py-2 text-right tabular-nums">
                                            @if ($l)
                                                {{ $n($l['cantidad']) }}
                                                <span class="ml-1 text-xs {{ $dif($l['diferencia']) }}">{{ $l['diferencia'] == 0 ? '✓' : ($l['diferencia'] > 0 ? '+' : '') . $n($l['diferencia']) }}</span>
                                            @else
                                                <span class="text-xs text-pewter">sin registro</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ 6 + count($infestacion['labores']) }}" class="px-3 py-6 text-center text-sm text-pewter">Sin infestaciones en el periodo.</td></tr>
                            @endforelse
                        </tbody>
                        @if ($infestacion['dias'])
                            <tfoot class="border-t border-mist bg-fog text-xs font-semibold text-ink">
                                <tr>
                                    <td class="px-3 py-2" colspan="3">Total del periodo</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $n($tot['kg']) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $n($tot['env'], 0) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $tot['env'] > 0 ? $n($tot['kg'] / $tot['env']) : '—' }}</td>
                                    @foreach ($infestacion['labores'] as $codigo => $nombre)
                                        <td class="px-3 py-2 text-right tabular-nums">{{ $n($totLabor[$codigo] ?? 0) }}</td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @else
                <p class="mb-3 text-xs text-pewter">
                    Kilos cosechados en campo (labores de cosecha en KG, con y sin bono) contra los kilos ingresados de poda mamá y poda cosecha.
                    Se advierte desde {{ $pctAviso }} % de diferencia y es anomalía desde {{ $pctAnomalia }} %. Un ingreso puede registrarse al día
                    siguiente de la cosecha: mira primero el total del periodo.
                </p>
                <div class="overflow-x-auto rounded-[10px] border border-mist bg-paper">
                    <table class="w-full text-[13px]">
                        <thead class="bg-fog text-left text-xs text-graphite">
                            <tr>
                                <th class="px-3 py-2 font-medium">Campo (periodo)</th>
                                <th class="px-3 py-2 text-right font-medium">Campo kg</th>
                                <th class="px-3 py-2 text-right font-medium">Ingreso kg</th>
                                <th class="px-3 py-2 text-right font-medium">Diferencia</th>
                                <th class="px-3 py-2 text-right font-medium">%</th>
                                <th class="px-3 py-2 font-medium">Estado</th>
                                <th class="px-3 py-2 text-right font-medium">Días a revisar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cuadre['campos'] as $c)
                                @php [$txt, $cls, $ico] = $estados[$c['estado']]; @endphp
                                <tr class="border-t border-hairline">
                                    <td class="px-3 py-2 font-medium">{{ $c['campo'] }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $n($c['campo_kg']) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $n($c['ingreso_kg']) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $n($c['diferencia_kg']) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $c['diferencia_pct'] === null ? '—' : $n($c['diferencia_pct'], 1) . ' %' }}</td>
                                    <td class="px-3 py-2"><span class="rounded-[6px] px-2 py-0.5 text-xs font-medium {{ $cls }}"><i class="fa {{ $ico }}"></i> {{ $txt }}</span></td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $c['dias_con_diferencia'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-3 py-6 text-center text-sm text-pewter">Sin cosecha ni ingresos en el periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @php $dias = collect($cuadre['dias'])->where('estado', '!=', 'ok'); @endphp
                @if ($dias->isNotEmpty())
                    <h3 class="mb-2 mt-5 text-sm font-semibold text-ink">Días con diferencia</h3>
                    <div class="overflow-x-auto rounded-[10px] border border-mist bg-paper">
                        <table class="w-full text-[13px]">
                            <thead class="bg-fog text-left text-xs text-graphite">
                                <tr><th class="px-3 py-2 font-medium">Fecha</th><th class="px-3 py-2 font-medium">Campo</th><th class="px-3 py-2 text-right font-medium">Campo kg</th><th class="px-3 py-2 text-right font-medium">Ingreso kg</th><th class="px-3 py-2 text-right font-medium">%</th><th class="px-3 py-2 font-medium">Estado</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($dias as $d)
                                    @php [$txt, $cls, $ico] = $estados[$d['estado']]; @endphp
                                    <tr class="border-t border-hairline">
                                        <td class="px-3 py-2 tabular-nums">{{ $f($d['fecha']) }}</td>
                                        <td class="px-3 py-2">{{ $d['campo'] }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums">{{ $n($d['campo_kg']) }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums">{{ $n($d['ingreso_kg']) }}</td>
                                        <td class="px-3 py-2 text-right tabular-nums">{{ $d['diferencia_pct'] === null ? '—' : $n($d['diferencia_pct'], 1) . ' %' }}</td>
                                        <td class="px-3 py-2"><span class="rounded-[6px] px-2 py-0.5 text-xs font-medium {{ $cls }}"><i class="fa {{ $ico }}"></i> {{ $txt }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>

        {{-- Panel: detalle de una actividad o registrar un día sin bono --}}
        @if ($pestania === 'registro' && ($actividad || $nuevoDia))
            <div class="space-y-4">
                @if ($nuevoDia && !$actividad)
                    <div class="space-y-3 rounded-[10px] border border-mist bg-paper p-4">
                        <h3 class="text-base font-semibold text-ink">Registrar producción sin bono</h3>
                        <p class="text-xs text-pewter">Para un día completo sin bono o personas fuera de la bonificación. Si el día ya tiene esa labor en el campo, se abre la misma.</p>
                        <div class="grid grid-cols-2 gap-3">
                            <x-fecha label="Fecha" wire:model="dia.fecha" />
                            <x-select-campo wire:model="dia.campo" label="Campo" />
                            <div class="col-span-2">
                                <x-select label="Labor (con unidad de producción)" wire:model="dia.labor">
                                    <option value="">Seleccionar…</option>
                                    @foreach ($labores as $l)
                                        <option value="{{ $l->codigo }}">{{ $l->codigo }} · {{ $l->nombre_labor }} ({{ $l->unidades }})</option>
                                    @endforeach
                                </x-select>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="cerrar" class="{{ $btn2 }}">Cancelar</button>
                            <button type="button" wire:click="crearDia" class="{{ $btnPrincipal }}">Continuar</button>
                        </div>
                    </div>
                @elseif ($actividad)
                    <div class="rounded-[10px] border border-mist bg-paper p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="text-base font-semibold text-ink">{{ $actividad->codigo_labor }} · {{ $actividad->nombre_labor }}</h3>
                                <p class="text-xs text-graphite">Campo {{ $actividad->campo }} · {{ $f($actividad->fecha) }}</p>
                            </div>
                            <button type="button" wire:click="cerrar" class="text-steel hover:text-ink" title="Cerrar"><i class="fa fa-xmark"></i></button>
                        </div>
                        <h4 class="mb-1 mt-3 text-xs font-medium uppercase text-graphite">Con bono (de bonificaciones)</h4>
                        @if ($conBono->isEmpty())
                            <p class="text-xs text-pewter">Sin cantidades con bono.</p>
                        @else
                            <table class="w-full text-[13px]">
                                <tbody>
                                    @foreach ($conBono as $filas)
                                        <tr class="border-t border-hairline">
                                            <td class="py-1">{{ $filas->first()->nombre_trabajador ?? '—' }}</td>
                                            <td class="py-1 text-right tabular-nums">{{ $filas->pluck('cantidad')->map(fn($c) => $n($c))->implode(' + ') }}</td>
                                            <td class="py-1 pl-2 text-right font-medium tabular-nums">{{ $n($filas->sum('cantidad')) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="mt-1 text-xs text-pewter">Se corrigen en Cuadrilla → Bonificaciones.</p>
                        @endif
                    </div>
                    <livewire:campo.campo-produccion-sin-bono-component :actividad-id="$actividad->id" wire:key="sinbono-panel-{{ $actividad->id }}" />
                @endif
            </div>
        @endif
    </div>

    <x-loading wire:loading wire:target="crearDia,pestania" />
</div>
