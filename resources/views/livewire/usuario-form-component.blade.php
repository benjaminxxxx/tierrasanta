<div>

    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            @if ($userId)
                Actualizar usuario
            @else
                Crear usuario
            @endif
        </x-slot>

        <x-slot name="content">
            <form autocomplete="nope">
                <div>
                    <x-label>Nombre de usuario</x-label>
                    <x-input type="text" wire:keydown.enter="crear" autocomplete="nope" wire:model="name" />
                    <x-input-error for="name" />
                </div>
                <div class="mt-3">
                    <x-label>Email</x-label>
                    <x-input type="email" wire:keydown.enter="crear" autocomplete="nope" wire:model="email" />
                    <x-input-error for="email" />
                </div>
                <div class="mt-3">
                    <x-label>Contraseña</x-label>
                    <x-input type="password" wire:keydown.enter="crear" wire:model="password" />
                    @if ($userId)
                        <small>Si la contraseña es la misma deje este campo en blanco.</small>
                    @endif
                    <x-input-error for="password" />
                </div>

                <hr class="my-4 border-gray-300">

                <x-h3>
                    Asignar Roles y Permisos
                </x-h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 dark:text-primaryTextDark">
                    @foreach ($rolesDisponibles as $rolIndice => $rolDisponible)
                        <div class="border rounded p-3">
                            <x-label for="rol_{{ $rolIndice }}">
                                <x-checkbox id="rol_{{ $rolIndice }}" value="{{ $rolDisponible->name }}" wire:model="rolesSeleccionados" class="mr-2" />
                                {{$rolDisponible->name}}
                            </x-label>
                        </div>
                    @endforeach
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-5">
                <x-secondary-button wire:click="cerrarMostrarFormulario" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="crear" wire:loading.attr="disabled">
                    @if ($userId)
                        Actualizar usuario
                    @else
                        Crear usuario
                    @endif
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>