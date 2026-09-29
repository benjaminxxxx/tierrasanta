<x-card>
    <x-table>
        <x-slot name="thead">
            <x-tr>
                <x-th class="w-48">N° de documento</x-th>
                <x-th>Descripción</x-th>
                <x-th class="text-right w-44">Monto</x-th>
            </x-tr>
        </x-slot>

        <x-slot name="tbody">
            @forelse($detalles as $i => $detalle)
                <x-tr wire:key="detalle-gasto-{{ $i }}">
                    <x-td compact>
                        <x-input type="text" wire:model="detalles.{{ $i }}.nro_documento"
                            placeholder="Opcional..." />
                    </x-td>

                    <x-td compact>
                        <x-input type="text" wire:model="detalles.{{ $i }}.descripcion" />
                    </x-td>

                    <x-td compact class="text-right font-bold text-base text-primary">
                        S/ {{ number_format($detalle['monto'], 2) }}
                    </x-td>
                </x-tr>
            @empty
                <x-tr>
                    <x-td colspan="3" class="text-center text-base-400">
                        No hay gastos pendientes en este rango.
                    </x-td>
                </x-tr>
            @endforelse
        </x-slot>

        <x-slot name="tfoot">
            <x-tr>
                <x-td colspan="2" class="text-right font-bold text-base">
                    TOTALIZADO:
                </x-td>

                <x-td class="text-right font-bold text-base text-primary">
                    S/ {{ number_format(collect($detalles)->sum('monto'), 2) }}
                </x-td>
            </x-tr>
        </x-slot>
    </x-table>

    <x-flex class="justify-end mt-4">
        <x-button wire:click="confirmarRegistroPago" :disabled="empty($detalles)">
            <i class="fa fa-check"></i> Confirmar registro de pago
        </x-button>
    </x-flex>
</x-card>
