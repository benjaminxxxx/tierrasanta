{{-- livewire/gestion-usuario/usuario-form-component.blade.php --}}
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input type="text" wire:keydown.enter="crear" label="Nombre de usuario" autocomplete="off"
                        error="name" wire:model="name" />

                    <x-input type="email" label="Email" wire:keydown.enter="crear" autocomplete="off" wire:model="email"
                        error="email" />

                    <x-input type="password" label="Contraseña" wire:keydown.enter="crear" wire:model="password"
                        autocomplete="new-password" error="password" />
                    @if ($userId)
                        <small class="text-gray-500 col-span-full">
                            Si la contraseña es la misma deje este campo en blanco.
                        </small>
                    @endif
                </div>

                <hr class="my-4 border-border">

                <x-h3>Asignar Rol</x-h3>
                <p class="text-sm text-gray-500 mb-3">Solo se puede asignar un rol por usuario.</p>

                <div x-data="{
        rolSeleccionado: @entangle('rolSeleccionado').live,
        nuevoRol: '',

        seleccionarRol(nombre) {
            this.rolSeleccionado = nombre;
            this.nuevoRol = '';
            $wire.set('nuevoRol', '');
        },

        escribirNuevoRol() {
            if (this.nuevoRol.trim() !== '') {
                this.rolSeleccionado = '';
                $wire.set('rolSeleccionado', '');
            }
            $wire.set('nuevoRol', this.nuevoRol);
        }
    }">
                    {{-- Roles existentes como radio buttons --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                        @foreach ($rolesDisponibles as $rol)
                                <label class="flex items-center gap-3 border rounded p-3 cursor-pointer transition-colors"
                                    :class="rolSeleccionado === '{{ $rol->name }}'
                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-600'
                            : 'border-card-foreground'">
                                    <input type="radio" name="rol" value="{{ $rol->name }}"
                                        :checked="rolSeleccionado === '{{ $rol->name }}'"
                                        x-on:change="seleccionarRol('{{ $rol->name }}')" class="accent-blue-600" />
                                    <span class="text-sm font-medium text-card-foreground">{{ $rol->name }}</span>
                                </label>
                        @endforeach
                    </div>

                    {{-- Input para crear nuevo rol --}}
                    <div class="border rounded p-3 border-dashed border-gray-300">
                        <x-input label="O escribe un nuevo rol" type="text" x-model="nuevoRol"
                            x-on:input.debounce.400ms="escribirNuevoRol()" placeholder="Nombre del nuevo rol..."
                            autocomplete="off" />
                        @error('nuevoRol')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                        @error('rolSeleccionado')
                            <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-5">
                <x-button variant="secondary" wire:click="cerrarMostrarFormulario" wire:loading.attr="disabled">
                    Cerrar
                </x-button>
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