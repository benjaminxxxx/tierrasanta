<x-dialog-modal maxWidth="lg" wire:model="mostrarHorasAcumuladasForm">
    <x-slot name="title">
        {{ $soloConsulta ? 'Horas acumuladas' : 'Registrar Uso de Horas Acumuladas' }}
    </x-slot>

    <x-slot name="content">
        @php
            $hm = fn($m) => intdiv((int) $m, 60) . 'h ' . str_pad((string) ((int) $m % 60), 2, '0', STR_PAD_LEFT) . 'min';
            $fechaRegistro = $resumenRiego ? \Carbon\Carbon::parse($resumenRiego->fecha) : null;
        @endphp
        @if ($soloConsulta)
            {{-- Solo consulta: saldo al inicio de la fecha (lo acumulado y usado antes de ese día) --}}
            <div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif]">
                <div class="flex items-center justify-between rounded-[10px] border border-mist bg-fog/50 px-4 py-3">
                    <div>
                        <p class="text-xs text-pewter">{{ $resumenRiego?->trabajador_nombre }}</p>
                        <p class="text-sm text-graphite">Disponible al {{ $fechaRegistro?->format('d/m/Y') }}</p>
                    </div>
                    <p class="text-2xl font-semibold tabular-nums text-ink">{{ $hm($saldo['disponible'] ?? 0) }}</p>
                </div>
                <p class="text-xs text-pewter">
                    Informativo: lo que tenía para usar ese día (acumulado los días anteriores menos lo ya usado antes de esa fecha).
                    Para usarlas, entra al día en que no trabaja y usa el botón "Usar … acumuladas". Las horas acumuladas se usan en el mismo mes.
                </p>
                @if (!empty($saldo['origenes']))
                    <div class="max-h-72 overflow-y-auto rounded-[10px] border border-mist">
                        <table class="w-full text-[13px]">
                            <thead class="sticky top-0 bg-fog text-xs text-graphite">
                                <tr>
                                    <th class="px-3 py-1.5 text-left font-medium">Acumulado el</th>
                                    <th class="px-3 py-1.5 text-right font-medium">Acumuló</th>
                                    <th class="px-3 py-1.5 text-right font-medium">Disponible</th>
                                    <th class="px-3 py-1.5 text-left font-medium">Usado el</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($saldo['origenes'] as $o)
                                    <tr class="border-t border-hairline">
                                        <td class="px-3 py-1.5 tabular-nums">
                                            {{ \Carbon\Carbon::parse($o['fecha'])->format('d/m/Y') }}
                                            @if ($o['otro_mes'] && $o['disponible'] > 0)
                                                <span class="ml-1 rounded-[6px] bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-400" title="Acumulado en otro mes: debió usarse en ese mes para que el cierre de costos cuadre">otro mes</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-1.5 text-right tabular-nums">{{ $hm($o['acumulado']) }}</td>
                                        <td class="px-3 py-1.5 text-right font-medium tabular-nums {{ $o['disponible'] > 0 ? 'text-green-700 dark:text-green-400' : 'text-pewter' }}">{{ $hm($o['disponible']) }}</td>
                                        <td class="px-3 py-1.5 text-xs text-graphite">
                                            {{ collect($o['usos'])->map(fn($u) => \Carbon\Carbon::parse($u['fecha'])->format('d/m') . ' (' . $hm($u['minutos']) . ')')->implode(', ') ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="rounded-[10px] border border-dashed border-mist p-4 text-center text-sm text-pewter">No tenía horas acumuladas antes de esta fecha.</p>
                @endif
            </div>
        @else
        @php
            // Días anteriores con horas acumuladas (saldo a esta fecha). Los usos de fechas posteriores no restan:
            // al registrar, los usos del regador se reasignan en orden de fecha (RiegoAcumulacionProceso).
            $fechaTxt = $fechaRegistro?->toDateString();
            $origenesUso = collect($saldo['origenes'] ?? [])->filter(fn($o) => $o['disponible'] > 0 || $o['usos'])->values()->map(fn($o) => [
                'fecha' => $o['fecha'],
                'disponible' => $o['disponible'],
                'acumulado' => $o['acumulado'],
                'otro_mes' => $o['otro_mes'],
                'antes' => collect($o['usos'])->where('fecha', '<', $fechaTxt)->values()->all(),
                'despues' => collect($o['usos'])->where('fecha', '>', $fechaTxt)->values()->all(),
            ])->all();
        @endphp
        <div class="space-y-4 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]"
            x-data="{
                inicio: @entangle('acumulado.horaInicio'),
                fin: @entangle('acumulado.horaFin'),
                origenes: @js($origenesUso),
                get minutos() {
                    if (!this.inicio || !this.fin) return 0;
                    const [hi, mi] = this.inicio.split(':').map(Number);
                    const [hf, mf] = this.fin.split(':').map(Number);
                    return Math.max(0, (hf * 60 + mf) - (hi * 60 + mi));
                },
                get total() { return (this.minutos / 60).toFixed(2); },
                // Lo que se tomará de cada día (lo más antiguo primero), según las horas escritas
                get tomas() {
                    let resto = this.minutos;
                    return this.origenes.map(o => { const t = Math.min(o.disponible, Math.max(0, resto)); resto -= t; return t; });
                },
                get faltan() { return Math.max(0, this.minutos - this.origenes.reduce((s, o) => s + o.disponible, 0)); },
                hm(m) { return Math.floor(m / 60) + 'h ' + String(m % 60).padStart(2, '0') + 'min'; },
                dm(f) { const [a, b, c] = f.split('-'); return c + '/' + b; },
            }">

            <div class="flex items-center justify-between rounded-[10px] border border-mist bg-fog/50 px-4 py-3">
                <div>
                    <p class="text-xs text-pewter">{{ $resumenRiego?->trabajador_nombre }} · FDM</p>
                    <p class="text-sm text-graphite">Disponible al {{ $fechaRegistro?->format('d/m/Y') }}</p>
                </div>
                <p class="text-2xl font-semibold tabular-nums text-green-700 dark:text-green-400">{{ $resumenRiego?->disponible_formateado }}</p>
            </div>

            {{-- De qué días salen las horas --}}
            <div>
                <p class="mb-1.5 text-xs font-medium text-graphite"><i class="fa fa-clock-rotate-left text-steel"></i> Se toman de estos días (lo más antiguo primero)</p>
                @if ($origenesUso)
                    <div class="max-h-56 overflow-y-auto rounded-[10px] border border-mist">
                        <table class="w-full text-[13px]">
                            <thead class="sticky top-0 bg-fog text-xs text-graphite">
                                <tr>
                                    <th class="px-3 py-1.5 text-left font-medium">Acumulado el</th>
                                    <th class="px-3 py-1.5 text-right font-medium">Queda</th>
                                    <th class="px-3 py-1.5 text-right font-medium">Se usará</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(o, i) in origenes" :key="o.fecha">
                                    <tr class="border-t border-hairline align-top" :class="tomas[i] > 0 ? 'bg-green-50/60 dark:bg-green-400/5' : ''">
                                        <td class="px-3 py-1.5">
                                            <span class="tabular-nums text-ink" x-text="dm(o.fecha)"></span>
                                            <span x-show="o.otro_mes && o.disponible > 0" class="ml-1 rounded-[6px] bg-amber-50 px-1.5 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-400/10 dark:text-amber-400" title="Acumulado en otro mes: debió usarse en ese mes">otro mes</span>
                                            <template x-if="o.antes.length">
                                                <p class="text-[11px] text-pewter">Usado antes: <span x-text="o.antes.map(u => dm(u.fecha) + ' (' + hm(u.minutos) + ')').join(', ')"></span></p>
                                            </template>
                                            <template x-if="o.despues.length">
                                                <p class="text-[11px] text-amber-700 dark:text-amber-400">
                                                    Ya lo tomó el <span x-text="o.despues.map(u => dm(u.fecha) + ' (' + hm(u.minutos) + ')').join(', ')"></span>;
                                                    si se usa hoy, ese día pasa a horas posteriores.
                                                </p>
                                            </template>
                                        </td>
                                        <td class="px-3 py-1.5 text-right tabular-nums" :class="o.disponible > 0 ? 'text-ink' : 'text-pewter'" x-text="hm(o.disponible)"></td>
                                        <td class="px-3 py-1.5 text-right font-medium tabular-nums" :class="tomas[i] > 0 ? 'text-green-700 dark:text-green-400' : 'text-pewter'" x-text="tomas[i] > 0 ? hm(tomas[i]) : '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p x-show="faltan > 0" x-cloak class="mt-1.5 text-xs text-red-700 dark:text-red-400">
                        <i class="fa fa-triangle-exclamation"></i> Faltan <span x-text="hm(faltan)"></span>: no tenía tantas horas acumuladas antes de este día.
                    </p>
                @else
                    <p class="rounded-[10px] border border-dashed border-mist p-4 text-center text-sm text-pewter">No tiene horas acumuladas antes de este día.</p>
                @endif
            </div>

            {{-- Horas a usar --}}
            <div class="rounded-[10px] border border-mist p-4">
                <p class="mb-3 text-xs font-medium text-graphite">Horas a utilizar</p>
                <div class="grid grid-cols-3 items-start gap-3">
                    <x-input type="time" label="Hora de inicio" x-model="inicio" class="w-full" />
                    <x-input type="time" label="Hora final" x-model="fin" class="w-full" />
                    <div>
                        <x-label class="text-sm">Total</x-label>
                        <div class="mt-1 rounded-[10px] border border-mist bg-fog px-3 py-2 text-center text-lg font-semibold tabular-nums text-ink" x-text="total"></div>
                    </div>
                </div>
                @error('acumulado.horaFin')
                    <x-warning class="mt-3">{{ $message }}</x-warning>
                @enderror
            </div>
        </div>
        @endif
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('mostrarHorasAcumuladasForm', false)"
            wire:loading.attr="disabled">
            Cerrar
        </x-button>
        @unless ($soloConsulta)
            <x-button wire:click="registrarUsoHorasAcumuladas" wire:loading.attr="disabled">
                <i class="fa fa-save"></i> Registrar Uso de Horas Acumuladas
            </x-button>
        @endunless
    </x-slot>
</x-dialog-modal>