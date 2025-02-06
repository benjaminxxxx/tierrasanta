<div>
    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="complete">
        <x-slot name="title">
            Detalle de horas
        </x-slot>

        <x-slot name="content">
            <div>
                <x-h3>Todas las actividades</x-h3>
                <x-table class="mb-4">
                    <x-slot name="thead">

                        <x-tr>
                            @if ($diasSemana)
                                @foreach ($diasSemana as $numero => $diaSemana)
                                    <x-th class="text-center">{{ $numero }}</x-th>
                                @endforeach
                            @endif
                        </x-tr>
                        <x-tr>
                            @if ($diasSemana)
                                @foreach ($diasSemana as $numero => $diaSemana)
                                    <x-th class="text-center">{{ $diaSemana['dia'] }}</x-th>
                                @endforeach
                            @endif
                        </x-tr>
                    </x-slot>
                    <x-slot name="tbody">
                        <x-tr>
                            @if ($diasSemana)
                                @foreach ($diasSemana as $numero => $diaSemana)
                                    <x-td>
                                        @if (isset($actividadesPorDia[$numero]))
                                            <ul>
                                                @foreach ($actividadesPorDia[$numero] as $actividadPorDia)
                                                    <li>
                                                        <p class="mt-2">
                                                            <b>{{ $actividadPorDia['labores']['nombre_labor'] }}
                                                                ({{ $actividadPorDia['labor_id'] }})
                                                            </b>
                                                        </p>
                                                        <p>Campo: {{ $actividadPorDia['campo'] }}</p>
                                                        <p>Horas: {{ $actividadPorDia['horas_trabajadas'] }}</p>
                                                        <p>Sujeto a Bono:
                                                            {{ $actividadPorDia['labor_valoracion_id'] == null ? 'No' : 'Si' }}
                                                        </p>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p>Sin actividades.</p>
                                        @endif
                                    </x-td>
                                @endforeach
                            @endif
                        </x-tr>
                    </x-slot>
                </x-table>
            </div>
            <x-flex class="w-full">
                <div class="flex-1">
                    <x-table>
                        <x-slot name="thead">
                            <x-tr>
                                <x-th class="text-center" rowspan="2">
                                    N°
                                </x-th>
                                <x-th rowspan="2">
                                    Cudrillero
                                </x-th>
                                @if ($diasSemana)
                                    @foreach ($diasSemana as $numero => $diaSemana)
                                        <x-th class="text-center">{{ $numero }}</x-th>
                                    @endforeach
                                @endif
                            </x-tr>
                            <x-tr>
                                @if ($diasSemana)
                                    @foreach ($diasSemana as $numero => $diaSemana)
                                        <x-th class="text-center">{{ $diaSemana['dia'] }}</x-th>
                                    @endforeach
                                @endif
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @if ($cuadrilleros)
                                @foreach ($cuadrilleros as $indice => $cuadrillero)
                                    <x-tr>
                                        <x-td class="text-center">
                                            {{ $indice + 1 }}
                                        </x-td>
                                        <x-td>
                                            {{ $cuadrillero['nombres'] }}
                                        </x-td>
                                        @if ($diasSemana)
                                            @foreach ($diasSemana as $diaFecha => $diaSemana)
                                                @php
                                                    $informacionCuadrillero =
                                                        $diaSemana['cuadrillero'][$cuadrillero['cua_asi_sem_cua_id']];
                                                @endphp
                                                <x-td>
                                                    <p>
                                                        Horas Registradas:
                                                        <b>{{ $informacionCuadrillero['horas'] }}</b>
                                                    </p>
                                                    <p>
                                                        Horas Detalladas:
                                                        <b>{{ $informacionCuadrillero['horas_contabilizadas'] }}</b>
                                                    </p>

                                                    <div>
                                                        Detalle:
                                                        @if (isset($actividadesPorDia[$diaFecha]))
                                                            <ul>
                                                                @foreach ($actividadesPorDia[$diaFecha] as $actividadPorDia)
                                                                    @php
                                                                        $actividadId = $actividadPorDia['id'];
                                                                    @endphp
                                                                    <li>
                                                                        <p class="mt-2">
                                                                            <b>Labor ({{ $actividadPorDia['labor_id'] }})</b>
                                                                            @if (isset($informacionCuadrillero['detalle']))
                                                                                @if (isset($informacionCuadrillero['detalle'][$actividadId]))
                                                                                    <span>Si</span>
                                                                                @else
                                                                                    <span>No</span>
                                                                                @endif
                                                                            @else
                                                                                <span>-</span>
                                                                            @endif
                                                                        </p>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif

                                                    </div>

                                                </x-td>
                                            @endforeach
                                        @endif
                                    </x-tr>
                                @endforeach
                            @endif

                        </x-slot>
                    </x-table>
                </div>
            </x-flex>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                Cerrar
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>
</div>
