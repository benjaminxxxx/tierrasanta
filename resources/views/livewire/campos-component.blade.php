<div>
    <x-card>
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">
                            N°
                        </x-th>
                        <x-th class="text-center">
                            Campo
                        </x-th>
                        <x-th class="text-center">
                            Campo Padre
                        </x-th>
                        <x-th class="text-center">
                            Área
                        </x-th>
                        <x-th class="text-center">
                            Campaña Actual
                        </x-th>
                        <x-th class="text-center">
                            Acciones
                        </x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($campos as $indice => $campo)
                        <x-tr>
                            <x-td class="text-center">
                                {{ $indice + 1 }}
                            </x-td>
                            <x-th class="text-center">
                                Campo {{ $campo->nombre }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $campo->campo_parent_nombre }}
                            </x-th>
                            <x-th class="text-center">
                                {{ $campo->area }}
                            </x-th>
                            <x-th class="text-center">
                                @if ($campo->campania_actual)
                                    <p><b>Nombre de la ultima Campaña: </b>{{$campo->campania_actual->nombre_campania}}</p>
                                    <p>Rango: {{$campo->campania_actual->fecha_inicio}} - {{$campo->campania_actual->fecha_fin}}</p>
                                @else
                                    <p>-</p>
                                @endif
                            </x-th>
                            <x-th class="text-center">
                                <x-flex>
                                    <x-button
                                        @click="$wire.dispatch('registroCampania',{campoNombre:'{{ $campo->nombre }}'})">
                                        <i class="fa fa-plus"></i> Crear campaña
                                    </x-button>
                                    <x-button-a href="{{route('campo.campania',['campo'=>$campo->nombre])}}">
                                        <i class="fa fa-eye"></i> Ver campañas
                                    </x-button-a>
                                </x-flex>
                            </x-th>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="mt-4">
                {{ $campos->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
