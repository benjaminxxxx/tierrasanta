{{-- Reemplazar cuadrillero en la semana (DESIGN.md): muestra todo lo que pasa al nuevo antes de confirmar --}}
@php
    $r = $resumenReemplazo;
    $horas = fn($h) => rtrim(rtrim(number_format((float) $h, 2), '0'), '.');
@endphp
<x-dialog-modal wire:model.live="mostrarReemplazarCuadrilleroForm">
    <x-slot name="title">Reemplazar cuadrillero</x-slot>

    <x-slot name="content">
        <div class="space-y-4 text-sm">
            @if ($cuadrilleroPorReemplazar)
                <div class="rounded-[10px] border border-mist bg-fog px-3 py-2">
                    <p class="text-xs text-graphite">Cuadrillero a reemplazar en esta semana</p>
                    <p class="font-medium text-ink">{{ $cuadrilleroPorReemplazar->nombres }}</p>
                </div>
            @endif

            <div>
                <x-label value="Reemplazar por" />
                <x-searchable-select wire:model="cuadrilleroARemplazarSeleccionado" buscar="getCuadrillero" />
            </div>

            @if ($r)
                <div class="rounded-[10px] border border-mist px-3 py-2">
                    <p class="mb-1 font-medium text-ink">Pasará al nuevo cuadrillero:</p>
                    <ul class="list-disc space-y-0.5 pl-5 text-graphite">
                        <li>{{ $r['registros'] }} día(s) de la semana con {{ $horas($r['horas']) }} h,
                            {{ $r['detalle'] }} tramo(s) de campo/labor y {{ $r['bonos'] }} bono(s).</li>
                        @if ($r['produccion'])
                            <li>{{ $r['produccion'] }} registro(s) de producción (kilos) de esos días.</li>
                        @endif
                        <li>Su nombre en la BDD de costos de esos días (los montos no cambian).</li>
                    </ul>
                    @if ($r['riego'])
                        <label class="mt-2 flex items-start gap-2 rounded-[6px] bg-fog px-2 py-1.5">
                            <input type="checkbox" wire:model="moverRiego" class="mt-0.5 rounded border-mist text-carmin focus:ring-carmin/25" />
                            <span class="text-ink">
                                Pasar también su riego ({{ $r['riego'] }} día(s): {{ implode(', ', $r['riego_dias']) }})
                                <span class="block text-xs text-graphite">El riego de un cuadrillero se paga con su jornal de cuadrilla: si se queda con el anterior, que ya no tendrá registro, no se le paga a nadie. Desmárcalo solo si el riego sí lo hizo el anterior.</span>
                            </span>
                        </label>
                    @endif
                    @if ($r['pagados'])
                        <p class="mt-2 rounded-[6px] bg-red-50 px-2 py-1.5 text-red-700 dark:bg-red-400/10 dark:text-red-400">
                            <i class="fa fa-circle-exclamation"></i> Tiene {{ $r['pagados'] }} día(s) pagados en un desglose: no se puede reemplazar.
                        </p>
                    @endif
                </div>
            @endif
            <p class="text-xs text-pewter">Úsalo cuando se anotó a la persona equivocada. Queda en la auditoría.</p>
        </div>
    </x-slot>

    <x-slot name="footer">
        <button type="button" wire:click="$set('mostrarReemplazarCuadrilleroForm', false)"
            class="inline-flex h-9 items-center rounded-[10px] border border-mist bg-paper px-3 text-sm font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25">
            Cancelar
        </button>
        <button type="button" wire:click="confirmarReemplazo" wire:loading.attr="disabled" @disabled(($r['pagados'] ?? 0) > 0)
            class="ml-2 inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25 disabled:cursor-not-allowed disabled:opacity-50">
            <i class="fa fa-right-left"></i> Reemplazar
        </button>
    </x-slot>
</x-dialog-modal>
