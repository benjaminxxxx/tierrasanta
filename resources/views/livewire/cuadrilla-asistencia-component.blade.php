<div>
    <x-card>
        <x-spacing>
            <div class="block md:flex items-center gap-5">
                <x-h2>
                    Asistencias Cuadrilla
                </x-h2>
                <div class="mt-5 md:mt-0">
                    <livewire:cuadrilla-asistencia-form-component />
                </div>
            </div>
        </x-spacing>
    </x-card>
    <x-card class="mt-5">
        <x-spacing>
            @if ($cuadrilla)
                <x-h3 class="w-full text-center">
                    {{ mb_strtoupper($cuadrilla->titulo) }}
                </x-h3>
                <div class="w-full">
                    <x-table class="mt-5">
                        <x-slot name="thead">
                            <x-tr>
                                <x-th value="" rowspan="2"
                                    class="text-center border-1 border-gray border bg-whiten" />
                                <x-th value="N°" rowspan="2"
                                    class="text-center border-1 border-gray border bg-whiten" />
                                <x-th value="FECHA" class="text-center border-1 border-gray border bg-whiten" />
                                @if ($fechas)
                                    @foreach ($fechas as $fecha)
                                        <x-th value="{{ str_pad($fecha['dia_numero'], 2, '0', STR_PAD_LEFT) }}"
                                            class="text-center border-1 border-gray border bg-whiten" />
                                    @endforeach
                                @endif
                                <x-th value="MONTO S/." rowspan="2" class="text-center border-1 border-gray border bg-whiten" />
                            </x-tr>
                            <x-tr>
                                <x-th value="NOMBRES" class="text-center border-1 border-gray border bg-whiten" />
                                @if ($fechas)
                                    @foreach ($fechas as $fecha)
                                        <x-th value="{{ $fecha['dia_nombre'] }}"
                                            class="text-center border-1 border-gray border bg-whiten" />
                                    @endforeach
                                @endif
                            </x-tr>
                        </x-slot>
                        <x-slot name="tbody">
                            @if ($grupos)
                                @foreach ($grupos as $nombreGrupo => $grupo)
                                    @php
                                        $cuadrilleros = array_key_exists($grupo->codigo, $cuadrillerosPorGrupo)
                                            ? $cuadrillerosPorGrupo[$grupo->codigo]
                                            : null;
                                        $totalCuadrilleros = $cuadrilleros ? $cuadrilleros->count() : 1;
                                        $contadorPorGrupo = 0;
                                    @endphp
                                    @if ($cuadrilleros)
                                        @foreach ($cuadrilleros as $cuadrillero)
                                            <x-tr
                                                style="background:{{ $cuadrillero->grupo ? $cuadrillero->grupo->color : '#ffffff' }}">
                                                @if ($contadorPorGrupo == 0)
                                                    <x-th rowspan="{{ $totalCuadrilleros }}"
                                                        class="text-center border-1 border-gray border">
                                                        {{ $grupo->nombre }} <br />
                                                        ({{ mb_strtoupper($grupo->modalidad_pago) }})
                                                    </x-th>
                                                @endif
                                                @php
                                                    $contadorPorGrupo++;
                                                @endphp
                                                <x-th value="{{ $contadorPorGrupo }}"
                                                    class="text-center border-1 border-gray border" />
                                                <x-th value="{{ mb_strtoupper($cuadrillero->nombres) }}"
                                                    class="border-1 border-gray border" />
                                                @if ($fechas)
                                                    @foreach ($fechas as $fecha)
                                                        <x-th class="text-center border-1 border-gray border bg-whiten">
                                                            <input type="text" class="w-16 border-none focus:outline-none focus:shadow-none focus:ring-0"/>
                                                        </x-th>
                                                    @endforeach
                                                @endif
                                                <x-td class="text-center border-1 border-gray border">
                                                    
                                                </x-td>
                                            </x-tr>
                                        @endforeach
                                    @else
                                        <x-tr style="background:{{ $grupo ? $grupo->color : '#ffffff' }}">
                                            <x-th value="{{ $grupo->nombre }}"
                                                class="text-center border-1 border-gray border">
                                                {{ $grupo->nombre }} <br />
                                                ({{ mb_strtoupper($grupo->modalidad_pago) }})
                                            </x-th>
                                            <x-th value="" class="text-center border-1 border-gray border" />
                                            <x-th value="Sin Cuadrilleros"
                                                class="text-center border-1 border-gray border" />
                                        </x-tr>
                                    @endif
                                @endforeach
                            @endif
                        </x-slot>
                    </x-table>
                </div>

            @endif
        </x-spacing>
    </x-card>
</div>
