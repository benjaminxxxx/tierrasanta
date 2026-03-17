<div class="space-y-4">

    <x-flex>
        <x-title>
            Gestión de Maquinaria
        </x-title>
        <x-button type="button" @click="$wire.dispatch('RegistrarMaquinaria')">
            <i class="fa fa-plus"></i> Nueva Maquinaria
        </x-button>
    </x-flex>
    <x-card>
        <x-table>
            <x-slot name="thead">
                <tr>
                    <x-th class="text-center">
                        N°
                    </x-th>
                    <x-th>
                        Nombre de Maquinaria
                    </x-th>
                    <x-th>
                        Alias para el Kardex Blanco
                    </x-th>
                    <x-th value="ACCIONES" class="text-center" />
                </tr>
            </x-slot>
            <x-slot name="tbody">
                @if ($maquinarias && $maquinarias->count() > 0)
                    @foreach ($maquinarias as $indice => $maquinaria)
                        <x-tr>
                            <x-th value="{{ $indice + 1 }}" class="text-center" />
                            <x-td value="{{ $maquinaria->nombre }}" />
                            <x-td value="{{ $maquinaria->alias_blanco }}" />

                            <x-td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <x-button variant="secondary" 
                                        @click="$wire.dispatch('EditarMaquinaria',{'id':{{ $maquinaria->id }}})">
                                        <i class="fa fa-edit"></i> Editar
                                    </x-button>
                                </div>

                            </x-td>
                        </x-tr>
                    @endforeach
                @else
                    <x-tr>
                        <x-td colspan="4">No Hay Maquinarias Registradas.</x-td>
                    </x-tr>
                @endif
            </x-slot>
        </x-table>
    </x-card>
</div>
