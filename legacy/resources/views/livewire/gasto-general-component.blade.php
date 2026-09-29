<div>
    <x-loading wire:loading />
    <x-flex>
        <x-h3>
            Gasto General
        </x-h3>
    </x-flex>
    <x-card class="mt-4">
        <x-spacing>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <x-label>
                        Selecciona el mes contable
                    </x-label>
                    <x-select wire:model.live="mes">
                        @php
                            $meses = [
                                1 => 'Enero',
                                2 => 'Febrero',
                                3 => 'Marzo',
                                4 => 'Abril',
                                5 => 'Mayo',
                                6 => 'Junio',
                                7 => 'Julio',
                                8 => 'Agosto',
                                9 => 'Septiembre',
                                10 => 'Octubre',
                                11 => 'Noviembre',
                                12 => 'Diciembre',
                            ];
                        @endphp
                        @foreach ($meses as $numero => $nombre)
                            <option value="{{ $numero }}">{{ $nombre }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-label>
                        Selecciona el año contable
                    </x-label>
                    <x-select wire:model.live="anio">
                        @php
                            $anioActual = date('Y');
                        @endphp
                        @for ($i = $anioActual + 1; $i >= $anioActual - 3; $i--)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </x-select>
                </div>
            </div>
        </x-spacing>
    </x-card>
    <x-flex class="mt-5">
        <x-h3>
            Gasto Blanco
        </x-h3>
    </x-flex>
    <x-card class="mt-4">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>
                            Pago Planilla
                        </x-th>
                        <x-th>
                            Compra Insumos
                        </x-th>
                        <x-th>
                            Compra Combustible
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    <x-tr>
                        <x-td>
                            -
                        </x-td>
                        <x-td>
                            {{$compraInsumosBlanco}}
                        </x-td>
                        <x-td>
                            {{$compraCombustibleBlanco}}
                        </x-td>
                    </x-tr>
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
    <x-flex class="mt-5">
        <x-h3>
            Gasto Negro
        </x-h3>
    </x-flex>
    <x-card class="mt-4">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>
                            Pago Cuadrilleros
                        </x-th>
                        <x-th>
                            Gastos Cuadrilla<br/>
                            <small>Comisiones, Movilidad, etc.</small>
                        </x-th>
                        <x-th>
                            Compra Insumos
                        </x-th>
                        <x-th>
                            Compra Combustible
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    <x-tr>
                        <x-td>
                            {{$pagoCuadrilleros}}
                        </x-td>
                        <x-td>
                            {{$gastosCuadrilla}}
                        </x-td>
                        <x-td>
                            {{$compraInsumosNegro}}
                        </x-td>
                        <x-td>
                            {{$compraCombustibleNegro}}
                        </x-td>
                    </x-tr>
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
