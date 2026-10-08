<div class="space-y-4">
    <x-card class="space-y-2">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h3 class="font-semibold text-foreground">{{ $titulo }}</h3>
            <span class="text-sm text-muted-foreground">
                {{ number_format($grafico['total']) }} {{ $mes ? 'trabajador-día(s)' : 'día(s) de suspensión' }}
            </span>
        </div>
        <p class="text-xs text-muted-foreground">
            {{ $mes ? 'Cuántos trabajadores estuvieron suspendidos cada día, por tipo: muestra qué días se concentran las ausencias.' : 'Días de suspensión de cada mes, por tipo: muestra qué tipos se usan más y en qué meses.' }}
            Elige un mes para verlo por día; elige un trabajador para ver solo lo suyo y su historial.
        </p>
        @if ($grafico['total'] > 0)
            <div class="relative h-80" wire:ignore x-data="{ chart: null, destroy() { this.chart?.destroy(); this.chart = null; } }"
                x-init="$nextTick(() => requestAnimationFrame(() => chart = window.dibujarGraficoSuspensiones($refs.canvas, @js($grafico))))">
                <canvas x-ref="canvas"></canvas>
            </div>
        @else
            <p class="text-sm text-muted-foreground py-10 text-center">Sin suspensiones con estos filtros.</p>
        @endif
    </x-card>

    @if ($historial)
        <x-card class="space-y-3">
            <h3 class="font-semibold text-foreground">Historial del trabajador</h3>
            <div class="flex flex-wrap gap-3">
                @foreach ($historial['por_anio'] as $anioH => $porTipo)
                    <div class="border border-border rounded-lg p-2 text-xs">
                        <p class="font-semibold mb-1">{{ $anioH }}</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach ($porTipo as $codigo => $dias)
                                <span class="px-1.5 py-0.5 rounded text-white" style="background: {{ \App\Services\Planilla\Suspension\PlanillaSuspensionConsulta::color($codigo) }}">{{ $codigo }} · {{ $dias }}d</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="overflow-x-auto">
                <x-table>
                    <x-slot name="thead">
                        <tr>
                            <x-th>Tipo</x-th>
                            <x-th>Desde</x-th>
                            <x-th>Hasta</x-th>
                            <x-th class="text-right">Días</x-th>
                            <x-th>Observación</x-th>
                        </tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @forelse ($historial['rangos'] as $h)
                            <x-tr>
                                <x-td><span class="inline-block w-2.5 h-2.5 rounded-sm mr-1" style="background: {{ $h['color'] }}"></span>{{ $h['codigo'] }} {{ $h['descripcion'] }}</x-td>
                                <x-td>{{ formatear_fecha($h['fecha_inicio']) }}</x-td>
                                <x-td>{{ $h['fecha_fin'] ? formatear_fecha($h['fecha_fin']) : 'sin fin' }}</x-td>
                                <x-td class="text-right">{{ $h['dias'] }}</x-td>
                                <x-td class="text-xs text-muted-foreground">{{ $h['observaciones'] }}</x-td>
                            </x-tr>
                        @empty
                            <x-tr><x-td colspan="5" class="text-center text-muted-foreground">Sin suspensiones registradas.</x-td></x-tr>
                        @endforelse
                    </x-slot>
                </x-table>
            </div>
        </x-card>
    @endif
</div>
