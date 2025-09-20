<div class="flex justify-end mt-5">
    <div>
        <x-flex>
            <x-h3>
                Cuadro resumen
            </x-h3>
            <x-button wire:click="recalcularResumen" size="xs" variant="success" >
                <i class="fa fa-sync"></i> Recalcular resumen
            </x-button>
        </x-flex>
        <x-table class="mt-5">
            <x-slot name="thead">
                <x-tr>
                    <x-th class="text-right">Descripción</x-th>
                    <x-th>Acumulación actual</x-th>
                    <x-th>Condición</x-th>
                    <x-th class="text-right">Fecha</x-th>
                    <x-th class="text-right">Recibo</x-th>
                    <x-th class="text-right">Deuda acumulada</x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
            </x-slot>
        </x-table>
    </div>
</div>