{{-- Desglose de una evaluación de infestación por piso (para comprobar el promedio ponderado). $d: CampoCampania::desgloseEvaluacionInfestacion() --}}
<div class="text-xs space-y-0.5">
    @foreach (['piso_2' => '2° piso', 'piso_3' => '3° piso'] as $clave => $etiqueta)
        @if ($d[$clave]['pencas'])
            <div class="flex justify-between">
                <span>{{ $etiqueta }}: {{ number_format($d[$clave]['promedio'], 0) }} por penca</span>
                <span>{{ $d[$clave]['pencas'] }} penca(s) · {{ number_format($d[$clave]['total']) }} individuos</span>
            </div>
        @endif
    @endforeach
</div>