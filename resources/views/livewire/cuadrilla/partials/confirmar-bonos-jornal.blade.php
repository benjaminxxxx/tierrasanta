{{--
    Confirmación al cambiar un jornal: bonos "respeta el jornal" que cambian (DESIGN.md).
    Variables: $bonosAfectados (CuadrillaBonoProceso::afectados), $accionConfirmar, $accionCancelar.
--}}
@php
    $pagados = collect($bonosAfectados)->where('pagado', true)->count();
    $f = fn($d) => \Carbon\Carbon::parse($d)->format('d/m');
@endphp
<x-dialog-modal wire:model="mostrarConfirmacionBonos" maxWidth="3xl">
    <x-slot name="title">El cambio de jornal cambia {{ count($bonosAfectados) }} bono(s)</x-slot>
    <x-slot name="content">
        <p class="text-sm text-graphite">
            Estos cuadrilleros están en un método destajo que <b>respeta el jornal</b>: su bono es lo que el destajo supera al jornal.
            Si cambia el jornal, cambia el bono. Revisa antes de guardar.
        </p>
        @if ($pagados)
            <p class="mt-2 rounded-[6px] bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                <i class="fa fa-lock"></i> {{ $pagados }} bono(s) ya están pagados: no se cambian. Si hace falta, corrígelos en el desglose de pago.
            </p>
        @endif
        <div class="mt-3 max-h-80 overflow-y-auto rounded-[10px] border border-mist">
            <table class="w-full text-[13px]">
                <thead class="sticky top-0 bg-fog text-left text-xs text-graphite">
                    <tr>
                        <th class="px-3 py-1.5 font-medium">Día</th>
                        <th class="px-3 py-1.5 font-medium">Cuadrillero</th>
                        <th class="px-3 py-1.5 font-medium">Labor</th>
                        <th class="px-3 py-1.5 text-right font-medium">Horas</th>
                        <th class="px-3 py-1.5 text-right font-medium">Cantidad</th>
                        <th class="px-3 py-1.5 text-right font-medium">Jornal</th>
                        <th class="px-3 py-1.5 text-right font-medium">Bono antes</th>
                        <th class="px-3 py-1.5 text-right font-medium">Bono nuevo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bonosAfectados as $b)
                        <tr class="border-t border-hairline {{ $b['pagado'] ? 'text-pewter' : '' }}">
                            <td class="px-3 py-1.5 tabular-nums">{{ $f($b['fecha']) }}</td>
                            <td class="px-3 py-1.5">{{ $b['cuadrillero'] }}</td>
                            <td class="px-3 py-1.5 text-xs">{{ $b['campo'] }} · {{ $b['labor'] }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($b['horas'], 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($b['cantidad'], 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($b['jornal'], 2) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ number_format($b['antes'], 2) }}</td>
                            <td class="px-3 py-1.5 text-right font-semibold tabular-nums">
                                @if ($b['pagado'])
                                    <span class="font-normal"><i class="fa fa-lock"></i> pagado</span>
                                @else
                                    {{ number_format($b['despues'], 2) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-slot>
    <x-slot name="footer">
        <x-button variant="secondary" wire:click="{{ $accionCancelar }}">Cancelar (no guardar)</x-button>
        <x-button wire:click="{{ $accionConfirmar }}" class="ml-2"><i class="fa fa-check"></i> Guardar jornal y actualizar bonos</x-button>
    </x-slot>
</x-dialog-modal>
