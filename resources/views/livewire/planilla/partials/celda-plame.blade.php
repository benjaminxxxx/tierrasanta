{{-- Celda de un concepto del PLAME: resaltada si tiene ajuste manual (Ajustes PLAME), con lo calculado en el título. $p: PlanMensualPersonal, $codigo --}}
@php
    $ajuste = $p->ajustePlame($codigo);
    $valor = $p->{\App\Services\Planilla\Plame\PlanillaPlameReglas::columna($codigo)};
@endphp
@if ($ajuste)
    <x-td class="text-center bg-amber-100 dark:bg-amber-900/30"
        title="Monto personalizado{{ $ajuste['motivo'] ?? null ? ' (' . $ajuste['motivo'] . ')' : '' }}. Calculado por el sistema: S/ {{ number_format((float) $p->calculadoPlame($codigo), 2) }}">
        {{ fmt($valor) }} <i class="fa fa-pen text-[10px] text-amber-700"></i>
    </x-td>
@else
    <x-td class="text-center">{{ fmt($valor) }}</x-td>
@endif
