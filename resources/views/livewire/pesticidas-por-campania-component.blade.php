<div>
    <x-flex class="!items-start w-full">
        <div class="md:w-[35rem]">
            <x-card>
                <x-spacing>
                    <x-h3>Resumen de pesticidas</x-h3>

                    @if ($resumenSalidas)
                        @foreach ($resumenSalidas as $categoria => $productos)
                            <h2 class="text-xl font-bold mt-8 uppercase">{{ $categoria }}</h2>

                            @foreach ($productos as $producto => $resumenPorFechas)
                                <h3 class="font-semibold text-lg mt-4">{{ $producto }}</h3>

                                <x-table class="mt-2">
                                    <x-slot name="thead">
                                        <x-tr>
                                            <x-th>#</x-th>
                                            <x-th>Rango de fechas</x-th>
                                            <x-th>Kg</x-th>
                                            <x-th>Kg/Ha</x-th>
                                        </x-tr>
                                    </x-slot>
                                    <x-slot name="tbody">
                                        @php $i = 1; @endphp
                                        @foreach ($resumenPorFechas as $rango => $valores)
                                            <x-tr>
                                                <x-td>{{ $i++ }}</x-td>
                                                <x-td>{{ $rango }}</x-td>
                                                <x-td>{{ $valores['kg'] ?? '-' }}</x-td>
                                                <x-td>{{ $valores['kg_ha'] ?? '-' }}</x-td>
                                            </x-tr>
                                        @endforeach
                                    </x-slot>
                                </x-table>
                            @endforeach
                        @endforeach
                    @endif
                </x-spacing>
            </x-card>

        </div>

        <div class="flex-1 overflow-auto">
            <x-flex class="justify-end w-full">
                <x-button wire:click="sincronizarPesticidaDesdeKardex">
                    <i class="fa fa-sync"></i> Actualizar desde Kardex
                </x-button>
            </x-flex>
            <x-card class="mt-5">
                <x-spacing>

                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center">N°</x-th>
                                <x-th class="text-center">Fecha</x-th>
                                <x-th>Producto</x-th>
                                <x-th class="text-center">Cantidad</x-th>
                                <x-th class="text-center">Cantidad x Ha</x-th>
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @foreach ($salidas as $indice => $productoSalida)
                                <x-tr>
                                    <x-td class="text-center">{{ $indice + 1 }}</x-td>
                                    <x-td
                                        class="text-center whitespace-nowrap">{{ formatear_fecha($productoSalida->fecha) }}</x-td>
                                    <x-td>{{ $productoSalida->producto->nombre_comercial }}</x-td>
                                    <x-td class="text-center">{{ $productoSalida->kg }}</x-td>
                                    <x-td class="text-center">{{ $productoSalida->kg_ha }}</x-td>
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>
                </x-spacing>
            </x-card>
        </div>
    </x-flex>
</div>
