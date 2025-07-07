<div>

    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="full">
        <x-slot name="title">
            Registre que labores se han realizado en los campos de la fecha indicada
        </x-slot>

        <x-slot name="content">

            <x-flex>
                <x-h3 class="my-2">
                    Lista de actividades registradas en fecha {{$fecha}}
                </x-h3>
                <x-button type="button" @click="$wire.dispatch('crearActividadDiaria',{fecha:'{{$fecha}}'})">
                    <i class="fa fa-plus"></i> Agregar actividad
                </x-button>
            </x-flex>
            <p>Si agregó una actividad que aun no tenía bono, debe eliminarlo y volverlo a agregar en caso quiera
                registrar sus bonos.</p>
            <x-table class="mt-2">
                <x-slot name="thead">

                    <x-tr>
                        <x-th class="text-center">N°</x-th>
                        <x-th>Labor</x-th>
                        <x-th class="text-center">Campo</x-th>
                        <x-th class="text-center">Horas trabajadas</x-th>
                        <x-th class="text-center">KG Promedio</x-th>
                        <x-th class="text-center">Acciones</x-th>
                    </x-tr>

                </x-slot>
                <x-slot name="tbody">

                    @foreach ($actividades as $indice => $actividad)
                        <x-tr>
                            <x-td class="text-center">{{ $indice + 1 }}</x-td>
                            <x-td>{{ $actividad->labores->nombre_labor }}</x-td>
                            <x-td class="text-center">{{ $actividad->campo }}</x-td>
                            <x-td class="text-center">{{ $actividad->horas_trabajadas }}</x-td>
                            <x-td class="text-center">{{ $actividad->kg }}</x-td>
                            <x-td>
                                <x-flex>
                                    <!--<x-secondary-button type="button"
                                        @click="$wire.dispatch('agregarCuadrillerosEnActividad',{actividadId:{{ $actividad->id }}})">
                                        <i class="fa fa-users"></i>
                                    </x-secondary-button>-->
                                    <x-secondary-button type="button"
                                        @click="$wire.dispatch('editarActividadDiaria',{actividadId:{{ $actividad->id }}})">
                                        <i class="fa fa-edit"></i>
                                    </x-secondary-button>
                                    <x-danger-button type="button"
                                        wire:click="preguntarEliminar({{ $actividad->id }})">
                                        <i class="fa fa-trash"></i>
                                    </x-danger-button>
                                </x-flex>
                            </x-td>
                        </x-tr>
                    @endforeach
                </x-slot>
            </x-table>
        </x-slot>
        <x-slot name="footer">
            <x-flex>
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>
