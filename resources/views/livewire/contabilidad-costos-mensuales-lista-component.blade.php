<div>
    <x-loading wire:loading/>
    <x-h3>
        Resumen de Costos Mensuales
    </x-h3>
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
                            <x-th class="text-center">Acciones</x-th>
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        @php
                            $meses = [
                                '1' => 'Enero',
                                '2' => 'Febrero',
                                '3' => 'Marzo',
                                '4' => 'Abril',
                                '5' => 'Mayo',
                                '6' => 'Junio',
                                '7' => 'Julio',
                                '8' => 'Agosto',
                                '9' => 'Septiembre',
                                '10' => 'Octubre',
                                '11' => 'Noviembre',
                                '12' => 'Diciembre',
                            ];
                        @endphp
                        @foreach ($costos as $indice => $costo)
                            <x-tr>
                                <x-td class="text-center">{{ $indice + 1 }}</x-td>
                                <x-td class="text-center">{{ $costo->anio }}</x-td>
                                <x-td class="text-center">{{ $meses[$costo->mes] ?? 'Desconocido' }}</x-td>

                                @if ($verCostoNegro)
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_administrativo_negro ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_financiero_negro ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_gastos_oficina_negro ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_depreciaciones_negro ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_terreno_negro ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->operativo_servicios_fundo_negro ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->operativo_mano_obra_indirecta_negro ?? 0, 2) }}</x-td>
                                @else
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_administrativo_blanco ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_financiero_blanco ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_gastos_oficina_blanco ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_depreciaciones_blanco ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->fijo_terreno_blanco ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->operativo_servicios_fundo_blanco ?? 0, 2) }}</x-td>
                                    <x-td
                                        class="text-right">S/ {{ number_format($costo->operativo_mano_obra_indirecta_blanco ?? 0, 2) }}</x-td>
                                @endif

                                <x-td class="text-center">
                                   -
                                </x-td>
                            </x-tr>
                        @endforeach
                    </x-slot>
                </x-table>

                <x-flex class="justify-end w-full mt-5">
                   {{$costos->links()}}
                </x-flex>
            </div>
        </x-spacing>
    </x-card>
</div>
