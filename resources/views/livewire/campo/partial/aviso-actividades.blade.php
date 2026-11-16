{{-- Pregunta al guardar horas (CampaniaSectorRevision::revisar), para el confirm de SweetAlert:
     - una siembra pasa a la siguiente etapa (ej. infestación → cosecha);
     - nueva siembra (relleno): labores de siembra cuando el campo ya pasó la siembra (otra fecha de siembra). --}}
@php($R = \App\Services\Campania\Sector\CampaniaSectorReglas::class)
<div class="mt-1 space-y-2 text-left text-sm">
    <p class="text-graphite">
        El {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }} se registran labores que cambian el mapa de etapas de estos campos.
        Si el código está mal digitado, elige <b>Corregir</b>.
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
                            @if (($a['tipo'] ?? '') === 'nueva_siembra')
                                <div class="font-semibold text-ink">
                                    Nueva siembra (relleno):
                                    <span class="rounded-[6px] px-1.5 py-0.5 text-white" style="background: {{ $R::color('siembra') }}">{{ $a['grupo'] }}</span>
                                    sembrada el {{ \Illuminate\Support\Carbon::parse($a['fecha'])->format('d/m/Y') }}
                                </div>
                                <div class="mt-1 text-graphite">Por: {{ implode(', ', $a['labores']) }}</div>
                                <div class="mt-1 text-pewter">Hoy el campo está en: {{ implode('; ', $a['siembras'] ?? []) }}. Cada siembra lleva su propia infestación y cosecha; la campaña se cierra con la última cosecha.</div>
                            @else
                                <div class="font-semibold text-ink">
                                    {{ $a['unico'] ? 'Todo el campo' : $a['grupo'] }}:
                                    <span class="rounded-[6px] px-1.5 py-0.5 text-white" style="background: {{ $R::color($a['actual']) }}">{{ $R::nombre($a['actual']) }}</span>
                                    →
                                    <span class="rounded-[6px] px-1.5 py-0.5 text-white" style="background: {{ $R::color($a['etapa']) }}">{{ $R::nombre($a['etapa']) }}</span>
                                </div>
                                <div class="mt-1 text-graphite">Por: {{ implode(', ', $a['labores']) }}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-pewter">
        Queda en Campañas → Mapa de etapas. Si parte del campo se sembró en otra fecha (relleno) y aún no está en el mapa, agrega esa siembra ahí;
        un replante de pocas plantas no es otra siembra.
    </p>
</div>
