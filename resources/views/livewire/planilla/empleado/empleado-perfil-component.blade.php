@php
    $r = $perfil['resumen'];
    $empleado = $perfil['empleado'];

    $colorEstado = [
        'verde' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'ambar' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'azul' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'rojo' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'gris' => 'bg-muted text-muted-foreground',
    ][$r['estado_color']];

    // Icono y color por tipo de evento
    $estilos = [
        'ingreso' => ['fa-right-to-bracket', 'bg-green-600'],
        'reingreso' => ['fa-rotate-left', 'bg-emerald-600'],
        'renovacion' => ['fa-file-signature', 'bg-teal-600'],
        'prueba' => ['fa-hourglass-half', 'bg-sky-600'],
        'cese' => ['fa-right-from-bracket', 'bg-red-600'],
        'fin_programado' => ['fa-calendar-xmark', 'bg-amber-500'],
        'cargo' => ['fa-id-badge', 'bg-indigo-600'],
        'cargo_fin' => ['fa-id-badge', 'bg-slate-500'],
        'sueldo' => ['fa-money-bill-trend-up', 'bg-lime-600'],
        'suspension' => ['fa-pause', 'bg-orange-500'],
        'familiar' => ['fa-people-roof', 'bg-fuchsia-600'],
        'eliminado' => ['fa-trash', 'bg-red-800'],
    ];

    $eventosPorAnio = collect($perfil['eventos'])->groupBy(fn($e) => substr($e['fecha'], 0, 4));
@endphp

<div class="space-y-6">
    {{-- Encabezado --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <x-h3>{{ $empleado->nombreCompleto }}</x-h3>
            <p class="text-sm text-muted-foreground">
                DNI {{ $empleado->documento }}
                @if ($r['edad']) · {{ $r['edad'] }} años @endif
            </p>
        </div>
        <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $colorEstado }}">{{ $r['estado'] }}</span>
    </div>

    {{-- Resumen --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        @foreach ([
            ['Primer ingreso', $r['primer_ingreso'] ? \Carbon\Carbon::parse($r['primer_ingreso'])->format('d/m/Y') : '—'],
            ['Tiempo trabajado', $r['antiguedad_texto']],
            ['Contratos', $r['contratos'] . ($r['reingresos'] ? " · {$r['reingresos']} reingreso(s)" : '')],
            ['Cargo actual', $r['cargo'] ?? '—'],
            ['Sueldo actual', $r['sueldo'] !== null ? 'S/ ' . number_format($r['sueldo'], 2) : '—'],
        ] as [$etiqueta, $valor])
            <div class="rounded-lg border border-border p-3">
                <div class="text-xs uppercase text-muted-foreground">{{ $etiqueta }}</div>
                <div class="font-semibold mt-0.5">{{ $valor }}</div>
            </div>
        @endforeach
        <div class="rounded-lg border border-border p-3">
            <div class="text-xs uppercase text-muted-foreground">Grupo</div>
            <div class="font-semibold mt-0.5 flex items-center gap-1.5">
                @if ($r['grupo'])
                    <span class="inline-block size-2.5 rounded-full border border-border" style="background-color: {{ $r['grupo']->color }}"></span>
                    {{ $r['grupo']->codigo }}
                @else
                    —
                @endif
            </div>
        </div>
    </div>

    {{-- Evolución del sueldo --}}
    @if (count($perfil['sueldos']) > 1)
        <div class="rounded-lg border border-border p-4">
            <div class="text-sm font-semibold mb-2">Evolución del sueldo</div>
            <div class="relative h-48" wire:ignore
                x-data="{
                    init() {
                        const datos = JSON.parse(this.$el.dataset.sueldos);
                        const dibujar = () => {
                            if (!window.Chart) { return setTimeout(dibujar, 300); }
                            const canvas = this.$refs.canvas;
                            Chart.getChart(canvas)?.destroy();
                            new Chart(canvas, {
                                type: 'line',
                                data: {
                                    labels: datos.map(d => d.fecha),
                                    datasets: [{ label: 'Sueldo', data: datos.map(d => d.monto), stepped: true, borderColor: '#65a30d', backgroundColor: 'rgba(101,163,13,.15)', fill: true, pointRadius: 4 }],
                                },
                                options: {
                                    responsive: true, maintainAspectRatio: false,
                                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => 'S/ ' + c.raw.toLocaleString('es-PE', { minimumFractionDigits: 2 }) } } },
                                    scales: { y: { ticks: { callback: v => 'S/ ' + v.toLocaleString('es-PE') } } },
                                },
                            });
                        };
                        dibujar();
                    }
                }"
                data-sueldos='@json($perfil['sueldos'])'>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    @endif

    {{-- Línea de tiempo --}}
    <div>
        <div class="text-sm font-semibold mb-3">Historia laboral</div>

        @forelse ($eventosPorAnio as $anio => $eventos)
            <div class="mb-2">
                <div class="inline-block px-2.5 py-0.5 mb-3 rounded-full bg-muted text-xs font-bold">{{ $anio }}</div>
                <ol class="relative border-s border-border ms-4">
                    @foreach ($eventos as $e)
                        @php [$icono, $color] = $estilos[$e['tipo']] ?? ['fa-circle', 'bg-slate-500']; @endphp
                        <li class="mb-5 ms-6 {{ !empty($e['futuro']) ? 'opacity-70' : '' }}">
                            <span class="absolute -start-3 flex items-center justify-center size-6 rounded-full ring-4 ring-card text-white text-[11px] {{ $color }}">
                                <i class="fa {{ $icono }}"></i>
                            </span>
                            <div class="flex flex-wrap items-baseline gap-x-2">
                                <time class="text-xs text-muted-foreground tabular-nums">{{ \Carbon\Carbon::parse($e['fecha'])->format('d/m/Y') }}</time>
                                <span class="font-semibold">{{ $e['titulo'] }}</span>
                                @if (!empty($e['futuro']))
                                    <span class="text-[11px] px-1.5 rounded bg-muted text-muted-foreground">programado</span>
                                @endif
                            </div>
                            @if (isset($e['monto']))
                                <div class="text-sm">
                                    S/ {{ number_format($e['monto'], 2) }}
                                    @if ($e['variacion'] !== null)
                                        <span class="{{ $e['variacion'] >= 0 ? 'text-green-600' : 'text-red-600' }} text-xs font-semibold">
                                            {{ $e['variacion'] >= 0 ? '+' : '' }}{{ number_format($e['variacion'], 2) }}
                                            ({{ $e['porcentaje'] >= 0 ? '+' : '' }}{{ $e['porcentaje'] }}%)
                                        </span>
                                    @endif
                                </div>
                            @endif
                            @if (!empty($e['detalle']))
                                <p class="text-sm text-muted-foreground">{{ $e['detalle'] }}</p>
                            @endif
                            @if (!empty($e['nota']))
                                <p class="text-xs text-emerald-700 dark:text-emerald-400 font-medium">{{ $e['nota'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @empty
            <p class="text-sm text-muted-foreground">Aún no hay historia registrada para este trabajador.</p>
        @endforelse
    </div>
</div>
