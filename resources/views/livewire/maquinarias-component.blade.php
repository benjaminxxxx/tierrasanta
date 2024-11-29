<div>

    <div class="md:flex items-center gap-5 mb-5">
        <x-h3>
            Maquinarias
        </x-h3>
        <x-button type="button" @click="$wire.dispatch('RegistrarMaquinaria')" class="w-full md:w-auto ">
            <i class="fa fa-plus"></i> Nueva Maquinaria
        </x-button>
    </div>
    <x-card>
        <x-spacing>
            <x-table class="mt-5">
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
                                        <x-secondary-button
                                            @click="$wire.dispatch('EditarMaquinaria',{'id':{{ $maquinaria->id }}})">
                                            <i class="fa fa-edit"></i>
                                        </x-secondary-button>
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
        </x-spacing>
    </x-card>
</div>
