<div>
    <x-card2>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-primaryTextDark">
                <thead class="text-xs text-gray-700 uppercase dark:bg-primaryDark dark:text-primaryTextDark">
                    <tr>
                        <th scope="col" class="px-6 py-3">
                            N°
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Empleado
                        </th>
                        @foreach ($diasMes as $diaMes)
                            <th scope="col" class="px-6 py-3">
                                {{ $esDias[$diaMes->format('D')] }} {{ $diaMes->format('d') }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($empleadosGeneral as $indice => $empleadoGeneral)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">

                            <td class="px-6 py-4">
                                {{ $indice + 1 }}
                            </td>
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $empleadoGeneral->empleado_nombre }}
                            </th>
                            @foreach ($diasMes as $diaMes)
                                <td class="px-6 py-4">
                                    @if (isset($empleadosData[$empleadoGeneral['documento']][$diaMes->format('Y-m-d')]))
                                        @php
                                            $detalles =
                                                $empleadosData[$empleadoGeneral['documento']][$diaMes->format('Y-m-d')]
                                                    ->detalles;

                                        @endphp
                                        @if ($detalles)
                                            @foreach ($detalles as $detalle)
                                                <p>{{ $detalle->labor }}</p>
                                            @endforeach
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </x-card2>
</div>
