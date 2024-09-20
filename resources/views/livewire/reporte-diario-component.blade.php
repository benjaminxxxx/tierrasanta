<div>
    <x-card>
        <x-spacing>
            <x-h3>
                Reporte Diario de Trabajo
            </x-h3>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th>N°</x-th>
                        <x-th class="!text-left">APELLIDOS Y NOMBRES</x-th>
                        <x-th>ASIST.</x-th>

                        @for ($x = 0; $x < 5; $x++)
                            <x-th>CAMPO</x-th>
                            <x-th>LABOR</x-th>
                            <x-th>HORA INICIO</x-th>
                            <x-th>HORA DE SALIDA</x-th>
                        @endfor
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($empleados && $empleados->count() > 0)
                        @foreach ($empleados as $indice => $empleado)
                            <x-tr>
                                <x-td>{{$indice+1}}</x-th>
                                <x-td class="!text-left">{{$empleado->NombreCompleto}}</x-th>
                                <x-td></x-td>

                                @for ($x = 0; $x < 5; $x++)
                                    <x-td>1</x-td>
                                    <x-td>2</x-td>
                                    <x-td>03:35</x-td>
                                    <x-td>04:00</x-td>
                                @endfor
                            </x-tr>
                        @endforeach
                    @endif
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>

</div>
