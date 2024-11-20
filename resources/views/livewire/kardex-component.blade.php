<div>
    <x-loading wire:loading />
    <x-flex>
        <x-h3>
            Kardex Indice
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('crearKardex')">
            <i class="fa fa-plus"></i> Registrar Kardex
        </x-button>
    </x-flex>
    <x-card class="my-4">
        <x-spacing>
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">N°</x-th>
                        <x-th>Nombre del Kardex</x-th>
                        <x-th class="text-center">Tipo</x-th>
                        <x-th class="text-center">Fecha Inicial</x-th>
                        <x-th class="text-center">Fecha Final</x-th>
                        <x-th class="text-center">Estado</x-th>
                        <x-th class="text-center">Acciones</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($kardexLista as $indice => $kardex)
                    <x-tr>
                        <x-td class="text-center">{{$indice + 1}}</x-td>
                        <x-td>{{$kardex->nombre}}</x-td>
                        <x-td class="text-center">{{$kardex->tipo_kardex}}</x-td>
                        <x-td class="text-center">{{$kardex->fecha_inicial}}</x-td>
                        <x-td class="text-center">{{$kardex->fecha_final}}</x-td>
                        <x-td class="text-center">{{mb_strtoupper($kardex->estado)}}</x-td>
                        <x-td class="text-center">
                            <x-flex class="justify-center">
                                <x-button-a type="button" href="{{route('kardex.ver',['id'=>$kardex->id])}}">
                                    <i class="fa fa-eye"></i> Ver Kardex
                                </x-button-a>
                                <x-danger-button type="button" wire:click="preguntarEliminar({{$kardex->id}})">
                                    <i class="fa fa-trash"></i>
                                </x-danger-button>
                            </x-flex>
                        </x-td>
                    </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        </x-spacing>
    </x-card>
</div>
