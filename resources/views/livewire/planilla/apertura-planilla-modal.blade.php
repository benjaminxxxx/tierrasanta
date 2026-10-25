{{-- Apertura y generación de la planilla agraria del mes. Los parámetros se leen de Parámetros de planilla. --}}
@php
    $btn2 = 'inline-flex h-8 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-xs font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $faltan = collect($parametrosMes)->where('falta', true);
@endphp
<x-dialog-modal wire:model.live="showingAperturaPlanilla" maxWidth="3xl">
    <x-slot name="title">
        Planilla agraria de {{ \Carbon\Carbon::create($anio, $mes, 1)->translatedFormat('F Y') }}
    </x-slot>

    <x-slot name="content">
        <div x-data="{ dias: $wire.entangle('dias_laborables') }" class="space-y-6 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">

            {{-- Lo propio del mes --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-input wire:model="dias_laborables" label="Días laborables" x-model.number="dias" type="number" min="1" max="31" />
                </div>
                <div>
                    <x-input x-bind:value="dias ? (dias * 8) : ''" disabled label="Total horas" />
                    <p class="mt-1 text-xs text-pewter">días × 8 horas</p>
                </div>
                <div>
                    <x-label value="Sueldo base del mes" />
                    <div class="mt-1 flex gap-2">
                        <x-input wire:model="remuneracion_basica" type="number" step="0.01" placeholder="Sin calcular" class="flex-1" />
                        <button type="button" wire:click="calcularSueldoBase" class="{{ $btn2 }} h-9" title="RMV del mes / 30 × días del mes">
                            {{ $remuneracion_basica ? 'Recalcular' : 'Calcular' }}
                        </button>
                    </div>
                </div>
            </div>

            @if ($excel)
                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($excel) }}" target="_blank" class="{{ $btn2 }} h-9 w-full justify-center">
                    <i class="fa fa-file-excel"></i> Descargar el último Excel generado
                </a>
            @endif

            {{-- Parámetros del mes (referencia; se editan en su modal) --}}
            <div class="rounded-[10px] border border-mist">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline bg-fog px-4 py-2.5">
                    <div>
                        <p class="text-sm font-semibold text-ink">Parámetros del mes</p>
                        <p class="text-xs text-pewter">Valores vigentes el 1 de {{ \Carbon\Carbon::create($anio, $mes, 1)->translatedFormat('F') }}, tomados de Parámetros de planilla. Haz clic en uno para ver su historial o corregirlo.</p>
                    </div>
                    <a href="{{ route('planilla.parametros') }}" target="_blank" class="{{ $btn2 }}"><i class="fa fa-up-right-from-square"></i> Parámetros</a>
                </div>
                @if ($faltan->isNotEmpty())
                    <p class="border-b border-hairline px-4 py-2 text-sm text-red-700 dark:text-red-400">
                        <i class="fa fa-triangle-exclamation"></i>
                        Faltan valores vigentes en este mes: {{ $faltan->pluck('nombre')->implode(', ') }}. Agrégalos antes de generar.
                    </p>
                @endif
                <div class="grid grid-cols-2 gap-px bg-hairline sm:grid-cols-3">
                    @foreach ($parametrosMes as $p)
                        <button type="button" wire:key="pm-{{ $p['codigo'] }}"
                            @click="$dispatch('abrirParametro', { codigo: '{{ $p['codigo'] }}', mes: {{ $mes }}, anio: {{ $anio }} })"
                            class="bg-paper px-3 py-2 text-left hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-inset focus:ring-carmin/25">
                            <span class="block text-xs text-graphite">{{ $p['nombre'] }}</span>
                            <span class="block text-sm font-medium tabular-nums {{ $p['falta'] ? 'text-red-700 dark:text-red-400' : 'text-ink' }}">
                                {{ $p['falta'] ? 'Falta' : $p['texto'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- AFP del mes --}}
            <div class="rounded-[10px] border border-mist">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-hairline bg-fog px-4 py-2.5">
                    <p class="text-sm font-semibold text-ink">Comisiones y primas AFP del mes</p>
                    <a href="{{ route('planilla.descuentos_afp') }}" target="_blank" class="{{ $btn2 }}"><i class="fa fa-up-right-from-square"></i> Descuentos AFP</a>
                </div>
                @if ($afpFaltan)
                    <p class="border-b border-hairline px-4 py-2 text-sm text-red-700 dark:text-red-400">
                        <i class="fa fa-triangle-exclamation"></i> Faltan valores vigentes de: {{ implode(', ', $afpFaltan) }}.
                    </p>
                @endif
                @if ($afpMes)
                    <div class="overflow-x-auto">
                        <table class="w-full text-[13px]">
                            <thead class="text-left text-xs text-graphite">
                                <tr>
                                    <th class="px-3 py-2 font-medium">AFP</th>
                                    <th class="px-3 py-2 text-right font-medium">Com. flujo</th>
                                    <th class="px-3 py-2 text-right font-medium">Com. saldo</th>
                                    <th class="px-3 py-2 text-right font-medium">Prima seguros</th>
                                    <th class="px-3 py-2 text-right font-medium">Aporte oblig.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($afpMes as $d)
                                    <tr class="border-t border-hairline">
                                        <td class="px-3 py-1.5 font-medium text-ink">{{ $d['referencia'] }}</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($d['comision_flujo'], 2) }} %</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($d['comision_saldo'], 2) }} %</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($d['prima_seguros'], 2) }} %</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($d['aporte_obligatorio'], 2) }} %</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-button variant="secondary" wire:click="$set('showingAperturaPlanilla', false)" wire:loading.attr="disabled">
            Cancelar
        </x-button>
        <x-button wire:click="generarPlanilla" wire:loading.attr="disabled" class="ml-2">
            <i class="fa fa-check"></i> Generar planilla
        </x-button>
    </x-slot>
</x-dialog-modal>
