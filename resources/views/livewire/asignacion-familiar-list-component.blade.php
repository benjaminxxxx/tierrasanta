<div>
    <x-card>
        <x-spacing>
            <div class="block md:flex items-center gap-5">
                <x-h2>
                    Asignación Familiar
                </x-h2>
            </div>
            <form class="flex my-10">

                <div class="relative w-full">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                        <i class="fa fa-search"></i>
                    </div>
                    <x-input type="search" wire:model.live="search" id="default-search" class="w-full !pl-10"
                        autocomplete="off" placeholder="Busca por Nombres, Apellidos o Documento" required />
                </div>
            </form>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Empleado" />
                        <x-th value="DNI Hijo" class="text-center" />
                        <x-th value="Nombres del Hijo" />
                        <x-th value="Fecha de Nacimiento" class="text-center" />
                        <x-th value="Edad" class="text-center" />
                        <x-th value="Está Estudiando" class="text-center" />
                        <x-th value="Acciones" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($asignaciones->count())
                        @foreach ($asignaciones as $indice => $asignacion)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td value="{{ $asignacion->empleado->nombreCompleto }}" />
                                <x-td value="{{ $asignacion->documento }}" class="text-center" />
                                <x-td value="{{ $asignacion->nombres }}" />
                                <x-td value="{{ $asignacion->fecha_nacimiento }}" class="text-center" />
                                <x-td value="{{ $asignacion->edad }}" class="text-center" />
                                <x-td class="text-center">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" value=""
                                            {{ $asignacion->esta_estudiando == 1 ? 'checked' : '' }} class="sr-only peer"
                                            wire:change="actualizarEstado({{ $asignacion->id }}, $event.target.checked)">
                                        <div
                                            class="relative w-11 h-6 bg-gray peer-focus:outline-none peer-focus:ring-0 rounded-full peer dark:bg-gray peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray peer-checked:bg-primary">
                                        </div>
                                    </label>
                                </x-td>
                                <x-td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-danger-button wire:click="confirmarEliminacion({{ $asignacion->id }})">
                                            <i class="fa fa-trash"></i>
                                        </x-danger-button>
                                    </div>

                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No Hay Familiares Registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
            <div class="mt-5">
                {{ $asignaciones->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
