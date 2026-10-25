{{-- Estado de cosecha de una campaña (DESIGN.md: chips de estado). $estado viene de CampaniaCosechaConsulta::estados() --}}
@php $chip = 'rounded-[6px] px-1.5 py-0.5 text-[11px] font-medium'; @endphp
@if (!$estado || $estado['estado'] === 'sin_cosecha')
    <span class="text-xs text-pewter">Sin cosecha registrada</span>
@else
    <div class="space-y-1 text-xs">
        <div class="flex flex-wrap gap-1">
            @if ($estado['estado'] === 'cosechando')
                <span class="{{ $chip }} bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400"><i class="fa fa-basket-shopping"></i> Cosechando</span>
            @else
                <span class="{{ $chip }} bg-blue-50 text-blue-700 dark:bg-blue-400/10 dark:text-blue-400">Cosechada hace {{ $estado['dias_desde_cosecha'] }} días</span>
            @endif
            @if ($estado['es_mama'])
                <span class="{{ $chip }} border border-mist text-graphite">Para mamá</span>
            @endif
        </div>
        <p class="tabular-nums text-graphite">
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
            <p class="font-medium text-red-700 dark:text-red-400">
                <i class="fa fa-flag-checkered"></i> Cerrar el {{ formatear_fecha($estado['fecha_cierre_sugerida']) }}:
                {{ $estado['actividad_posterior']['labor'] }} el {{ formatear_fecha($estado['actividad_posterior']['fecha']) }}
                ya es de la siguiente campaña.
            </p>
        @elseif ($estado['recomendar_cierre'] === 'tiempo')
            <p class="font-medium text-amber-700 dark:text-amber-400">
                <i class="fa fa-clock"></i> Se recomienda cerrar el {{ formatear_fecha($estado['fecha_cierre_sugerida']) }}.
            </p>
        @endif
    </div>
@endif
