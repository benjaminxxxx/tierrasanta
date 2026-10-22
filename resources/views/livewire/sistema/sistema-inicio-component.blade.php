{{-- Inicio (dashboard) según DESIGN.md: tarjetas Papel con borde fino, carmín solo en la acción principal --}}
@php
    $ind = $datos['indicadores'];
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'];
    $mesesLargos = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $etiquetasLargas = array_map(fn($m) => "{$m} {$anio}", $mesesLargos);
    $kg = fn($v) => number_format($v, 0) . ' kg';
    $soles = fn($v) => 'S/ ' . number_format($v, 0);
    // Variación contra el mes anterior: null si no hay base
    $variacion = fn(array $par) => $par['anterior'] > 0 ? round(($par['valor'] - $par['anterior']) / $par['anterior'] * 100, 1) : null;
    $tarjeta = 'rounded-[10px] border border-mist bg-paper';
@endphp

<div class="font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em] text-ink space-y-6">

    {{-- Encabezado y periodo --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Inicio</h1>
            <p class="text-sm text-graphite">Hola, {{ str(auth()->user()->name)->before(' ') }}. Resumen de <span class="font-medium text-ink">{{ $periodo }}</span>.</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center rounded-[10px] border border-mist bg-paper">
                <button type="button" wire:click="mesAnterior" class="h-9 w-9 text-graphite hover:text-ink" title="Mes anterior" aria-label="Mes anterior">
                    <i class="fa fa-chevron-left text-xs"></i>
                </button>
                <span class="min-w-[9rem] text-center text-sm font-medium">{{ \Illuminate\Support\Str::ucfirst($periodo) }}</span>
                <button type="button" wire:click="mesSiguiente" @disabled($esMesActual) class="h-9 w-9 text-graphite hover:text-ink disabled:opacity-30" title="Mes siguiente" aria-label="Mes siguiente">
                    <i class="fa fa-chevron-right text-xs"></i>
                </button>
            </div>
            <button type="button" wire:click="actualizar" class="h-9 rounded-[10px] border border-mist bg-paper px-3 text-sm text-graphite hover:bg-fog hover:text-ink"
                title="Volver a calcular (los datos se guardan 5 minutos)">
                <i class="fa fa-rotate" wire:loading.class="fa-spin" wire:target="actualizar"></i>
                <span class="hidden sm:inline">Actualizar</span>
            </button>
        </div>
    </div>

    {{-- Indicadores del mes --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @if ($ver['cochinilla'])
            @php $v = $variacion($ind['cosecha_kg']); @endphp
            <div class="{{ $tarjeta }} p-5">
                <p class="text-sm text-graphite">Cochinilla cosechada</p>
                <p class="mt-2 text-[28px] font-semibold leading-none">{{ $kg($ind['cosecha_kg']['valor']) }}</p>
                <p class="mt-3 text-xs text-pewter">
                    @if ($v !== null)
                        <span class="font-medium {{ $v >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                            <i class="fa {{ $v >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i> {{ abs($v) }} %
                        </span> vs mes anterior ·
                    @endif
                    {{ $kg($ind['cosecha_anio_kg']) }} en {{ $anio }}
                </p>
            </div>
        @endif
        @if ($ver['costos'])
            @php $v = $variacion($ind['mano_obra']); @endphp
            <div class="{{ $tarjeta }} p-5">
                <p class="text-sm text-graphite">Costo de mano de obra</p>
                <p class="mt-2 text-[28px] font-semibold leading-none">{{ $soles($ind['mano_obra']['valor']) }}</p>
                <p class="mt-3 text-xs text-pewter">
                    @if ($v !== null)
                        <span class="font-medium text-graphite"><i class="fa {{ $v >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i> {{ abs($v) }} %</span> vs mes anterior ·
                    @endif
                    planilla, cuadrilla e indirecta
                </p>
            </div>
        @endif
        @if ($ver['compras'])
            @php $v = $variacion($ind['compras']); @endphp
            <div class="{{ $tarjeta }} p-5">
                <p class="text-sm text-graphite">Compras</p>
                <p class="mt-2 text-[28px] font-semibold leading-none">{{ $soles($ind['compras']['valor']) }}</p>
                <p class="mt-3 text-xs text-pewter">
                    @if ($v !== null)
                        <span class="font-medium text-graphite"><i class="fa {{ $v >= 0 ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i> {{ abs($v) }} %</span> vs mes anterior ·
                    @endif
                    {{ $ind['compras_cantidad'] }} comprobante(s)
                </p>
            </div>
        @endif
        @if ($ver['personal'])
            <div class="{{ $tarjeta }} p-5">
                <p class="text-sm text-graphite">Personal del mes</p>
                <p class="mt-2 text-[28px] font-semibold leading-none">{{ number_format($ind['planilla_agraria'] + $ind['planilla_oficina'] + $ind['cuadrilleros']['valor']) }}</p>
                <p class="mt-3 text-xs text-pewter">
                    {{ $ind['planilla_agraria'] }} planilla agraria · {{ $ind['planilla_oficina'] }} oficina · {{ $ind['cuadrilleros']['valor'] }} cuadrilleros
                </p>
            </div>
        @endif
    </div>

    {{-- Gráficos del año --}}
    @if ($ver['cochinilla'] || $ver['costos'])
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            @if ($ver['cochinilla'])
                <div class="{{ $tarjeta }} p-5" wire:key="g-cosecha-{{ $anio }}-{{ $mes }}-{{ $datos['generado'] }}"
                    x-data="inicioGrafico(@js(['tipo' => 'enfasis', 'unidad' => 'kg', 'resaltar' => $mes - 1, 'etiquetas' => $meses, 'etiquetasLargas' => $etiquetasLargas,
                        'series' => [['nombre' => 'Cochinilla cosechada', 'datos' => array_values($datos['cosecha'])]]]))">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold">Cochinilla cosechada por mes</h2>
                            <p class="text-xs text-pewter">Kilos ingresados en {{ $anio }}. Resaltado: {{ mb_strtolower($mesesLargos[$mes - 1]) }}.</p>
                        </div>
                        <button type="button" @click="tabla = !tabla" class="shrink-0 text-xs text-graphite underline-offset-4 hover:underline" x-text="tabla ? 'Ver gráfico' : 'Ver tabla'"></button>
                    </div>
                    <div class="relative mt-4 h-64" x-show="!tabla"><canvas x-ref="lienzo" role="img" aria-label="Cochinilla cosechada por mes en {{ $anio }}"></canvas></div>
                    <div x-show="tabla" x-cloak class="mt-4 max-h-64 overflow-y-auto">
                        <table class="w-full text-[13px]">
                            <thead class="bg-fog text-xs text-graphite"><tr><th class="px-3 py-2 text-left font-medium">Mes</th><th class="px-3 py-2 text-right font-medium">Kilos</th></tr></thead>
                            <tbody>
                                @foreach ($datos['cosecha'] as $m => $valor)
                                    <tr class="border-t border-hairline {{ $m === $mes ? 'font-semibold' : '' }}"><td class="px-3 py-1.5">{{ $mesesLargos[$m - 1] }}</td><td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($valor, 2) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($ver['costos'])
                @php $grupos = array_keys($datos['mano_obra']); @endphp
                <div class="{{ $tarjeta }} p-5" wire:key="g-mo-{{ $anio }}-{{ $mes }}-{{ $datos['generado'] }}"
                    x-data="inicioGrafico(@js(['tipo' => 'apilado', 'unidad' => 'S/', 'etiquetas' => $meses, 'etiquetasLargas' => $etiquetasLargas,
                        'series' => collect($datos['mano_obra'])->map(fn($d, $g) => ['nombre' => $g, 'datos' => array_values($d)])->values()->all()]))">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold">Costo de mano de obra por mes</h2>
                            <p class="text-xs text-pewter">Soles en {{ $anio }}, según la BDD de costos.</p>
                        </div>
                        <button type="button" @click="tabla = !tabla" class="shrink-0 text-xs text-graphite underline-offset-4 hover:underline" x-text="tabla ? 'Ver gráfico' : 'Ver tabla'"></button>
                    </div>
                    {{-- Leyenda: el color va en la marca, el texto en tinta --}}
                    <div class="mt-3 flex flex-wrap gap-4 text-xs text-graphite">
                        @foreach ($grupos as $i => $g)
                            <span class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-sm {{ ['bg-[#2a78d6] dark:bg-[#3987e5]', 'bg-[#eb6834] dark:bg-[#d95926]', 'bg-[#1baf7a] dark:bg-[#199e70]'][$i] }}"></span>
                                {{ $g }} <span class="text-pewter tabular-nums">{{ $soles(array_sum($datos['mano_obra'][$g])) }}</span>
                            </span>
                        @endforeach
                    </div>
                    <div class="relative mt-3 h-56" x-show="!tabla"><canvas x-ref="lienzo" role="img" aria-label="Costo de mano de obra por mes en {{ $anio }}"></canvas></div>
                    <div x-show="tabla" x-cloak class="mt-3 max-h-64 overflow-y-auto">
                        <table class="w-full text-[13px]">
                            <thead class="bg-fog text-xs text-graphite">
                                <tr><th class="px-3 py-2 text-left font-medium">Mes</th>
                                    @foreach ($grupos as $g)<th class="px-3 py-2 text-right font-medium">{{ $g }}</th>@endforeach
                                    <th class="px-3 py-2 text-right font-medium">Total</th></tr>
                            </thead>
                            <tbody>
                                @foreach (range(1, 12) as $m)
                                    <tr class="border-t border-hairline {{ $m === $mes ? 'font-semibold' : '' }}">
                                        <td class="px-3 py-1.5">{{ $mesesLargos[$m - 1] }}</td>
                                        @foreach ($grupos as $g)<td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($datos['mano_obra'][$g][$m], 0) }}</td>@endforeach
                                        <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format(collect($grupos)->sum(fn($g) => $datos['mano_obra'][$g][$m]), 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        {{-- Alertas: tareas pendientes del mes --}}
        <div class="{{ $tarjeta }} p-5 xl:col-span-2">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold">Alertas</h2>
                    <p class="text-xs text-pewter">Tareas pendientes de {{ $periodo }}.</p>
                </div>
                <button type="button" wire:click="abrirTareas"
                    class="h-9 shrink-0 rounded-[10px] bg-carmin px-3 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                    Revisar {{ $alertas['total'] ? "las {$alertas['total']}" : 'tareas' }}
                </button>
            </div>
            <div class="mt-4 divide-y divide-hairline">
                @forelse ($alertas['tareas'] as $t)
                    @php
                        [$icono, $color, $fondo] = match ($t['variante']) {
                            'danger' => ['fa-circle-exclamation', 'text-red-700 dark:text-red-400', 'bg-red-50 dark:bg-red-400/10'],
                            'info' => ['fa-circle-info', 'text-blue-700 dark:text-blue-400', 'bg-blue-50 dark:bg-blue-400/10'],
                            default => ['fa-triangle-exclamation', 'text-amber-700 dark:text-amber-400', 'bg-amber-50 dark:bg-amber-400/10'],
                        };
                    @endphp
                    <div class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md {{ $fondo }} {{ $color }}"><i class="fa {{ $icono }} text-sm"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">{{ $t['titulo'] }}</p>
                            @if ($t['descripcion'])
                                <p class="text-xs text-pewter line-clamp-2">{{ $t['descripcion'] }}</p>
                            @endif
                        </div>
                        @if ($t['cantidad'])
                            <span class="shrink-0 rounded-md bg-fog px-2 py-0.5 text-xs font-medium tabular-nums text-graphite">{{ number_format($t['cantidad']) }}</span>
                        @endif
                    </div>
                @empty
                    <div class="flex items-center gap-3 py-6 text-sm text-graphite">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md bg-green-50 text-green-700 dark:bg-green-400/10 dark:text-green-400"><i class="fa fa-check"></i></span>
                        Sin tareas pendientes registradas para este mes. Usa "Revisar tareas" para buscar.
                    </div>
                @endforelse
                @if ($alertas['total'] > count($alertas['tareas']))
                    <p class="pt-3 text-xs text-pewter">y {{ $alertas['total'] - count($alertas['tareas']) }} más.</p>
                @endif
            </div>
        </div>

        {{-- Campo en el mes --}}
        <div class="{{ $tarjeta }} p-5 space-y-4">
            <h2 class="text-base font-semibold">Campo</h2>
            @if ($ver['campanias'])
                <a href="{{ route('campania.por_campo') }}" class="block rounded-[10px] bg-fog p-4 text-ink hover:bg-hairline">
                    <p class="text-xs text-graphite">Campañas en curso</p>
                    <p class="mt-1 text-2xl font-semibold">{{ number_format($ind['campanias_en_curso']) }}</p>
                </a>
            @endif
            @if ($ver['cochinilla'])
                <a href="{{ route('cochinilla.infestacion') }}" class="block rounded-[10px] bg-fog p-4 text-ink hover:bg-hairline">
                    <p class="text-xs text-graphite">Infestación del mes</p>
                    <p class="mt-1 text-2xl font-semibold">{{ number_format($ind['infestacion']['kg'], 0) }} kg</p>
                    <p class="text-xs text-pewter">de madres en {{ $ind['infestacion']['campos'] }} campo(s)</p>
                </a>
            @endif
            @if (!$ver['campanias'] && !$ver['cochinilla'])
                <p class="text-sm text-pewter">Sin módulos de campo asignados.</p>
            @endif
        </div>
    </div>

    {{-- Escritorio de archivos: libros Excel (recursos, insumos, empleados) y descargas del mes --}}
    <livewire:sistema.sistema-escritorio-archivos-component />

    {{-- Accesos del flujo diario --}}
    @if ($accesos)
        <div>
            <h2 class="mb-3 text-base font-semibold">Accesos</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
                @foreach ($accesos as [$titulo, $descripcion, $icono, $ruta])
                    <a href="{{ route($ruta) }}"
                        class="group {{ $tarjeta }} flex flex-col gap-3 p-4 text-ink hover:border-graphite/40 hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-fog text-graphite group-hover:bg-paper group-hover:text-ink"><i class="fa {{ $icono }}"></i></span>
                        <span>
                            <span class="block text-sm font-medium text-ink">{{ $titulo }}</span>
                            <span class="block text-xs text-pewter">{{ $descripcion }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <p class="text-xs text-pewter">Datos calculados a las {{ $datos['generado'] }}.</p>
</div>
