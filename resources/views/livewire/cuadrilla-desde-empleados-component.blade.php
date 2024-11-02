<div>
    <x-loading wire:loading />
    <x-dialog-modal wire:model.live="mostrarFormulario">
        <x-slot name="title">
            Registrar Cuadrillero de Planilla
        </x-slot>

        <x-slot name="content">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @if ($empleados)
                    @foreach ($empleados as $empleado)
                        <div wire:click="seleccionarEmpleado({{ $empleado->id }})"
                             class="cursor-pointer p-4 rounded-lg border shadow-sm transition duration-200 hover:shadow-lg 
                                    {{ in_array($empleado->id, $empleadosSeleccionados) ? 'bg-green-100' : 'bg-white' }}">
                            <div class="flex items-center justify-between">
                                <!-- Nombre del empleado -->
                                {{ $empleado->nombreCompleto }}

                                <!-- Icono de check visible solo si está seleccionado -->
                                @if(in_array($empleado->id, $empleadosSeleccionados))
                                    <div class="text-green-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="flex items-center gap-2">
                <x-secondary-button wire:click="$set('mostrarFormulario', false)" wire:loading.attr="disabled">
                    Cerrar
                </x-secondary-button>
                <x-button wire:click="registrarSeleccionados" wire:loading.attr="disabled">
                    Agregar seleccionados
                </x-button>
            </div>
        </x-slot>
    </x-dialog-modal>
</div>
