{{-- Pregunta "¿pasa a la siguiente etapa?" al guardar horas (CampaniaSectorRevision::revisar), para el confirm de SweetAlert. --}}
@php($R = \App\Services\Campania\Sector\CampaniaSectorReglas::class)
<div class="mt-1 space-y-2 text-left text-sm">
    <p class="text-graphite">
        El {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }} se registran labores de una <b>etapa nueva</b> para estos campos.
        Si es correcto, el grupo de hileras pasa a esa etapa desde ese día. Si el código está mal digitado, elige <b>Corregir</b>.
    </p>
    <div class="max-h-80 overflow-auto rounded-[8px] border border-fog">
        <table class="w-full text-xs">
            <tbody>
                @foreach ($avances as $a)
                    <tr class="border-b border-fog last:border-0 align-top">
                        <th class="whitespace-nowrap bg-muted px-2 py-2 text-left font-semibold text-ink">
                            Campo {{ $a['campo'] }}
                            <div class="font-normal text-pewter">{{ $a['campania'] }}</div>
                        </th>
                        <td class="px-2 py-2">
                            <div class="font-semibold text-ink">
                                {{ $a['unico'] ? 'Todo el campo' : "{$a['grupo']} (hileras {$a['hileras']})" }}:
                                <span class="rounded-[6px] px-1.5 py-0.5 text-white" style="background: {{ $R::color($a['actual']) }}">{{ $R::nombre($a['actual']) }}</span>
                                →
                                <span class="rounded-[6px] px-1.5 py-0.5 text-white" style="background: {{ $R::color($a['etapa']) }}">{{ $R::nombre($a['etapa']) }}</span>
                            </div>
                            <div class="mt-1 text-graphite">Por: {{ implode(', ', $a['labores']) }}</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-pewter">Queda en el mapa de etapas de la campaña; si fue otro grupo de hileras, ajústalo ahí.</p>
</div>
