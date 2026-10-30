{{--
    Costo personalizado por cuadrillero y día (DESIGN.md). Vacío = jornal del grupo de ese día (se muestra como ayuda).
    El personalizado manda sobre el costo del grupo: si cambia el costo del grupo, quien tiene personalizado lo conserva.
--}}
@php
    $btn2 = 'inline-flex h-8 items-center gap-1.5 rounded-[10px] border border-mist bg-paper px-3 text-xs font-medium text-ink hover:bg-fog focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $input = 'h-8 w-full min-w-[4.5rem] rounded-[10px] border border-mist bg-paper px-2 text-right text-[13px] tabular-nums text-ink placeholder:text-steel focus:border-carmin focus:outline-none focus:ring-[3px] focus:ring-carmin/25';
    $hayPersonalizados = collect($cuadrillerosCostosPersonalizados)->contains(fn($c) => collect($c['costos'] ?? [])->contains(fn($v) => $v !== null && $v !== ''));
@endphp
<x-dialog-modal wire:model.live="mostrarFormularioCostoHora" maxWidth="full">
    <x-slot name="title">Costo personalizado por cuadrillero</x-slot>

    <x-slot name="content">
        <div class="mb-3 flex flex-wrap items-start justify-between gap-3">
            <p class="max-w-3xl text-sm text-graphite">
                El costo personalizado <b class="text-ink">reemplaza al costo del grupo</b> ese día: si luego cambias el costo del grupo,
                quien tiene personalizado lo conserva. Deja la celda vacía para cobrar el jornal del grupo (se ve en gris).
            </p>
            @if ($hayPersonalizados)
                <button type="button" class="{{ $btn2 }}" wire:click="vaciarCostosPersonalizados">
                    <i class="fa fa-eraser"></i> Quitar personalizados
                </button>
            @endif
        </div>

        <div class="overflow-x-auto rounded-[10px] border border-mist">
            <table class="w-full text-[13px]">
                <thead class="bg-fog text-xs text-graphite">
                    <tr>
                        <th class="px-3 py-2 text-right font-medium">N°</th>
                        <th class="px-3 py-2 text-left font-medium">Grupo</th>
                        <th class="px-3 py-2 text-left font-medium">Cuadrillero</th>
                        @foreach ($diasSemana ?? [] as $diaSemana)
                            @php $d = \Carbon\Carbon::parse($diaSemana); @endphp
                            <th class="px-2 py-2 text-center font-medium">{{ $d->isoFormat('ddd') }} {{ $d->format('d') }}</th>
                        @endforeach
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cuadrillerosCostosPersonalizados ?? [] as $claveCuadrilla => $cuadrillero)
                        @php $filaConPersonalizado = collect($cuadrillero['costos'])->contains(fn($v) => $v !== null && $v !== ''); @endphp
                        <tr wire:key="tr-{{ $claveCuadrilla }}" class="border-t border-hairline">
                            <td class="px-3 py-1.5 text-right tabular-nums text-pewter">{{ $loop->iteration }}</td>
                            <td class="px-3 py-1.5 text-graphite">{{ $cuadrillero['grupo_codigo'] ?? '-' }}</td>
                            <td class="px-3 py-1.5 font-medium text-ink">{{ $cuadrillero['cuadrillero_nombres'] ?? '-' }}</td>
                            @foreach ($cuadrillero['costos'] as $idxDia => $costo)
                                @php $grupo = $cuadrillero['jornal_grupo'][$idxDia] ?? null; @endphp
                                <td class="px-2 py-1.5">
                                    <input type="number" step="0.01" min="0" class="{{ $input }}"
                                        wire:key="costo-{{ $claveCuadrilla }}-{{ $idxDia }}"
                                        wire:model="cuadrillerosCostosPersonalizados.{{ $claveCuadrilla }}.costos.{{ $idxDia }}"
                                        placeholder="{{ $grupo !== null ? number_format($grupo, 2) : '—' }}"
                                        title="{{ $grupo !== null ? 'Jornal del grupo: S/ ' . number_format($grupo, 2) : 'El grupo no tiene jornal este día' }}" />
                                </td>
                            @endforeach
                            <td class="px-2 py-1.5 text-right">
                                @if ($filaConPersonalizado)
                                    <button type="button" class="text-steel hover:text-ink" title="Vaciar esta fila (usar el jornal del grupo)"
                                        wire:click="vaciarCostosPersonalizados('{{ $claveCuadrilla }}')">
                                        <i class="fa fa-eraser"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-xs text-pewter">Los cambios se aplican al pulsar "Registrar costo".</p>
    </x-slot>

    <x-slot name="footer">
        <x-button wire:click="$set('mostrarFormularioCostoHora', false)" variant="secondary" wire:loading.attr="disabled">
            Cerrar
        </x-button>
        <x-button wire:click="registrarCostoPersonalizado" wire:loading.attr="disabled" class="ml-2">
            <i class="fa fa-save"></i> Registrar costo
        </x-button>
    </x-slot>
</x-dialog-modal>
