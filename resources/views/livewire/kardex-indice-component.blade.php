<div>
    <x-card>
        <x-spacing>
            <x-h2 class="w-full text-center">TABLA PRINCIPAL</x-h2>
            <x-table class="my-4">
                <x-slot name="thead">
                    <x-tr>
                        <x-th colspan="2">
                            ÍNDICE DE FERTILIZANTES Y PESTICIDAS
                        </x-th>
                        <x-th>
                            CONDICIÓN
                        </x-th>
                        <x-th>
                            UNIDAD DE MEDIDA (TABLA 6)
                        </x-th>
                        <x-th>
                            TOTAL ENTRADAS UNIDADES
                        </x-th>
                        <x-th>
                            TOTAL ENTRADAS IMPORTE
                        </x-th>
                        <x-th>
                            TOTAL SALIDAS UNIDADES
                        </x-th>
                        <x-th>
                            TOTAL SALIDAS IMPORTE
                        </x-th>
                        <x-th>
                            SALDO UNIDADES
                        </x-th>
                        <x-th>
                            SALDO IMPORTE
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($productosKardex as $productoKardex)
                        <x-tr>
                            <x-th>
                                {{$productoKardex['codigo_existencia']}}
                            </x-th>
                            <x-th>
                                {{$productoKardex['nombre_comercial']}}
                            </x-th>
                            <x-th>
                                
                            </x-th>
                            <x-th>
                                {{$productoKardex['tabla_6']}}
                            </x-th>
                            <x-th>
                                TOTAL ENTRADAS IMPORTE
                            </x-th>
                            <x-th>
                                TOTAL SALIDAS UNIDADES
                            </x-th>
                            <x-th>
                                TOTAL SALIDAS IMPORTE
                            </x-th>
                            <x-th>
                                SALDO UNIDADES
                            </x-th>
                            <x-th>
                                SALDO IMPORTE
                            </x-th>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
