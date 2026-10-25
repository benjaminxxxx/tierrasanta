{{-- Producción sin bono de una actividad (DESIGN.md). Trabajador opcional; una cantidad por recojo. --}}
@php
    $btn2 = 'inline-flex h-8 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-xs font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $btnPrincipal = 'inline-flex h-9 items-center gap-1.5 rounded-[10px] bg-carmin px-4 text-sm font-semibold text-white hover:bg-carmin-hover focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $input = 'h-8 w-full rounded-[10px] border border-mist bg-paper px-2 text-right text-[13px] tabular-nums text-ink focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
@endphp

<div class="rounded-[10px] border border-mist bg-paper p-4">
    <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
        <div>
            <h3 class="text-base font-semibold text-ink">Producción sin bono</h3>
            <p class="text-xs text-pewter">
                Cantidades de quienes hicieron la labor sin bono (o del día completo). Se suman a la producción del campo.
                Unidad: <span class="font-medium text-ink">{{ $unidad ?? '—' }}</span>. El trabajador es opcional.
            </p>
        </div>
        @if ($puede)
            <div class="flex gap-2">
                <button type="button" wire:click="agregarRecojo" class="{{ $btn2 }}"><i class="fa fa-plus"></i> Recojo</button>
                <button type="button" wire:click="agregarFila" class="{{ $btn2 }}"><i class="fa fa-plus"></i> Fila</button>
            </div>
        @endif
    </div>

    @if (!$unidad)
        <p class="mb-3 rounded-[6px] bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-400/10 dark:text-amber-400">
            <i class="fa fa-triangle-exclamation"></i> Esta labor no tiene unidad de producción: asígnala en Catálogos → Labores para registrar cantidades.
        </p>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full min-w-[40rem] text-[13px]">
            <thead class="bg-fog text-left text-xs text-graphite">
                <tr>
                    <th class="px-2 py-1.5 font-medium">Trabajador (opcional)</th>
                    @for ($r = 1; $r <= $recojos; $r++)
                        <th class="w-28 px-2 py-1.5 text-right font-medium">Recojo {{ $r }}</th>
                    @endfor
                    <th class="px-2 py-1.5 font-medium">Observación</th>
                    <th class="w-8"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $i => $fila)
                    <tr wire:key="sb-{{ $actividadId }}-{{ $i }}" class="border-t border-hairline">
                        <td class="min-w-[16rem] px-2 py-1.5">
                            <x-searchable-select buscar="buscarTrabajadores" wire:model="filas.{{ $i }}.trabajador" :etiqueta="$fila['nombre']"
                                placeholder="Sin trabajador" :disabled="!$puede" />
                        </td>
                        @for ($r = 1; $r <= $recojos; $r++)
                            <td class="px-2 py-1.5">
                                <input type="number" step="0.01" min="0" wire:model="filas.{{ $i }}.recojos.{{ $r }}" class="{{ $input }}" @disabled(!$puede) />
                            </td>
                        @endfor
                        <td class="px-2 py-1.5">
                            <input type="text" wire:model="filas.{{ $i }}.observacion" class="{{ $input }} !text-left" @disabled(!$puede) />
                        </td>
                        <td class="px-2 py-1.5 text-right">
                            @if ($puede)
                                <button type="button" wire:click="quitarFila({{ $i }})" class="text-steel hover:text-red-700" title="Quitar"><i class="fa fa-xmark"></i></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $recojos + 3 }}" class="px-2 py-3 text-xs text-pewter">Sin cantidades sin bono. Usa "Fila" para agregar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($puede)
        <div class="mt-3 flex justify-end">
            <button type="button" wire:click="guardar" class="{{ $btnPrincipal }}"><i class="fa fa-check"></i> Guardar sin bono</button>
        </div>
    @endif
    <x-loading wire:loading wire:target="guardar" />
</div>
