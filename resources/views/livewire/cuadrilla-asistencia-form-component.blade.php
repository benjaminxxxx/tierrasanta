<div>
    <x-button type="button" wire:click="CrearRegistroSemanal" class="w-full md:w-auto ">
        <i class="fa fa-plus"></i> Registrar Asistencia Semanal
    </x-button>

    <x-dialog-modal wire:model="isFormOpen" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Nueva Asisencia Semanal
                </x-h3>
                <div class="flex-shrink-0">
                    <button wire:click="$set('isFormOpen', false)" class="focus:outline-none">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>
            </div>
        </x-slot>
        <x-slot name="content">
            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th value="Grupo" />
                        <x-th value="Precio" class="text-center" />
                        <x-th value="Horas por Jornal" class="text-center" />
                        <x-th value="Precio por Hora" class="text-center" />
                        <x-th value="Incluir" class="text-center" />
                        <x-th value="Incluir personales" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($gruposCuadrilla && $gruposCuadrilla->count())
                        @foreach ($gruposCuadrilla as $indice => $grupo)
                            <x-tr>
                                <x-th>
                                    <div class="rounded-lg p-2"
                                        style="background-color:{{ $grupo->color ? $grupo->color : '#ffffff' }}">
                                        {{ $grupo->nombre }}
                                    </div>

                                </x-th>
                                <x-th class="text-center">
                                    <input type="text"
                                        wire:model.live="grupos.{{ $grupo->codigo }}.costo_dia_sugerido"
                                        class="p-2 !w-16 border-1 border-gray-400 rounded-lg text-center" />
                                </x-th>
                                <x-th class="text-center">
                                    8
                                </x-th>
                                <x-th class="text-center">
                                    <input type="text" wire:model.live="grupos.{{ $grupo->codigo }}.total"
                                        class="p-2 !w-20 border-1 border-gray-400 rounded-lg text-center" readonly />
                                </x-th>
                                <x-th class="text-center">
                                    <x-checkbox wire:model.live="grupos.{{ $grupo->codigo }}.activo" class="" />
                                </x-th>
                                <x-th class="text-center">
                                    @if (array_key_exists($grupo->codigo, $grupos) && $grupos[$grupo->codigo]['activo'])
                                        <x-checkbox wire:model="grupos.{{ $grupo->codigo }}.conpersonales"
                                            class="" />
                                    @endif
                                </x-th>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Empleados registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
            <form wire:submit.prevent="store" class="my-5">

                <div class="grid grid-cols-2 gap-5">

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="fecha_inicio">Fecha de inicio</x-label>
                        <x-input type="date" class="uppercase" wire:model="fecha_inicio"
                            wire:change="evaluarTituloFecha" id="fecha_inicio" />
                        <x-input-error for="fecha_inicio" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="fecha_fin">Fecha de Fin</x-label>
                        <x-input type="date" class="uppercase" wire:model="fecha_fin"
                            wire:change="evaluarTituloFecha" id="fecha_fin" />
                        <x-input-error for="fecha_fin" />
                    </div>

                    <div class="col-span-2 mt-3">
                        <x-label for="titulo">Titulo del Libro</x-label>
                        <x-input type="text" autocomplete="off" wire:model="titulo" class="uppercase"
                            id="titulo" />
                        <x-input-error for="titulo" />
                    </div>

                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="$set('isFormOpen', false)"
                class="mr-2">Cancelar</x-secondary-button>
            <x-button type="submit" wire:click="store" class="ml-3">Guardar</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
