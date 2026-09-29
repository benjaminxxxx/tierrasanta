@if ($datosPorFecha)
    <x-table>
        <x-slot name="thead">
            <x-tr>
                <x-th class="text-center">Fecha</x-th>
                <x-th class="text-center">Ingresos</x-th>
                <x-th class="text-center">Venteados</x-th>
                <x-th class="text-center">Filtrados</x-th>
            </x-tr>
        </x-slot>
        <x-slot name="tbody">
            @foreach ($datosPorFecha as $fecha => $procesos)
                <x-tr>
                    <x-td class="align-top text-center">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</x-td>

                    {{-- INGRESOS --}}
                    <x-td class="align-top">
                        @foreach ($procesos['ingresos'] as $ingreso)
                            @if ($ingreso->sublote_codigo)
                                <div
                                    class="max-w-sm p-4 mb-2 bg-gray-100 border border-green-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                    <p class="font-semibold text-green-700">
                                        <i class="fa fa-bug"></i> Sublote: {{ $ingreso->sublote_codigo }}
                                    </p>
                                    <p>Fecha:
                                        {{ \Carbon\Carbon::parse($ingreso->fecha)->format('d/m/Y') }}
                                    </p>
                                    <p>Total: {{ $ingreso->total_kilos }} kg</p>
                                    <p>Obs: {{ $ingreso->observacionRelacionada?->descripcion }}</p>
                                </div>
                            @else
                                <div
                                    class="max-w-sm p-4 mb-2 bg-green-100 border border-green-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                    <p class="font-semibold text-green-700">
                                        <i class="fa fa-bug"></i> Lote: {{ $ingreso->lote }}
                                    </p>
                                    <p>Fecha:
                                        {{ \Carbon\Carbon::parse($ingreso->fecha)->format('d/m/Y') }}
                                    </p>
                                    <p>Total: {{ $ingreso->total_kilos }} kg</p>
                                    <p>Obs: {{ $ingreso->observacionRelacionada?->descripcion }}</p>
                                </div>
                            @endif
                        @endforeach
                    </x-td>

                    {{-- VENTEADOS --}}
                    <x-td class="align-top">
                        @foreach ($procesos['venteados'] as $venteado)
                            <div class="p-4 mb-2 bg-blue-100 rounded-xl shadow-sm">
                                <p class="text-blue-700 font-semibold">
                                    <i class="fas fa-wind"></i> Lote: {{ $venteado->lote }}
                                </p>
                                <p>Fecha:
                                    {{ \Carbon\Carbon::parse($venteado->fecha_proceso)->format('d/m/Y') }}
                                </p>
                                <p>Ingresado: {{ $venteado->kilos_ingresado }} kg</p>
                                <p>Limpia: {{ $venteado->limpia }} kg</p>
                                <p>Polvillo: {{ $venteado->polvillo }} kg</p>
                                <p>Basura: {{ $venteado->basura }} kg</p>
                            </div>
                        @endforeach
                    </x-td>

                    {{-- FILTRADOS --}}
                    <x-td class="align-top">
                        @foreach ($procesos['filtrados'] as $filtrado)
                            <div class="p-4 mb-2 bg-yellow-100 rounded-xl shadow-sm">
                                <p class="text-yellow-700 font-semibold">
                                    <i class="fas fa-filter"></i> Lote: {{ $filtrado->lote }}
                                </p>
                                <p>Fecha:
                                    {{ \Carbon\Carbon::parse($filtrado->fecha_proceso)->format('d/m/Y') }}
                                </p>
                                <p>1ra: {{ $filtrado->primera }} kg</p>
                                <p>2da: {{ $filtrado->segunda }} kg</p>
                                <p>3ra: {{ $filtrado->tercera }} kg</p>
                                <p>Piedra: {{ $filtrado->piedra }} kg</p>
                                <p>Basura: {{ $filtrado->basura }} kg</p>
                            </div>
                        @endforeach
                    </x-td>
                </x-tr>
            @endforeach
        </x-slot>
    </x-table>

    @if ($resumen)
        <x-table2 class="mt-4 w-full">
            <thead>
                <x-tr2>
                    <x-th2 width="33.333333%">Total Kilos</x-th2>
                    <x-th2 width="33.333333%" colspan="3">Total Kilos Venteado</x-th2>
                    <x-th2 width="33.333333%" colspan="5">Total Kilos Filtrado</x-th2>
                </x-tr2>
            </thead>
            <tbody>
                <x-tr2>
                    <x-td2 rowspan="3" class="align-center">
                        {!! barra_porcentaje(100, 'bg-green-500') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['total_kilos'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                    <x-td2 colspan="3" class="align-bottom">
                        {!! barra_porcentaje(100 - $resumen['porcentaje_diferencia'], 'bg-green-500') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['venteado_total_kilos'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                    <x-td2 colspan="5" class="align-bottom">
                        {!! barra_porcentaje(100 - $resumen['porcentaje_diferencia_filtrado'], 'bg-green-500') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['filtrado_total_kilos'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                </x-tr2>
                <x-tr2 class="align-bottom">
                    <x-th2>Limpia</x-th2>
                    <x-th2>Basura</x-th2>
                    <x-th2>Polvillo</x-th2>
                    <x-th2>1ra</x-th2>
                    <x-th2>2da</x-th2>
                    <x-th2>3ra</x-th2>
                    <x-th2>Piedra</x-th2>
                    <x-th2>Basura</x-th2>
                </x-tr2>
                <x-tr2 class="align-bottom">
                    <x-td2>
                        {!! barra_porcentaje($resumen['porcentaje_venteado_limpia'], 'bg-green-500') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['total_venteado_limpia'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                    <x-td2>
                        {!! barra_porcentaje($resumen['porcentaje_venteado_basura'], 'bg-red-400') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['total_venteado_basura'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                    <x-td2>
                        {!! barra_porcentaje($resumen['porcentaje_venteado_polvillo'], 'bg-yellow-400') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['total_venteado_polvillo'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                    <x-td2>
                        {!! barra_porcentaje($resumen['porcentaje_filtrado_primera'], 'bg-green-500') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['total_filtrado_primera'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                    <x-td2>
                        {!! barra_porcentaje($resumen['porcentaje_filtrado_segunda'], 'bg-red-400') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['total_filtrado_segunda'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                    <x-td2>
                        {!! barra_porcentaje($resumen['porcentaje_filtrado_tercera'], 'bg-yellow-400') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['total_filtrado_tercera'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                    <x-td2>
                        {!! barra_porcentaje($resumen['porcentaje_filtrado_piedra'], 'bg-stone-400') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['total_filtrado_piedra'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                    <x-td2>
                        {!! barra_porcentaje($resumen['porcentaje_filtrado_basura'], 'bg-yellow-400') !!}
                        <p>
                            <b class="text-lg">
                                {{ number_format($resumen['total_filtrado_basura'], 2) }}Kl
                            </b>
                        </p>
                    </x-td2>
                </x-tr2>
            </tbody>
        </x-table2>
    @endif
@endif
