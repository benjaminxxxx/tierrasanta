<div>
    <x-flex class="!items-start w-full">
        <div class="md:w-[35rem]">
            <x-card>
                <x-spacing>
                    <x-h3>Resumen de fertilización</x-h3>

                    @if ($resumenSalidas)
                        @php
                            $campos = [
                                'n_ha' => 'N/Ha',
                                'p_ha' => 'P/Ha',
                                'k_ha' => 'K/Ha',
                                'ca_ha' => 'Ca/Ha',
                                'mg_ha' => 'Mg/Ha',
                                'zn_ha' => 'Zn/Ha',
                                'mn_ha' => 'Mn/Ha',
                                'fe_ha' => 'Fe/Ha',
                            ];
                        @endphp

                        @foreach ($resumenSalidas as $producto => $resumenPorFechas)
                            @php
                                // Detectar qué campos mostrar para este producto
                                $camposVisibles = [];
                                foreach ($campos as $campo => $etiqueta) {
                                    foreach ($resumenPorFechas as $valores) {
                                        if (!empty($valores[$campo]) && $valores[$campo] != 0) {
                                            $camposVisibles[$campo] = $etiqueta;
                                            break;
                                        }
                                    }
                                }
                            @endphp

                            <h3 class="font-bold text-lg mt-6">{{ $producto }}</h3>

                            <x-table class="mt-3">
                                <x-slot name="thead">
                                    <x-tr>
                                        <x-th>#</x-th>
                                        <x-th>Rango de fechas</x-th>
                                        <x-th>Kg</x-th>
                                        <x-th>Kg/Ha</x-th>
                                        @foreach ($camposVisibles as $etiqueta)
                                            <x-th>{{ $etiqueta }}</x-th>
                                        @endforeach
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
                                            @foreach (array_keys($camposVisibles) as $campo)
                                                <x-td>{{ $valores[$campo] ?? '-' }}</x-td>
                                            @endforeach
                                        </x-tr>
                                    @endforeach
                                </x-slot>
                            </x-table>
                        @endforeach
                    @endif

                </x-spacing>
            </x-card>
            <x-card class="mt-5">
                <x-spacing>
                    <x-h3>Nutrientes</x-h3>

                    @if ($campania)
                        <x-table class="mt-4">
                            <x-slot name="thead">
                                <x-tr>
                                    <x-th class="text-left">Nutriente</x-th>
                                    <x-th class="text-right">Cantidad Kg/Ha</x-th>
                                </x-tr>
                            </x-slot>

                            <x-slot name="tbody">

                                <x-tr>
                                    <x-td>Nitrógeno (N)</x-td>
                                    <x-td
                                        class="text-right">{{ number_format($campania->nutriente_nitrogeno_kg_x_ha, 2) }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td>Fósforo (P)</x-td>
                                    <x-td
                                        class="text-right">{{ number_format($campania->nutriente_fosforo_kg_x_ha, 2) }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td>Potasio (K)</x-td>
                                    <x-td
                                        class="text-right">{{ number_format($campania->nutriente_potasio_kg_x_ha, 2) }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td>Calcio (Ca)</x-td>
                                    <x-td
                                        class="text-right">{{ number_format($campania->nutriente_calcio_kg_x_ha, 2) }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td>Magnesio (Mg)</x-td>
                                    <x-td
                                        class="text-right">{{ number_format($campania->nutriente_magnesio_kg_x_ha, 2) }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td>Zinc (Zn)</x-td>
                                    <x-td
                                        class="text-right">{{ number_format($campania->nutriente_zinc_kg_x_ha, 2) }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td>Manganeso (Mn)</x-td>
                                    <x-td
                                        class="text-right">{{ number_format($campania->nutriente_manganeso_kg_x_ha, 2) }}</x-td>
                                </x-tr>

                                <x-tr>
                                    <x-td>Fierro (Fe)</x-td>
                                    <x-td
                                        class="text-right">{{ number_format($campania->nutriente_fierro_kg_x_ha, 2) }}</x-td>
                                </x-tr>
                            </x-slot>
                        </x-table>
                    @endif
                </x-spacing>
            </x-card>

        </div>

        <div class="flex-1 overflow-auto">
            <x-flex class="justify-end w-full">
                <x-button wire:click="sincronizarDesdeKardex">
                    <i class="fa fa-sync"></i> Actualizar desde Kardex
                </x-button>
            </x-flex>
            <x-card class="mt-5">
                <x-spacing>
                    @php
                        $campos = [
                            'n_ha' => 'Cantidad de Nitrógeno x Ha',
                            'p_ha' => 'Cantidad de Fósforo x Ha',
                            'k_ha' => 'Cantidad de Potasio x Ha',
                            'ca_ha' => 'Cantidad de Calcio x Ha',
                            'mg_ha' => 'Cantidad de Magnesio x Ha',
                            'zn_ha' => 'Cantidad de Zinc x Ha',
                            'mn_ha' => 'Cantidad de Manganeso x Ha',
                            'fe_ha' => 'Cantidad de Fierro x Ha',
                        ];

                        // Detectar campos que tienen al menos un valor no vacío
                        $camposVisibles = [];
                        foreach ($campos as $campo => $etiqueta) {
                            foreach ($salidas as $salida) {
                                if (!is_null($salida->$campo)) {
                                    $camposVisibles[$campo] = $etiqueta;
                                    break;
                                }
                            }
                        }
                    @endphp

                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center">N°</x-th>
                                <x-th class="text-center">Fecha</x-th>
                                <x-th>Producto</x-th>
                                <x-th class="text-center">Cantidad</x-th>
                                <x-th class="text-center">Cantidad x Ha</x-th>
                                @foreach ($camposVisibles as $etiqueta)
                                    <x-th class="text-center">{{ $etiqueta }}</x-th>
                                @endforeach
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
                                    @foreach (array_keys($camposVisibles) as $campo)
                                        <x-td class="text-center">{{ $productoSalida->$campo }}</x-td>
                                    @endforeach
                                </x-tr>
                            @endforeach
                        </x-slot>
                    </x-table>


                </x-spacing>
            </x-card>
        </div>
    </x-flex>
</div>
