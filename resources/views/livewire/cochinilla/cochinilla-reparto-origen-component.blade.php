<div>
    <x-dialog-modal wire:model.live="mostrar" maxWidth="2xl">
        <x-slot name="title">
            @if ($ingreso)
                Lote {{ $ingreso->lote }}: ¿de qué campos es esta cochinilla?
            @endif
        </x-slot>
        <x-slot name="content">
            @if ($ingreso && $opciones)
                @php
                    $total = (float) $ingreso->total_kilos;
                    $yaConfirmado = $ingreso->origen_estado === 'manual' && $ingreso->origenes->isNotEmpty();
                    $factor = \App\Services\Cochinilla\Recuperacion\CochinillaRecuperacionReglas::factorSeco();
                @endphp
                <div x-data="{ kg: $wire.entangle('kg'), total: {{ $total }},
                        get suma() { return Object.values(this.kg).reduce((a, v) => a + (parseFloat(v) || 0), 0); },
                        get diferencia() { return Math.round((this.total - this.suma) * 100) / 100; } }" class="space-y-3">
                    <p class="text-sm text-muted-foreground">
                        {{ number_format($total, 2) }} kg de infestadores recogidos en <b>{{ $ingreso->campo }}</b> el {{ formatear_fecha($ingreso->fecha) }}.
                        @if ($opciones['tanda'])
                            Ese campo se infestó del {{ formatear_fecha($opciones['tanda']['inicio']) }} al {{ formatear_fecha($opciones['tanda']['fin']) }} con mamá de
                            {{ count($opciones['origenes']) }} campo(s). Lo sugerido reparte según los kg de mamá de cada uno; corrígelo si sabes de qué camas
                            salió (en el camino se pierde peso, no siempre en la misma proporción). "Esperado seco" = mamá fresca ÷ {{ $factor }}: si un
                            campo se aleja mucho de lo esperado, puede que se haya marcado mal de dónde venían los infestadores.
                        @endif
                    </p>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-muted-foreground border-b border-border">
                                <th class="text-left py-1">Campo de origen</th>
                                <th class="text-right">Kg de mamá (fresco)</th>
                                <th class="text-right">Esperado seco</th>
                                <th class="text-right">%</th>
                                <th class="text-right">Sugerido</th>
                                <th class="text-right w-36">Kg de este lote</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($opciones['origenes'] as $campo => $o)
                                <tr class="border-b border-border" wire:key="reparto-{{ $campo }}">
                                    <td class="py-1.5">
                                        <span class="font-semibold">{{ $campo }}</span>
                                        <span class="text-xs text-muted-foreground block">{{ $o['campania'] ?? 'campaña no encontrada' }} · infestó {{ formatear_fecha($o['desde']) }}{{ $o['desde'] !== $o['hasta'] ? ' – ' . formatear_fecha($o['hasta']) : '' }}</span>
                                    </td>
                                    <td class="text-right">{{ number_format($o['kg_madres'], 2) }}</td>
                                    <td class="text-right text-muted-foreground">~{{ number_format($o['kg_madres'] / $factor, 2) }}</td>
                                    <td class="text-right">{{ number_format($o['porcentaje'], 1) }} %</td>
                                    <td class="text-right text-muted-foreground">{{ number_format($o['kg_sugerido'], 2) }}</td>
                                    <td class="text-right">
                                        <input type="number" step="0.01" min="0" x-model="kg[@js((string) $campo)]"
                                            class="w-32 h-8 text-right rounded-md border border-input bg-background px-2 text-sm">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td colspan="5" class="text-right py-1.5">Suma</td>
                                <td class="text-right" :class="Math.abs(diferencia) > 0.01 ? 'text-red-600' : 'text-green-700 dark:text-green-400'">
                                    <span x-text="suma.toFixed(2)"></span> / {{ number_format($total, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <p x-show="Math.abs(diferencia) > 0.01" x-cloak class="text-xs text-red-600">
                        Faltan <span x-text="diferencia.toFixed(2)"></span> kg por asignar (o sobran, si es negativo): la suma debe ser igual a los kg del lote.
                    </p>
                    @if ($yaConfirmado)
                        <x-input type="text" wire:model="motivo" label="Motivo del cambio (obligatorio: el reparto ya estaba confirmado)"
                            placeholder="Ej. el campo B9 perdió más peso, sus camas tenían menos cochinilla" class="w-full" />
                    @endif
                    <p class="text-xs text-muted-foreground">La mano de obra de recoger estos infestadores es de {{ $ingreso->campo }}; los kilos van a cada campaña de origen.
                        Queda registrado quién confirmó el reparto (y el motivo, si se cambia) en la auditoría.</p>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <div class="flex gap-2">
                <x-button variant="secondary" wire:click="$set('mostrar', false)">Cancelar</x-button>
                <x-button variant="secondary" wire:click="usarSugerido"><i class="fa fa-magic"></i> Usar lo sugerido</x-button>
                <x-button wire:click="guardar"><i class="fa fa-check"></i> Confirmar reparto</x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
