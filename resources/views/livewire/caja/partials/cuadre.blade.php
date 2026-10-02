{{-- Cuadre del mes entre la caja de movimientos y la caja de oficina. $cuadre: CajaOficinaConsulta::cuadre() --}}
@php $n = fn($v) => number_format((float) $v, 2); @endphp
<x-card class="space-y-2">
    <div>
        <h3 class="font-semibold text-foreground">Cuadre de {{ $nombreMes }} entre las dos cajas</h3>
        <p class="text-xs text-muted-foreground">La caja de movimientos tiene además el dinero que no está en la oficina (campo, naranja, cuadrillas…). Restado eso, debe quedar lo mismo que en la caja de oficina.</p>
    </div>
    <table class="text-sm w-full max-w-xl">
        <tbody>
            <tr>
                <td class="py-1">Disponible en la caja de movimientos</td>
                <td class="py-1 text-right tabular-nums">{{ $n($cuadre['movimientos']) }}</td>
            </tr>
            <tr>
                <td class="py-1">
                    − Saldos en otras fuentes
                    <span class="block text-xs text-muted-foreground">
                        @if ($cuadre['arqueo_fecha'])
                            Arqueo del {{ $cuadre['arqueo_fecha'] }}:
                            {{ collect($cuadre['fuentes'])->map(fn($f) => $f['fuente'] . ' ' . $n($f['monto']))->implode(' · ') ?: 'sin otras fuentes' }}
                        @else
                            Sin arqueo en el mes: se toma 0. Registra el arqueo en la caja de movimientos.
                        @endif
                    </span>
                </td>
                <td class="py-1 text-right tabular-nums align-top">{{ $n($cuadre['otras_fuentes']) }}</td>
            </tr>
            <tr class="border-t border-border">
                <td class="py-1">= Debe haber en la oficina</td>
                <td class="py-1 text-right tabular-nums">{{ $n($cuadre['esperado']) }}</td>
            </tr>
            <tr>
                <td class="py-1">Disponible en la caja de oficina</td>
                <td class="py-1 text-right tabular-nums">{{ $n($cuadre['oficina']) }}</td>
            </tr>
            <tr class="border-t border-border font-semibold {{ $cuadre['cuadra'] ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                <td class="py-1">Diferencia</td>
                <td class="py-1 text-right tabular-nums">{{ $cuadre['cuadra'] ? 'Cuadra' : $n($cuadre['diferencia']) }}</td>
            </tr>
        </tbody>
    </table>
    @if ($cuadre['por_enviar'] || $cuadre['envios_pendientes'])
        <p class="text-xs text-amber-700 dark:text-amber-400">
            <i class="fa fa-clock"></i> Falta: {{ $cuadre['por_enviar'] }} cambio(s) del mes sin enviar desde la oficina y {{ $cuadre['envios_pendientes'] }} envío(s) sin anexar. El mes no se puede cerrar así.
        </p>
    @endif
</x-card>
