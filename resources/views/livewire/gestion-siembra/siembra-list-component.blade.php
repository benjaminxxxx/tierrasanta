<div>
    <x-card>
        <x-flex>
            <x-title>
                Siembras
            </x-title>
            <x-button type="button" @click="$wire.dispatch('agregarSiembra')">
                <i class="fa fa-plus"></i> Registrar Siembra
            </x-button>
        </x-flex>
        <x-flex class="my-4">
            <x-select-campo wire:model.live="filtroCampo" label="Filtrar por campo" error="false"
                placeholder="Todos los campos" class="w-full" />

            <x-select label="Filtrar por año" wire:model.live="filtroAnio">
                <option value="">Todos los años</option>
                @foreach ($aniosDisponibles as $anio)
                    <option value="{{ $anio }}">{{ $anio }}</option>
                @endforeach
            </x-select>

        </x-flex>

        <div class="mt-5">
            <x-table>
                <x-slot name="thead">
                    <x-tr>
                        <x-th class="text-center">#</x-th>
                        <x-th class="text-center">Campo</x-th>
                        <x-th class="text-center">Fecha de Siembra</x-th>
                        <x-th class="text-center">Fecha de Renovación</x-th>
                        <x-th class="text-center">Acciones</x-th>
                    </x-tr>
                </x-slot>
                <x-slot name="tbody">
                    @foreach ($siembraLista as $index => $siembra)
                        <x-tr>
                            <x-td class="text-center">{{ $index + 1 }}</x-td>
                            <x-td class="text-center">{{ $siembra->campo_nombre }}</x-td>
                            <x-td class="text-center">{{ $siembra->fecha_siembra }}</x-td>
                            <x-td class="text-center">{{ $siembra->fecha_renovacion ?? '-' }}</x-td>

                            <x-td class="text-center">
                                <x-flex class="justify-center">
                                    <x-button @click="$wire.dispatch('editarSiembra',{id:{{ $siembra->id }}})">
                                        <i class="fa fa-edit"></i> Editar
                                    </x-button>
                                    <x-button variant="danger"
                                        wire:click="preguntarEliminarSiembra({{ $siembra->id }})">
                                        <i class="fa fa-trash"></i> Eliminar
                                    </x-button>
                                </x-flex>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
            <div class="mt-5">
                {{ $siembraLista->links() }}
            </div>
        </div>
    </x-card>

    <x-loading wire:loading />
</div>
