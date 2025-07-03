<div class="p-6">
    <x-h3>Gestión de Roles y Permisos</x-h3>

    <div class="flex justify-end space-x-2 my-4">
        @hasrole('Super Admin')
        <x-button wire:click="$set('mostrarModalCrearPermiso', true)">
            <I class="fa fa-plus"></I> Nuevo Permiso
        </x-button>
        @endhasrole

        <x-button wire:click="$set('mostrarModalCrearRol', true)">
            <I class="fa fa-plus"></I> Nuevo Rol
        </x-button>
    </div>

    <x-h3>Roles Existentes</x-h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 my-4">
        @foreach ($roles as $rol)

            @if ($rol->name == 'Super Admin')


            @else
                <x-card>
                    <x-spacing>
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-lg dark:text-primaryTextDark">{{ $rol->name }}</span>
                            <div class="flex space-x-2">
                                <x-secondary-button wire:click="editarRol({{ $rol->id }})" title="Editar Rol">
                                    <i class="fa fa-edit"></i>
                                </x-secondary-button>
                                <x-danger-button wire:click="eliminarRol({{ $rol->id }})" title="Eliminar Rol">
                                    <i class="fa fa-trash"></i>
                                </x-danger-button>
                            </div>
                        </div>


                        <div class="mt-2">
                            <span class="font-semibold dark:text-primaryTextDark">Permisos:</span>
                            @if ($rol->permissions->count())
                                <ul class="list-disc list-inside text-sm text-gray-700 mt-1 dark:text-primaryTextDark">
                                    @foreach ($rol->permissions as $permiso)
                                        <li>{{ $permiso->name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-gray-500 dark:text-primaryTextDark">Sin permisos asignados.</p>
                            @endif
                        </div>
                    </x-spacing>
                </x-card>
            @endif


        @endforeach
    </div>

    <x-h3>Permisos Existentes</x-h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 my-4">
        @foreach ($permisos as $permiso)
            <x-card>
                <x-spacing>
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-lg dark:text-primaryTextDark">{{ $permiso->name }}</span>
                        <div class="flex space-x-2">

                            @hasrole('Super Admin')
                            <x-secondary-button wire:click="editarPermiso({{ $permiso->id }})" title="Editar Permiso">
                                <i class="fa fa-edit"></i>
                            </x-secondary-button>
                            <x-danger-button wire:click="eliminarPermiso({{ $permiso->id }})" title="Eliminar Permiso">
                                <i class="fa fa-trash"></i>
                            </x-danger-button>
                            @endhasrole
                        </div>
                    </div>

                    <p class="text-sm text-gray-500 mt-1 dark:text-primaryTextDark">
                        ID: {{ $permiso->id }}
                    </p>
                </x-spacing>
            </x-card>
        @endforeach
    </div>

    <!-- Modal Crear Permiso -->
    <x-dialog-modal wire:model.live="mostrarModalCrearPermiso">
        <x-slot name="title">
            <x-h3>Crear Nuevo Permiso</x-h3>
        </x-slot>

        <x-slot name="content">
            <x-input-string label="Nombre del Permiso" wire:model.live="nombrePermiso" error="nombrePermiso" />

            <div class="mt-4">
                <span class="font-semibold">Asignar a Roles:</span>
                @foreach ($roles as $rol)
                    <div class="mt-1">
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model.live="rolesSeleccionados" value="{{ $rol->id }}"
                                class="rounded border-gray-300">
                            <span class="ml-2">{{ $rol->name }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end full-width">
                <x-secondary-button wire:click="$set('mostrarModalCrearPermiso', false)">
                    Cancelar
                </x-secondary-button>

                <x-button wire:click="guardarPermiso">
                    <I class="fa fa-save"></I> {{ $modoEditarPermiso ? 'Actualizar Permiso' : 'Guardar Permiso' }}
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    <!-- Modal Crear Rol -->
    <x-dialog-modal wire:model.live="mostrarModalCrearRol">
        <x-slot name="title">
            <x-h3>Crear Nuevo Rol</x-h3>
        </x-slot>

        <x-slot name="content">
            <x-input-string label="Nombre del Rol" wire:model.live="nombreRol" error="nombreRol" />

            <div class="mt-4">
                <span class="font-semibold">Asignar Permisos:</span>
                @foreach ($permisos as $permiso)
                    <div class="mt-1">
                        <label class="inline-flex items-center">
                            <input type="checkbox" wire:model.live="permisosSeleccionados" value="{{ $permiso->id }}"
                                class="rounded border-gray-300">
                            <span class="ml-2">{{ $permiso->name }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-flex class="justify-end full-width">
                <x-secondary-button wire:click="$set('mostrarModalCrearRol', false)">
                    Cancelar
                </x-secondary-button>

                <x-button wire:click="guardarRol">
                    <I class="fa fa-save"></I> {{ $modoEditarRol ? 'Actualizar Rol' : 'Guardar Rol' }}
                </x-button>
            </x-flex>

        </x-slot>
    </x-dialog-modal>
</div>