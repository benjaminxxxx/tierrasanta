<div>
    <x-dialog-modal-header wire:model="isFormOpen" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Asignación Familiar
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="closeForm" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="store">
                <div class="grid grid-cols-2 gap-5">
                    @if ($nombre_empleado)
                        <div class="col-span-2 mt-3">
                            <x-label for="nombresempleado">Empleado</x-label>
                            <x-label for="nombresempleado" class="font-semibold">{{ $nombre_empleado }}</x-label>
                        </div>
                    @endif
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="nombres">Nombres y Apellidos de su Hijo</x-label>
                        <x-input type="text" autocomplete="off" wire:model="nombres" class="uppercase"
                            id="nombres" />
                        <x-input-error for="nombres" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="documento">Documento</x-label>
                        <x-input type="text" autocomplete="off" class="uppercase" wire:model="documento"
                            id="documento" />
                        <x-input-error for="documento" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="fecha_nacimiento">Fecha de Nacimiento</x-label>
                        <x-input type="date" autocomplete="off" wire:model="fecha_nacimiento" class="uppercase"
                            id="fecha_nacimiento" />
                        <x-input-error for="fecha_nacimiento" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="esta_estudiando_cabecera">¿Cursa Estudios Superiores?</x-label>
                        <x-label for="esta_estudiando" class="mt-4">
                            <x-checkbox wire:model="esta_estudiando" id="esta_estudiando" class="mr-2" />
                            Está estudiando
                        </x-label>
                        <x-input-error for="esta_estudiando" />
                    </div>
                    <div class="col-span-2 text-right">
                        <x-button type="submit" class="ml-3">Guardar</x-button>
                    </div>
                </div>
            </form>
            @if ($asignaciones_familiares->count() > 0)
                <x-label class="mt-5">
                    Hijos Agregados
                </x-label>
                <x-table class="mt-5">
                    <x-slot name="thead">
                        <tr>
                            <x-th value="N°" class="text-center" />
                            <x-th value="Documento" />
                            <x-th value="Hijo" />
                            <x-th value="Fecha de Nacimiento" />
                            <x-th value="Edad" />
                            <x-th value="Está Estudiando" />
                            <x-th value="Acciones" class="text-center" />
                        </tr>
                    </x-slot>
                    <x-slot name="tbody">

                        @foreach ($asignaciones_familiares as $indice => $asignacion)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td value="{{ $asignacion->documento }}" class="text-center" />
                                <x-td value="{{ $asignacion->nombres }}" class="text-center" />
                                <x-td value="{{ $asignacion->fecha_nacimiento }}" class="text-center" />
                                <x-td value="{{ $asignacion->edad }}" class="text-center" />
                                <x-td value="{{ $asignacion->esta_estudiando_string }}" class="text-center" />
                                <x-td class="text-center">
                                    <div class="flex items-center justify-center gap-2">

                                        <x-danger-button wire:click="confirmarEliminacion({{ $asignacion->id }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </div>

                                </x-td>
                            </x-tr>
                        @endforeach

                    </x-slot>
                </x-table>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="closeForm" class="mr-2">Cerrar</x-secondary-button>
        </x-slot>
    </x-dialog-modal-header>
</div>
