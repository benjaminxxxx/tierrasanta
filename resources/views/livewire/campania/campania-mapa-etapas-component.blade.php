{{-- Modal "Mapa de etapas" (DESIGN.md): hileras del campo por siembra y etapa, línea de tiempo y edición --}}
@php
    use App\Services\Campania\Sector\CampaniaSectorReglas as R;
    $f = fn($d) => $d ? formatear_fecha($d) : '—';
    $input = 'h-9 rounded-[10px] border border-mist bg-paper px-3 text-sm text-ink focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btn2 = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:cursor-not-allowed disabled:opacity-50';
    $chip = 'inline-flex items-center gap-1 rounded-[6px] px-1.5 py-0.5 text-[11px] font-medium text-white';
@endphp
<div>
    <x-dialog-modal wire:model="mostrar" maxWidth="full">
        <x-slot name="title">
            Mapa de etapas
            @if ($campania)
                <span class="text-base font-normal text-graphite">· campo {{ $campania->campo }} · {{ $campania->nombre_campania }}</span>
            @endif
        </x-slot>
        <x-slot name="content">
            @if ($campania && $dibujo)
                @php
                    // Bloques seguidos de hileras del mismo grupo (o sin grupo)
                    $bloques = [];
                    foreach ($dibujo['filas'] as $fila) {
                        $ultimo = count($bloques) - 1;
                        if ($ultimo >= 0 && $bloques[$ultimo]['sector'] === $fila['sector']) {
                            $bloques[$ultimo]['filas'][] = $fila;
                        } else {
                            $bloques[] = ['sector' => $fila['sector'], 'filas' => [$fila]];
                        }
                    }
                    $alto = max(6, min(22, intdiv(340, max(1, count($dibujo['filas'])))));
                    $numerar = count($dibujo['filas']) <= 30;
                    $totalDias = max(1, \Illuminate\Support\Carbon::parse($dibujo['inicio'])->diffInDays(\Illuminate\Support\Carbon::parse($dibujo['fin'])));
                @endphp
                <div class="space-y-4 text-sm">
                    {{-- Fecha que se mira --}}
                    <div class="flex flex-wrap items-end gap-3 rounded-[10px] border border-mist bg-fog px-3 py-2"
                        x-data="{
                            inicio: new Date(@js($dibujo['inicio']) + 'T00:00:00'),
                            dia: {{ \Illuminate\Support\Carbon::parse($dibujo['inicio'])->diffInDays(\Illuminate\Support\Carbon::parse($dibujo['fecha']), false) }},
                            aFecha(d) { const x = new Date(this.inicio); x.setDate(x.getDate() + Number(d)); return x.toISOString().slice(0, 10); },
                        }">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-graphite">Ver el campo el</span>
                            <input type="date" wire:model.live="fecha" min="{{ $dibujo['inicio'] }}" max="{{ $dibujo['fin'] }}" class="{{ $input }}" />
                        </label>
                        <input type="range" min="0" max="{{ $totalDias }}" x-model="dia" class="mb-2 min-w-[180px] flex-1 accent-[#BC002D]"
                            @change="$wire.set('fecha', aFecha(dia))" title="Mueve para ver cómo avanzó cada siembra" />
                        <p class="mb-2 text-xs text-graphite">Campaña del {{ $f($dibujo['inicio']) }} al {{ $campania->fecha_fin ? $f($campania->fecha_fin) : 'hoy' }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">
                        {{-- El campo con sus hileras --}}
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase text-graphite">Campo {{ $campania->campo }} · {{ $hileras }} hileras</p>
                            <div class="space-y-1 rounded-[10px] border border-mist bg-paper p-2">
                                @foreach ($bloques as $b)
                                    @php
                                        $s = $b['sector'] !== null ? ($sectores[$b['sector']] ?? null) : null;
                                        $etapa = $b['filas'][0]['etapa'];
                                    @endphp
                                    <div class="flex items-stretch gap-2">
                                        <div class="flex-1 space-y-[2px]">
                                            @foreach ($b['filas'] as $fila)
                                                <div class="flex items-center gap-1" title="Hilera {{ $fila['hilera'] }}{{ $s ? ' · ' . $s['nombre'] . ' · ' . R::nombre($fila['etapa']) : ' · sin siembra' }}">
                                                    <span class="w-5 text-right text-[10px] tabular-nums text-pewter">{{ $numerar || $fila['hilera'] % 5 === 0 || $fila['hilera'] === 1 ? $fila['hilera'] : '' }}</span>
                                                    <div class="flex-1 rounded-[3px]" style="height: {{ $alto }}px; background: {{ $fila['color'] }}; {{ $s ? '' : 'background-image: repeating-linear-gradient(45deg, transparent 0 4px, rgb(0 0 0 / .06) 4px 8px)' }}"></div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="w-28 shrink-0 border-l-2 pl-2 text-xs" style="border-color: {{ $s ? R::color($etapa) : '#CBD5E1' }}">
                                            @if ($s)
                                                <p class="font-semibold text-ink">{{ $s['nombre'] }}</p>
                                                <p class="text-graphite">hileras {{ $s['hilera_desde'] }}–{{ $s['hilera_hasta'] }}</p>
                                                <p class="mt-0.5"><span class="{{ $chip }}" style="background: {{ R::color($etapa) }}">{{ R::nombre($etapa) }}</span></p>
                                            @else
                                                <p class="text-pewter">Sin siembra</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Línea de tiempo de cada siembra --}}
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase text-graphite">Etapas de cada siembra en el tiempo</p>
                            <div class="space-y-3 rounded-[10px] border border-mist bg-paper p-3">
                                @foreach ($dibujo['lineas'] as $linea)
                                    @php $s = $sectores[$linea['sector']] ?? null; @endphp
                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-xs">
                                            <span class="font-medium text-ink">{{ $s['nombre'] ?? '' }} <span class="font-normal text-pewter">@if (!empty($s['fecha_siembra'])) · sembrada el {{ $f($s['fecha_siembra']) }} @endif · hileras {{ $s['hilera_desde'] ?? '' }}–{{ $s['hilera_hasta'] ?? '' }}</span></span>
                                            @if (($s['origen'] ?? '') === 'detectado')
                                                <span class="text-pewter" title="Armado por el sistema con las labores registradas; corrígelo si sabes cómo fue">detectado</span>
                                            @endif
                                        </div>
                                        <div class="relative h-6 overflow-hidden rounded-[6px] bg-fog">
                                            @foreach ($linea['tramos'] as $t)
                                                <div class="absolute inset-y-0 flex items-center overflow-hidden whitespace-nowrap px-1 text-[10px] font-medium text-white"
                                                    style="left: {{ $t['izq'] }}%; width: {{ $t['ancho'] }}%; background: {{ R::color($t['etapa']) }}"
                                                    title="{{ R::nombre($t['etapa']) }} desde el {{ $f($t['desde']) }}">
                                                    {{ $t['etapa'] ? R::nombre($t['etapa']) : '' }}
                                                </div>
                                            @endforeach
                                            <div class="absolute inset-y-0 w-0.5 bg-ink" style="left: {{ $dibujo['marca'] }}%" title="{{ $f($dibujo['fecha']) }}"></div>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="flex justify-between text-[10px] tabular-nums text-pewter">
                                    <span>{{ $f($dibujo['inicio']) }}</span><span>{{ $f($dibujo['fin']) }}</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 border-t border-hairline pt-2">
                                    @foreach ($etapas as $clave => [$nombre, $color])
                                        <span class="{{ $chip }}" style="background: {{ $color }}">{{ $nombre }}</span>
                                    @endforeach
                                    <span class="{{ $chip }} !text-graphite" style="background: {{ R::SIN_ETAPA[1] }}">{{ R::SIN_ETAPA[0] }}</span>
                                </div>
                            </div>

                            {{-- Labores de etapa registradas (de dónde salen las fechas) --}}
                            <details class="mt-3 rounded-[10px] border border-mist" @if (!$resumenLabores) open @endif>
                                <summary class="cursor-pointer select-none px-3 py-2 text-xs font-medium text-graphite">
                                    Labores de etapa registradas en la campaña ({{ count($resumenLabores) }} etapa(s))
                                </summary>
                                <div class="border-t border-hairline px-3 py-2 text-xs">
                                    @forelse ($resumenLabores as $r)
                                        <p class="py-0.5"><span class="{{ $chip }}" style="background: {{ R::color($r['etapa']) }}">{{ R::nombre($r['etapa']) }}</span>
                                            <span class="text-ink">{{ $r['dias'] }} día(s)</span> <span class="text-graphite">del {{ $f($r['desde']) }} al {{ $f($r['hasta']) }}</span></p>
                                    @empty
                                        <p class="text-graphite">No hay labores de preparación, siembra, infestación ni poda registradas en esta campaña.</p>
                                    @endforelse
                                </div>
                            </details>
                            @if ($fuera->isNotEmpty())
                                <p class="mt-2 rounded-[10px] bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
                                    <i class="fa fa-triangle-exclamation"></i>
                                    {{ $fuera->pluck('fecha')->unique()->count() }} día(s) con labores fuera de su etapa en todas las siembras
                                    (ej. {{ $fuera->take(3)->map(fn($r) => $f($r['fecha']) . ' ' . $r['labor'] . ' · ' . $r['nombre'])->implode('; ') }}).
                                    Revisa si es un error de código, un replante, o si alguna siembra iba en esa etapa.
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Edición --}}
                    @if ($editando)
                        <div class="space-y-3 rounded-[10px] border border-carmin/40 p-3">
                            <div class="flex flex-wrap items-end gap-3">
                                <label class="block">
                                    <span class="mb-1 block text-xs font-medium text-graphite">Hileras del campo {{ $campania->campo }}</span>
                                    <input type="number" min="1" max="500" wire:model.blur="hileras" class="{{ $input }} w-28" />
                                </label>
                                <p class="mb-2 flex-1 text-xs text-graphite">Es propio del campo (vale para todas sus campañas). Al cambiarlo, las siembras se ajustan en proporción.</p>
                                <button type="button" class="{{ $btn2 }}" wire:click="agregarGrupo"><i class="fa fa-plus"></i> Siembra (relleno)</button>
                            </div>
                            @foreach ($sectores as $i => $s)
                                <div class="rounded-[10px] border border-mist p-2" wire:key="sector-{{ $i }}">
                                    <div class="flex flex-wrap items-end gap-2">
                                        <label class="block"><span class="mb-1 block text-xs text-graphite">Nombre</span>
                                            <input type="text" wire:model.blur="sectores.{{ $i }}.nombre" maxlength="40" class="{{ $input }} w-36" /></label>
                                        <label class="block"><span class="mb-1 block text-xs text-graphite">Fecha de siembra{{ count($sectores) > 1 ? ' *' : '' }}</span>
                                            <input type="date" wire:model.blur="sectores.{{ $i }}.fecha_siembra" class="{{ $input }}" /></label>
                                        <label class="block"><span class="mb-1 block text-xs text-graphite">Hilera desde</span>
                                            <input type="number" min="1" max="{{ $hileras }}" wire:model.live.debounce.500ms="sectores.{{ $i }}.hilera_desde" class="{{ $input }} w-24" /></label>
                                        <label class="block"><span class="mb-1 block text-xs text-graphite">hasta</span>
                                            <input type="number" min="1" max="{{ $hileras }}" wire:model.live.debounce.500ms="sectores.{{ $i }}.hilera_hasta" class="{{ $input }} w-24" /></label>
                                        <button type="button" class="{{ $btn2 }}" wire:click="agregarEtapa({{ $i }})"><i class="fa fa-plus"></i> Etapa</button>
                                        @if (count($sectores) > 1)
                                            <button type="button" class="{{ $btn2 }} !text-red-700" wire:click="quitarGrupo({{ $i }})" title="Quitar siembra"><i class="fa fa-trash"></i></button>
                                        @endif
                                    </div>
                                    <div class="mt-2 space-y-1">
                                        @forelse ($s['etapas'] as $k => $e)
                                            <div class="flex flex-wrap items-center gap-2" wire:key="sector-{{ $i }}-etapa-{{ $k }}">
                                                <span class="h-3 w-3 rounded-full" style="background: {{ R::color($e['etapa'] ?: null) }}"></span>
                                                <select wire:model.live="sectores.{{ $i }}.etapas.{{ $k }}.etapa" class="{{ $input }} w-52">
                                                    <option value="">Elige la etapa…</option>
                                                    @foreach ($etapas as $clave => [$nombre])
                                                        <option value="{{ $clave }}">{{ $nombre }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="text-xs text-graphite">desde</span>
                                                <input type="date" wire:model.live="sectores.{{ $i }}.etapas.{{ $k }}.fecha_desde" class="{{ $input }}" />
                                                <button type="button" class="text-xs text-red-700 hover:underline dark:text-red-400" wire:click="quitarEtapa({{ $i }}, {{ $k }})">Quitar</button>
                                            </div>
                                        @empty
                                            <p class="text-xs text-pewter">Sin etapas: agrega desde cuándo está en cada una.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                            @if ($siembraExistenteMensaje)
                                <div class="rounded-[10px] bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-400/10 dark:text-amber-400" role="alert">
                                    <p class="font-medium"><i class="fa fa-triangle-exclamation"></i> {{ $siembraExistenteMensaje }}</p>
                                    <p class="mt-1 text-xs">Si confirmas, pasa a ser una siembra parcial de esta campaña y los registros repetidos se unen en uno.</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <button type="button" class="{{ $btn2 }}" wire:click="guardar(true)" wire:loading.attr="disabled"><i class="fa fa-check"></i> Sí, es esta siembra</button>
                                        <button type="button" class="{{ $btn2 }}" wire:click="noEsSiembraExistente">No, corregir la fecha</button>
                                    </div>
                                </div>
                            @endif
                            <p class="text-xs text-graphite">
                                Una campaña tiene otra siembra solo si parte del campo se sembró en otra fecha (ej. relleno a 2 m meses después): cada una
                                necesita su fecha de siembra y lleva su propia infestación y cosecha. Cada siembra está en una etapa desde su fecha hasta que
                                empieza la siguiente. No se puede guardar si una labor ya registrada queda fuera de su etapa en todas las siembras.
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <div class="flex w-full flex-wrap items-center justify-between gap-2">
                <div>
                    @if ($puedeEditar && $editando)
                        <button type="button" class="{{ $btn2 }}" wire:click="redetectar"
                            wire:confirm="¿Volver a armar las siembras con las labores registradas? Se pierde lo corregido a mano en esta campaña.">
                            <i class="fa fa-wand-magic-sparkles"></i> Volver a detectar
                        </button>
                    @endif
                </div>
                <div class="flex gap-2">
                    @if ($editando)
                        <button type="button" class="{{ $btn2 }}" wire:click="cancelar">Cancelar</button>
                        <button type="button" class="{{ $btnPrincipal }}" wire:click="guardar" wire:loading.attr="disabled"><i class="fa fa-check"></i> Guardar</button>
                    @else
                        <button type="button" class="{{ $btn2 }}" wire:click="$set('mostrar', false)">Cerrar</button>
                        @if ($puedeEditar)
                            <button type="button" class="{{ $btnPrincipal }}" wire:click="editar"><i class="fa fa-pen"></i> Corregir hileras y etapas</button>
                        @endif
                    @endif
                </div>
            </div>
        </x-slot>
    </x-dialog-modal>

    {{-- Candado: la siembra que se quita tiene evaluaciones --}}
    <x-dialog-modal wire:model="avisoEvaluaciones" maxWidth="4xl">
        <x-slot name="title">
            <span class="text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> Advertencia: siembra con evaluaciones</span>
        </x-slot>
        <x-slot name="content">
            <div class="space-y-3 text-sm">
                <p class="text-ink">
                    {{ collect($evaluacionesVinculadas)->pluck('siembra')->unique()->implode(', ') }} tiene {{ count($evaluacionesVinculadas) }} evaluación(es) vinculada(s).
                    Si quitas la siembra, <strong>cada una debe pasar a otra siembra o eliminarse</strong>. Nada cambia hasta que presiones Guardar en el mapa.
                </p>
                <div class="overflow-x-auto rounded-[10px] border border-mist">
                    <table class="w-full text-[13px]">
                        <thead class="bg-fog text-left text-xs text-graphite">
                            <tr>
                                <th class="px-3 py-2 font-medium">Evaluación</th>
                                <th class="px-3 py-2 font-medium">Fecha</th>
                                <th class="px-3 py-2 font-medium">Evaluador</th>
                                <th class="px-3 py-2 text-right font-medium">Camas</th>
                                <th class="px-3 py-2 font-medium">Por fecha corresponde a</th>
                                <th class="px-3 py-2 font-medium">Qué hacer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($evaluacionesVinculadas as $e)
                                @php
                                    $sugerida = \App\Services\Evaluacion\Siembra\EvaluacionSiembraConsulta::sugerida($e['fecha'], $destinosEvaluacion);
                                    $accion = $decisiones[$e['clave']]['accion'] ?? 'mover';
                                @endphp
                                <tr class="border-t border-hairline" wire:key="aviso-{{ $e['clave'] }}">
                                    <td class="px-3 py-2 font-medium text-ink">{{ $e['nombre_tipo'] }} <span class="block text-xs font-normal text-pewter">{{ $e['siembra'] }}</span></td>
                                    <td class="px-3 py-2 tabular-nums">{{ $f($e['fecha']) }}</td>
                                    <td class="px-3 py-2">{{ $e['evaluador'] ?: '—' }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $e['camas'] }}</td>
                                    <td class="px-3 py-2">
                                        @if ($sugerida)
                                            {{ $sugerida['nombre'] }}
                                        @else
                                            <span class="text-amber-700 dark:text-amber-400"><i class="fa fa-triangle-exclamation"></i> Ninguna otra siembra existía ese día</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-2">
                                            <select wire:model.live="decisiones.{{ $e['clave'] }}.accion" class="{{ $input }}" aria-label="Qué hacer con la evaluación">
                                                <option value="mover">Pasar a otra siembra</option>
                                                <option value="eliminar">Eliminar evaluación</option>
                                            </select>
                                            @if ($accion === 'mover')
                                                <select wire:model.live="decisiones.{{ $e['clave'] }}.destino" class="{{ $input }}" aria-label="Siembra de destino">
                                                    <option value="">Elige la siembra…</option>
                                                    @foreach ($destinosEvaluacion as $d)
                                                        <option value="{{ $d['clave'] }}">{{ $d['nombre'] }}{{ $d['fecha_siembra'] ? ' · ' . $f($d['fecha_siembra']) : '' }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-end gap-2 rounded-[10px] bg-fog p-3">
                    <label class="block"><span class="mb-1 block text-xs font-medium text-graphite">¿Son de una siembra que falta? Agrégala con su fecha</span>
                        <input type="date" wire:model="fechaSiembraDestino" class="{{ $input }}" /></label>
                    <button type="button" class="{{ $btn2 }}" wire:click="agregarSiembraDestino"><i class="fa fa-plus"></i> Nueva siembra</button>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex gap-2">
                <button type="button" class="{{ $btn2 }}" wire:click="cancelarAviso">No quitar la siembra</button>
                <button type="button" class="{{ $btnPrincipal }}" wire:click="confirmarAviso"><i class="fa fa-check"></i> Continuar</button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
