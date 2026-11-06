{{-- Modal "Ver detalle de las horas" (DESIGN.md): un tab por día; por cuadrillero sus tramos (horario · campo · labor) y bonos; leyenda de labores al pie --}}
@php
    $dias = $detalleHoras['dias'] ?? [];
    $leyenda = $detalleHoras['leyenda'] ?? [];
    $chip = 'inline-flex items-center gap-1 rounded-[6px] px-1.5 py-0.5 text-[11px] font-medium';
    $nombresDia = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
    $horas = fn($h) => rtrim(rtrim(number_format((float) $h, 2), '0'), '.');
@endphp
<x-dialog-modal wire:model="mostrarDetalleHoras" maxWidth="4xl">
    <x-slot name="title">Detalle de las horas</x-slot>
    <x-slot name="content">
        @if ($dias)
            <div x-data="{ tab: 0 }" wire:key="detalle-horas-{{ md5(json_encode(array_column($dias, 'fecha'))) }}" class="space-y-3 text-sm">
                @if (count($dias) > 1)
                    <div class="flex flex-wrap gap-1 border-b border-hairline pb-2">
                        @foreach ($dias as $i => $dia)
                            @php $f = \Illuminate\Support\Carbon::parse($dia['fecha']); @endphp
                            <button type="button" @click="tab = {{ $i }}"
                                :class="tab === {{ $i }} ? 'bg-carmin text-white border-carmin' : 'bg-paper text-ink border-mist hover:bg-fog'"
                                class="rounded-[10px] border px-3 py-1.5 text-left leading-tight focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
                                <span class="block text-xs font-semibold capitalize">{{ $nombresDia[$f->dayOfWeek] }} {{ $f->format('d/m') }}</span>
                                <span class="block text-[11px] opacity-80">{{ $horas($dia['horas']) }} h</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                @foreach ($dias as $i => $dia)
                    @php $f = \Illuminate\Support\Carbon::parse($dia['fecha']); @endphp
                    <div x-show="tab === {{ $i }}" @if ($i > 0) x-cloak @endif>
                        <p class="mb-2 text-xs text-graphite">
                            <span class="font-medium capitalize text-ink">{{ $nombresDia[$f->dayOfWeek] }} {{ $f->format('d/m/Y') }}</span>
                            · {{ count($dia['filas']) }} cuadrillero(s) · {{ $horas($dia['horas']) }} h
                        </p>
                        <div class="max-h-[55vh] divide-y divide-hairline overflow-y-auto rounded-[10px] border border-mist">
                            @foreach ($dia['filas'] as $fila)
                                <div class="flex flex-col gap-1.5 px-3 py-2 sm:flex-row sm:items-start sm:gap-3">
                                    {{-- Cuadrillero y totales --}}
                                    <div class="sm:w-60 sm:shrink-0">
                                        <p class="flex items-center gap-1.5 font-medium text-ink">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full border border-mist" style="background: {{ $fila['color'] ?: '#7b7b7b' }}"></span>
                                            <span class="truncate">{{ $fila['nombres'] }}</span>
                                        </p>
                                        <p class="mt-0.5 text-xs text-graphite">
                                            {{ $fila['grupo'] }}
                                            @if ($fila['registrado'])
                                                · <span class="font-semibold text-ink tabular-nums">{{ $horas($fila['horas']) }} h</span>
                                                · S/ {{ number_format($fila['jornal'], 2) }}
                                                @if ($fila['bono'] != 0)
                                                    · bono S/ {{ number_format($fila['bono'], 2) }}
                                                @endif
                                            @endif
                                        </p>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @if ($fila['personalizado'])
                                                <span class="{{ $chip }} bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-400" title="Costo personalizado del día">personalizado</span>
                                            @endif
                                            @if ($fila['pagado'])
                                                <span class="{{ $chip }} bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400">pagado</span>
                                            @endif
                                            @if ($fila['registrado'] && abs($fila['horas'] - $fila['horas_tramos']) > 0.01)
                                                <span class="{{ $chip }} bg-red-50 text-red-700 dark:bg-red-400/10 dark:text-red-400"
                                                    title="El total del día no coincide con la suma de los tramos del registro diario">
                                                    tramos suman {{ $horas($fila['horas_tramos']) }} h
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Tramos y bonos --}}
                                    <div class="min-w-0 flex-1">
                                        @if (!$fila['registrado'])
                                            <p class="text-xs text-pewter">Sin registro este día.</p>
                                        @elseif (!$fila['tramos'])
                                            <p class="text-xs text-pewter">Sin detalle de horas (campo y labor) en el registro diario.</p>
                                        @else
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($fila['tramos'] as $t)
                                                    <span class="inline-flex items-center gap-1.5 rounded-[6px] border border-mist bg-fog px-2 py-1 text-xs">
                                                        <span class="tabular-nums text-graphite">{{ $t['inicio'] }}–{{ $t['fin'] }}</span>
                                                        <span class="font-semibold tabular-nums text-ink">{{ $horas($t['horas']) }} h</span>
                                                        <span class="text-graphite">campo <span class="font-medium text-ink">{{ $t['campo'] }}</span></span>
                                                        <span class="rounded-[4px] bg-paper px-1 font-semibold text-carmin">{{ $t['codigo'] ?? '—' }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if ($fila['bonos'])
                                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                                @foreach ($fila['bonos'] as $b)
                                                    <span class="{{ $chip }} bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400">
                                                        bono {{ $b['codigo'] }} · campo {{ $b['campo'] }} · S/ {{ number_format($b['bono'], 2) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if ($leyenda)
                    <div class="rounded-[10px] border border-mist px-3 py-2">
                        <p class="mb-1.5 text-xs font-medium text-graphite">Labores</p>
                        <div class="grid grid-cols-1 gap-x-4 gap-y-1 text-xs sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($leyenda as $l)
                                <p class="flex items-baseline gap-2">
                                    <span class="w-9 shrink-0 rounded-[4px] bg-fog px-1 text-center font-semibold text-carmin">{{ $l['codigo'] }}</span>
                                    <span class="text-ink">{{ $l['labor'] }}</span>
                                </p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <p class="text-sm text-pewter">No hay cuadrilleros o días seleccionados.</p>
        @endif
    </x-slot>
    <x-slot name="footer">
        <button type="button" wire:click="$set('mostrarDetalleHoras', false)"
            class="inline-flex h-9 items-center rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
            Cerrar
        </button>
    </x-slot>
</x-dialog-modal>
