{{-- Estado de cosecha de una campaña. $estado viene de CampaniaCosechaConsulta::estados() --}}
@if (!$estado || $estado['estado'] === 'sin_cosecha')
    <span class="text-xs text-muted-foreground">Sin cosecha registrada</span>
@else
    <div class="space-y-1 text-xs">
        <div class="flex flex-wrap gap-1">
            @if ($estado['estado'] === 'cosechando')
                <x-badge color="yellow">Cosechando</x-badge>
            @else
                <x-badge color="blue">Cosechada hace {{ $estado['dias_desde_cosecha'] }} días</x-badge>
            @endif
            @if ($estado['es_mama'])
                <x-badge color="purple">Para mamá</x-badge>
            @endif
        </div>
        <p class="text-muted-foreground">
            {{ formatear_fecha($estado['primera_cosecha']) }}
            @if ($estado['dias_cosecha'] > 1)
                – {{ formatear_fecha($estado['ultima_cosecha']) }}
            @endif
            · {{ $estado['dias_cosecha'] }} {{ $estado['dias_cosecha'] === 1 ? 'día' : 'días' }}
            @if ($estado['kg'] > 0)
                · {{ number_format($estado['kg'], 2) }} kg
            @endif
        </p>
        @if ($estado['recomendar_cierre'] === 'actividades')
            <p class="font-semibold text-red-600 dark:text-red-400">
                Cerrar el {{ formatear_fecha($estado['fecha_cierre_sugerida']) }}:
                {{ $estado['actividad_posterior']['labor'] }} el {{ formatear_fecha($estado['actividad_posterior']['fecha']) }}
                ya es de la siguiente campaña.
            </p>
        @elseif ($estado['recomendar_cierre'] === 'tiempo')
            <p class="font-semibold text-amber-600 dark:text-amber-400">
                Se recomienda cerrar el {{ formatear_fecha($estado['fecha_cierre_sugerida']) }}.
            </p>
        @endif
    </div>
@endif
