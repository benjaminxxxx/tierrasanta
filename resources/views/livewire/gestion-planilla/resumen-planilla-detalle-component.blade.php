<div>

    <x-card class="mt-4">

        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-white">
            <thead class="text-xs text-gray-700 uppercase dark:bg-gray-700 dark:text-white">
                <tr>
                    <th scope="col" class="px-1 py-2">
                        N°
                    </th>
                    <th scope="col" class="px-1 py-2">
                        Empleado
                    </th>
                    @foreach ($diasMes as $diaMes)
                        <th scope="col" class="px-1 py-2">
                            {{ $esDias[$diaMes->format('D')] }} {{ $diaMes->format('d') }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($empleadosGeneral as $indice => $empleadoGeneral)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">

                        <td class="px-1 py-1">
                            {{ $indice + 1 }}
                        </td>
                        <th scope="row" class="px-1 py-1 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $empleadoGeneral->detalleMensual->nombres }}
                        </th>
                        @foreach ($diasMes as $diaMes)
                            <td class="px-1 py-1">
                                @if (isset($empleadosData[$empleadoGeneral['plan_det_men_id']][$diaMes->format('Y-m-d')]))
                                    @php
                                        $detalles =
                                            $empleadosData[$empleadoGeneral['plan_det_men_id']][$diaMes->format('Y-m-d')]
                                                ->detalles;

                                    @endphp
                                    @if ($detalles)
                                        @foreach ($detalles as $detalle)
                                            <p>{{ $detalle?->labores?->nombre_labor }}</p>
                                        @endforeach
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>
</div>