<div>
    <x-card class="mt-3">
        <x-spacing>
            <div class="mb-3 col-span-2">
                <x-flex class="justify-end w-full mb-5">
                    <x-toggle-switch :checked="$verCostoNegro" label="Ver Costo Negro" wire:model.live="verCostoNegro" />
                </x-flex>
                <x-table>
                    <x-slot name="thead">
                        <x-tr>
                            <x-th class="text-center" rowspan="2">N°</x-th>
                            <x-th class="text-center" rowspan="2">Año</x-th>
                            <x-th class="text-center" rowspan="2">Mes</x-th>
                            <x-th class="text-center bg-yellow-100 border-yellow-800" colspan="5">Costo Fijo</x-th>
                            <x-th class="text-center bg-blue-100 border-blue-800" colspan="2">Costo Operativo</x-th>
                            <x-th class="text-center">-</x-th>
                        </x-tr>
                        <x-tr>

                            <x-th class="text-center bg-yellow-100 border-yellow-800">Costo Administrativo</x-th>
                            <x-th class="text-center bg-yellow-100 border-yellow-800">Costo Financiero</x-th>
                            <x-th class="text-center bg-yellow-100 border-yellow-800">Gastos Oficina</x-th>
                            <x-th class="text-center bg-yellow-100 border-yellow-800">Depreciaciones</x-th>
                            <x-th class="text-center bg-yellow-100 border-yellow-800">Costo Terreno</x-th>
                            <x-th class="text-center bg-blue-100 border-blue-800">Servicios Fundo</x-th>
                            <x-th class="text-center bg-blue-100 border-blue-800">Mano de Obra Indirecta</x-th>
                            <x-th class="text-center">-</x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @foreach ($costos as $costo)
                            <x-tr>
                                @if ($verCostoNegro)
                                    <x-td>{{ $costo->fijo_administrativo_negro }}</x-td>
                                @else
                                    <x-td>{{ $costo->fijo_administrativo_blanco }}</x-td>
                                @endif

                            </x-tr>
                        @endforeach
                    </x-slot>
                </x-table>
                <x-flex class="justify-end">
                    <x-secondary-button type="button" wire:click="agregarCosto" class="my-4">
                        <i class="fa fa-plus"></i> Agregar Costo
                    </x-secondary-button>
                </x-flex>
            </div>
        </x-spacing>
    </x-card>
</div>
