<div>

    <x-card>
        <x-spacing>
            <div class="block md:flex items-center gap-5">
                <x-h2>
                    Empleados
                </x-h2>
                <div class="mt-5 md:mt-0" >
                    <livewire:empleado-form-component/>
                </div>
                
                <livewire:empleados-import-export-component wire:key="eleement" />
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
                        <x-th value="Nombres" />
                        <x-th value="Apellido Paterno" />
                        <x-th value="Apellido Materno" />
                        <x-th value="Documento" class="text-center" />
                        <x-th value="Acciones" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($empleados->count())
                        @foreach ($empleados as $indice => $empleado)
                            <x-tr>
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td value="{{ $empleado->nombres }}" />
                                <x-td value="{{ $empleado->apellido_paterno }}" />
                                <x-td value="{{ $empleado->apellido_materno }}" />
                                <x-td value="{{ $empleado->documento }}" class="text-center" />
                                <x-td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if ($empleado->status != 'activo')
                                            <x-warning-button wire:click="enable('{{ $empleado->code }}')"
                                                >
                                                <i class="fa fa-ban"></i>
                                            </x-warning-button>
                                        @else
                                            <x-success-button wire:click="disable('{{ $empleado->code }}')"
                                                >
                                                <i class="fa fa-check"></i>
                                            </x-success-button>
                                        @endif
                                        <x-button wire:click="editar('{{ $empleado->code }}')">
                                            <i class="fa fa-pencil"></i> <span
                                                class="hidden md:inline-block ml-2">Editar</span>
                                        </x-button>
                                        <x-danger-button wire:click="confirmarEliminacion('{{ $empleado->code }}')"
                                            >
                                            <i class="fa fa-remove"></i> <span
                                                class="hidden md:inline-block ml-2">Eliminar</span>
                                        </x-danger-button>
                                    </div>

                                </x-td>
                            </x-tr>
                        @endforeach
                    @else
                        <x-tr>
                            <x-td colspan="4">No hay Empleados registrados.</x-td>
                        </x-tr>
                    @endif
                </x-slot>
            </x-table>
            <div class="mt-5">
                {{ $empleados->links() }}
            </div>
        </x-spacing>
    </x-card>
</div>
