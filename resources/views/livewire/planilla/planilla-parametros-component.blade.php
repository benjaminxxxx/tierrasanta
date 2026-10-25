{{-- Parámetros de planilla: una tarjeta por valor que usa el sistema; clic = historial y nuevo valor (DESIGN.md) --}}
@php
    $mesTexto = fn($f) => $f ? ucfirst(\Carbon\Carbon::parse($f)->translatedFormat('M Y')) : null;
    $tarjeta = 'group flex h-full w-full flex-col rounded-[10px] border border-mist bg-paper p-4 text-left hover:border-graphite/40 hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
@endphp
<div class="space-y-6 font-['Inter',ui-sans-serif,system-ui,sans-serif] tracking-[-0.01em]">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Parámetros de planilla</h1>
            <p class="text-sm text-graphite">Valores vigentes en {{ $mesActual }}. Haz clic en uno para ver su historial o registrar un cambio (con su motivo).</p>
        </div>
        <x-input type="search" wire:model.live.debounce.300ms="buscar" placeholder="Buscar parámetro…" class="w-64" />
    </div>

    @foreach ($grupos as $titulo => $lista)
        @if ($lista->isNotEmpty())
            <section>
                <h2 class="mb-2 text-xs font-medium uppercase tracking-wide text-pewter">{{ $titulo }}</h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($lista as $t)
                        <button type="button" wire:key="t-{{ $t['codigo'] }}" class="{{ $tarjeta }}"
                            @click="$dispatch('abrirParametro', { codigo: '{{ $t['codigo'] }}' })">
                            <span class="flex items-start justify-between gap-2">
                                <span class="text-sm font-medium text-graphite">{{ $t['nombre'] }}</span>
                                @if ($t['bloqueado'])
                                    <i class="fa fa-file-invoice-dollar text-xs text-pewter" title="Ya lo usó una planilla generada: cambiarlo pide confirmar y regenera"></i>
                                @endif
                            </span>
                            <span class="mt-1 text-2xl font-semibold tabular-nums {{ $t['valor'] === null ? 'text-red-700 dark:text-red-400' : 'text-ink' }}">
                                {{ $t['valor'] === null ? 'Sin valor' : $t['texto'] }}
                            </span>
                            <span class="mt-1 text-xs text-pewter">
                                @if ($t['desde']) Desde {{ $mesTexto($t['desde']) }} @else Regístralo para poder generar la planilla @endif
                                @if ($t['proximo']) · cambia en {{ $mesTexto($t['proximo']) }} @endif
                            </span>
                            @if ($t['motivo'])
                                <span class="mt-2 line-clamp-2 text-xs text-graphite">{{ $t['motivo'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    {{-- AFP: tienen su propia página (se pegan de la SBS) --}}
    <section>
        <h2 class="mb-2 text-xs font-medium uppercase tracking-wide text-pewter">Sistema privado de pensiones</h2>
        <a href="{{ route('planilla.descuentos_afp') }}" class="{{ $tarjeta }} max-w-2xl">
            <span class="flex items-start justify-between gap-2">
                <span class="text-sm font-medium text-graphite">Comisiones y primas de AFP</span>
                <span class="text-xs text-pewter">Abrir Descuentos AFP <i class="fa fa-arrow-right"></i></span>
            </span>
            @if ($afp['afp'])
                <span class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-[13px] sm:grid-cols-4">
                    @foreach ($afp['afp'] as $a)
                        <span><span class="font-medium text-ink">{{ $a['referencia'] }}</span>
                            <span class="block tabular-nums text-graphite">{{ number_format($a['comision_flujo'], 2) }} · {{ number_format($a['prima_seguros'], 2) }} · {{ number_format($a['aporte_obligatorio'], 2) }} %</span></span>
                    @endforeach
                </span>
                <span class="mt-1 text-xs text-pewter">Comisión flujo · prima · aporte. Desde {{ $mesTexto($afp['desde']) }}{{ $afp['motivo'] ? ' · ' . $afp['motivo'] : '' }}</span>
            @endif
            @if ($afp['faltan'])
                <span class="mt-2 text-sm text-red-700 dark:text-red-400"><i class="fa fa-triangle-exclamation"></i> Sin valores vigentes: {{ implode(', ', $afp['faltan']) }}</span>
            @endif
        </a>
    </section>

    @if ($otros->isNotEmpty())
        <details class="rounded-[10px] border border-mist bg-paper">
            <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-graphite">Otros valores guardados que el sistema no usa ({{ $otros->count() }})</summary>
            <div class="grid grid-cols-1 gap-3 border-t border-hairline p-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($otros as $t)
                    <button type="button" wire:key="o-{{ $t['codigo'] }}" class="{{ $tarjeta }}" @click="$dispatch('abrirParametro', { codigo: '{{ $t['codigo'] }}' })">
                        <span class="text-sm font-medium text-graphite">{{ $t['codigo'] }}</span>
                        <span class="mt-1 text-lg font-semibold tabular-nums text-ink">{{ $t['valor'] === null ? '—' : rtrim(rtrim(number_format($t['valor'], 4, '.', ''), '0'), '.') }}</span>
                    </button>
                @endforeach
            </div>
        </details>
    @endif

    <livewire:planilla.planilla-parametro-modal-component />
</div>
