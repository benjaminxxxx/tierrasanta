<div>
    <x-card class="mt-5">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th rowspan="2">
                            N°
                        </x-th>
                        <x-th rowspan="2">
                            Empleado
                        </x-th>
                        @foreach ($diasMes as $diaMes)
                            <x-th>
                                {{ $esDias[$diaMes->format('D')] }}
                            </x-th>
                        @endforeach
                    </x-tr>
                    <x-tr>
                        @foreach ($diasMes as $diaMes)
                            <x-th>
                                {{ $diaMes->format('d') }}
                            </x-th>
                        @endforeach
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($empleadosGeneral as $indice => $empleadoGeneral)
                        <x-tr>
                            <x-td>
                                {{ $indice + 1 }}
                            </x-td>
                            <x-td>
                                {{ $empleadoGeneral->empleado_nombre }}
                            </x-td>
                            @foreach ($diasMes as $diaMes)
                                <x-th>
                                    @if (isset($empleadosData[$empleadoGeneral['documento']][$diaMes->format('Y-m-d')]))
                                        @php
                                            $detalles = $empleadosData[$empleadoGeneral['documento']][$diaMes->format('Y-m-d')]->detalles;
                                            
                                        @endphp
                                        @if ($detalles)
                                            @foreach ($detalles as $detalle)
                                                <p>{{$detalle->labor}}</p>
                                            @endforeach
                                        @endif
                                    @endif
                                </x-th>
                            @endforeach
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
