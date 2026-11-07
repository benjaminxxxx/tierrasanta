{{-- Pregunta "¿es correcto?" de actividades por campo (CampoActividadProceso::revisar), para el confirm de SweetAlert. --}}
<div class="mt-1 space-y-2 text-left text-sm">
    <p class="text-graphite">
        El {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }} estos campos tienen <b>más de una actividad</b>.
        Revisa los códigos: si alguno está mal digitado, elige <b>Corregir</b>. Lo marcado <span class="font-semibold text-amber-700 dark:text-amber-400">NUEVO</span> aún no fue aceptado.
    </p>
    <div class="max-h-80 overflow-auto rounded-[8px] border border-fog">
        <table class="w-full text-xs">
            <tbody>
                @foreach ($campos as $c)
                    <tr class="border-b border-fog last:border-0 align-top">
                        <th class="whitespace-nowrap bg-muted px-2 py-2 text-left font-semibold text-ink">Campo {{ $c['campo'] }}</th>
                        @foreach ($c['codigos'] as $cod)
                            <td class="min-w-[130px] px-2 py-2 {{ $cod['nuevo'] ? 'bg-amber-50 dark:bg-amber-400/10' : '' }}">
                                <div class="font-semibold text-ink">
                                    {{ $cod['codigo'] }}
                                    @if ($cod['nuevo'])
                                        <span class="ml-1 rounded-[6px] bg-amber-100 px-1 text-[10px] font-semibold text-amber-700 dark:bg-amber-400/20 dark:text-amber-400">NUEVO</span>
                                    @endif
                                </div>
                                <div class="text-graphite">{{ \Illuminate\Support\Str::limit($cod['labor'], 40) }}</div>
                                <div class="mt-0.5 text-pewter">
                                    {{ $cod['planilla'] + $cod['cuadrilla'] }} trab.
                                    @if ($cod['planilla'] && $cod['cuadrilla'])
                                        ({{ $cod['planilla'] }} planilla, {{ $cod['cuadrilla'] }} cuadrilla)
                                    @elseif ($cod['cuadrilla'])
                                        (cuadrilla)
                                    @else
                                        (planilla)
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-pewter">Al aceptar, estos códigos quedan como correctos para ese campo y día; solo se volverá a preguntar si aparece otro.</p>
</div>
