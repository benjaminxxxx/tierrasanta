<div>

    <x-card>
        <x-spacing>
            <div class="block md:flex items-center gap-5">
                <x-h2>
                    Empleados
                </x-h2>
                <div class="mt-5 md:mt-0">
                    <livewire:empleado-form-component />
                    <livewire:asignacion-familiar-form-component />
                </div>

                <livewire:empleados-import-export-component wire:key="eleement" />
            </div>
            <form class="md:flex my-10 gap-3">
                <div>
                    <x-label for="cargo_id">Cargo</x-label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-primary">
                            <i class="fa fa-search"></i>
                        </div>
                        <x-input type="search" wire:model.live="search" id="default-search" class="w-full !pl-10"
                            autocomplete="off" placeholder="Busca por Nombres, Apellidos o Documento" required />
                    </div>
                </div>

                <div>
                    <x-label for="cargo_id">Cargo</x-label>
                    <x-select class="uppercase" wire:model.live="cargo_id" id="cargo_id">
                        <option value="">TODOS</option>
                        @if ($cargos)
                            @foreach ($cargos as $cargo)
                                <option value="{{ $cargo->codigo }}">{{ $cargo->nombre }}</option>
                            @endforeach
                        @endif
                    </x-select>
                </div>
                <div>
                    <x-label for="descuento_sp_id">SPP o SNP</x-label>
                    <x-select class="uppercase" wire:model.live="descuento_sp_id" id="descuento_sp_id">
                        <option value="">TODOS</option>
                        @if ($descuentos)
                            @foreach ($descuentos as $descuento)
                                <option value="{{ $descuento->codigo }}">{{ $descuento->descripcion }}</option>
                            @endforeach
                        @endif
                    </x-select>
                </div>
                <div>
                    <x-label for="grupo_codigo">Grupo</x-label>
                    <x-select class="uppercase" wire:model.live="grupo_codigo" id="grupo_codigo">
                        <option value="">TODOS</option>
                        <option value="sg">SIN GRUPO</option>
                        @if ($grupos)
                            @foreach ($grupos as $grupo)
                                <option value="{{ $grupo->codigo }}">{{ $grupo->descripcion }}</option>
                            @endforeach
                        @endif
                    </x-select>
                </div>
                <div>
                    <x-label for="genero">Género</x-label>
                    <x-select class="uppercase" wire:model.live="genero" id="genero">
                        <option value="">TODOS</option>
                        <option value="F">MUJERES</option>
                        <option value="M">HOMBRES</option>
                    </x-select>
                </div>
                <div>
                    <x-label for="estado">Estado</x-label>
                    <x-select class="uppercase" wire:model.live="estado" id="estado">
                        <option value="">TODOS</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </x-select>
                </div>
            </form>
            <x-table class="mt-5">
                <x-slot name="thead">
                    <tr>
                        <x-th value="N°" class="text-center" />
                        <x-th value="Documento" class="text-center" />
                        <x-th value="Nombre Completo" />
                        <x-th value="Asignación Familiar" class="text-center" />
                        <x-th value="SNP/SPP" class="text-center" />
                        <x-th value="Cargo" class="text-center" />
                        <x-th value="Fech. Nac." class="text-center" />
                        <x-th value="Fech. Ingreso." class="text-center" />
                        <x-th value="Género" class="text-center" />
                        <x-th value="Acciones" class="text-center" />
                    </tr>
                </x-slot>
                <x-slot name="tbody">
                    @if ($empleados->count())
                        @foreach ($empleados as $indice => $empleado)
                            <x-tr style="background-color:{{ $empleado->grupo ? $empleado->grupo->color : '#ffffff' }}">
                                <x-th value="{{ $indice + 1 }}" class="text-center" />
                                <x-td value="{{ $empleado->documento }}" class="text-center" />
                                <x-td value="{{ $empleado->nombreCompleto }}" />
                                <x-td class="text-center">
                                    <x-secondary-button wire:click="asignacionFamiliar('{{ $empleado->code }}')">
                                        {{ $empleado->tieneAsignacionFamiliar['mensaje'] }}
                                    </x-secondary-button>
                                </x-td>
                                <x-td value="{{ $empleado->descuento_sp_id }}" class="text-center" />
                                <x-td value="{{ isset($empleado->cargo) ? $empleado->cargo->nombre : '-' }}"
                                    class="text-center" />
                                <x-td value="{{ $empleado->fecha_nacimiento }}" class="text-center" />
                                <x-td value="{{ $empleado->fecha_ingreso }}" class="text-center" />
                                <x-td value="{{ $empleado->genero }}" class="text-center" />
                                <x-td class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        @if ($empleado->status != 'activo')
                                            <x-warning-button wire:click="enable('{{ $empleado->code }}')">
                                                <i class="fa fa-ban"></i>
                                            </x-warning-button>
                                        @else
                                            <x-success-button wire:click="disable('{{ $empleado->code }}')">
                                                <i class="fa fa-check"></i>
                                            </x-success-button>
                                        @endif
                                        <x-button wire:click="editar('{{ $empleado->code }}')">
                                            <i class="fa fa-pencil"></i>
                                        </x-button>
                                        <x-danger-button wire:click="confirmarEliminacion('{{ $empleado->code }}')">
                                            <i class="fa fa-remove"></i>
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
