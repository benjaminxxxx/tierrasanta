{{-- Línea de tiempo de la campaña: etapas por fecha, día de hoy y evaluaciones de brotes sugeridas.
     $lineaTiempo: CampaniaEtapaConsulta::lineaDeTiempo() --}}
@php
    $lt = $lineaTiempo;
    $etapas = \App\Services\Campania\Etapa\CampaniaEtapaReglas::ETAPAS;
    $pos = fn($dia) => $dia === null ? null : max(0, min(100, $dia / max(1, $lt['total_dias']) * 100));
    $fecha = fn($f) => \Illuminate\Support\Carbon::parse($f)->format('d/m/Y');
    $alcanzadas = collect($lt['hitos'])->groupBy('etapa');
    $ordenActual = $lt['actual'] ? array_search($lt['actual']['etapa'], array_keys($etapas)) : -1;
    $vencidas = array_values(array_filter($lt['sugeridas'], fn($s) => $s['vencida']));
@endphp
<x-card class="mt-4 space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="font-semibold text-foreground">Etapas de la campaña</h3>
            @if ($lt['actual'])
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold text-white" style="background: {{ $lt['actual']['color'] }}">
                    {{ $lt['actual']['nombre'] }}
                </span>
            @endif
            @if ($lt['hoy_dia'] !== null)
                <span class="text-xs text-muted-foreground">Día {{ $lt['hoy_dia'] }} desde el inicio</span>
            @endif
        </div>
        @if ($vencidas)
            <span class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                <i class="fa fa-lightbulb"></i> Sugerencia: toca la {{ $vencidas[0]['numero'] }}ª evaluación de brotes (día {{ $vencidas[0]['dias'] }}, desde el {{ $fecha($vencidas[0]['fecha']) }})
            </span>
        @endif
    </div>

    @if ($lt['inicio'])
        {{-- Barra proporcional a los días de la campaña --}}
        <div class="relative h-14 mx-2">
            <div class="absolute left-0 right-0 top-6 h-1.5 rounded-full bg-muted"></div>
            @if ($lt['hoy_dia'] !== null)
                <div class="absolute top-6 h-1.5 rounded-full bg-primary/40" style="left:0; width: {{ $pos($lt['hoy_dia']) }}%"></div>
                <div class="absolute top-2 bottom-2 w-px bg-primary" style="left: {{ $pos($lt['hoy_dia']) }}%" title="Hoy (día {{ $lt['hoy_dia'] }})">
                    <span class="absolute -top-1 -translate-x-1/2 text-[10px] font-semibold text-primary whitespace-nowrap">Hoy</span>
                </div>
            @endif
            {{-- Evaluaciones sugeridas que aún no se registran --}}
            @foreach ($lt['sugeridas'] as $s)
                <div class="absolute top-[18px] w-3.5 h-3.5 -translate-x-1/2 rounded-full border-2 border-dashed {{ $s['vencida'] ? 'border-amber-500' : 'border-muted-foreground/50' }} bg-background"
                    style="left: {{ $pos($s['dias']) }}%"
                    title="{{ $s['numero'] }}ª evaluación de brotes sugerida: día {{ $s['dias'] }} ({{ $fecha($s['fecha']) }}){{ $s['vencida'] ? ' — ya pasó' : '' }}"></div>
            @endforeach
            @foreach ($lt['hitos'] as $h)
                @if ($h['dia'] !== null)
                    <div class="absolute top-[18px] w-3.5 h-3.5 -translate-x-1/2 rounded-full ring-2 ring-background shadow"
                        style="left: {{ $pos($h['dia']) }}%; background: {{ $h['color'] }}"
                        title="{{ $h['nombre'] }}{{ $h['detalle'] ? ' (' . $h['detalle'] . ')' : '' }}: {{ $fecha($h['fecha']) }} · día {{ $h['dia'] }}"></div>
                @endif
            @endforeach
            <span class="absolute left-0 bottom-0 text-[10px] text-muted-foreground">{{ $fecha($lt['inicio']) }}</span>
            <span class="absolute right-0 bottom-0 text-[10px] text-muted-foreground">{{ $fecha($lt['fin']) }}</span>
        </div>
    @else
        <p class="text-sm text-muted-foreground">La campaña no tiene fecha de inicio: no se puede ubicar en el tiempo.</p>
    @endif

    {{-- Etapas en orden: hechas (con su fecha), la actual y las que faltan --}}
    <ol class="flex flex-wrap gap-2">
        @foreach ($etapas as $clave => [$nombre, $color])
            @php
                $hitos = $alcanzadas->get($clave, collect());
                $hecha = $hitos->isNotEmpty();
                $esActual = $lt['actual'] && $lt['actual']['etapa'] === $clave;
                $orden = array_search($clave, array_keys($etapas));
            @endphp
            <li class="flex items-center gap-1.5 px-2 py-1 rounded-md border text-xs
                {{ $esActual ? 'border-transparent text-white shadow-sm' : ($hecha ? 'border-border' : 'border-dashed border-border text-muted-foreground') }}"
                @if ($esActual) style="background: {{ $color }}" @endif
                title="{{ $hecha ? $hitos->map(fn($h) => ($h['detalle'] ? $h['detalle'] . ': ' : '') . $fecha($h['fecha']))->implode(' · ') : 'Sin fecha registrada' }}">
                <span class="w-2 h-2 rounded-full" style="background: {{ $hecha || $esActual ? ($esActual ? '#fff' : $color) : 'transparent' }}; border: 1px solid {{ $color }}"></span>
                <span class="font-semibold">{{ $nombre }}</span>
                @if ($hecha)
                    <span class="{{ $esActual ? 'text-white/90' : 'text-muted-foreground' }}">
                        {{ $fecha($hitos->last()['fecha']) }}{{ $hitos->count() > 1 ? ' (' . $hitos->count() . ')' : '' }}
                    </span>
                @elseif ($orden < $ordenActual)
                    <span class="text-muted-foreground">—</span>
                @endif
            </li>
        @endforeach
    </ol>
    <p class="text-[11px] text-muted-foreground">Las etapas salen de las fechas registradas en la campaña. Los círculos punteados son las evaluaciones de brotes sugeridas según Sistema → Configuración (no es una regla estricta).</p>
</x-card>
