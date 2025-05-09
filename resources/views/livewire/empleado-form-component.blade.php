<div>
    <x-button type="button" wire:click="CrearEmpleado" class="w-full md:w-auto ">Registrar Empleado</x-button>

    <x-dialog-modal-header wire:model="isFormOpen" maxWidth="full">
        <x-slot name="title">
            <div class="flex items-center justify-between">
                <x-h3>
                    Registro de Empleado
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

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="nombres">Nombres</x-label>
                        <x-input type="text" wire:keydown.enter="store" wire:model="nombres" class="uppercase"
                            id="nombres" />
                        <x-input-error for="nombres" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="apellido_paterno">Apellido Paterno</x-label>
                        <x-input type="text" wire:keydown.enter="store" class="uppercase"
                            wire:model="apellido_paterno" id="apellido_paterno" />
                        <x-input-error for="apellido_paterno" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="apellido_materno">Apellido Materno</x-label>
                        <x-input type="text" wire:keydown.enter="store" class="uppercase"
                            wire:model="apellido_materno" id="apellido_materno" />
                        <x-input-error for="apellido_materno" />
                    </div>

                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="documento">Documento</x-label>
                        <x-input type="text" wire:keydown.enter="store" class="uppercase" wire:model="documento"
                            id="documento" />
                        <x-input-error for="documento" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="descuento_sp_id">Sistema de Pension</x-label>
                        <x-select class="uppercase" wire:model="descuento_sp_id" id="descuento_sp_id">
                            <option value="">No Afiliado a Ningún Sistema de Pensiones</option>
                            @if ($descuentos)
                                @foreach ($descuentos as $descuento)
                                    <option value="{{ $descuento->codigo }}">{{ $descuento->descripcion }}</option>
                                @endforeach
                            @endif
                        </x-select>
                        <x-input-error for="descuento_sp_id" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="grupo_codigo">Grupo</x-label>
                        <x-select class="uppercase" wire:model="grupo_codigo" id="grupo_codigo">
                            <option value="">SIN GRUPO</option>
                            @if ($grupos)
                                @foreach ($grupos as $grupo)
                                    <option value="{{ $grupo->codigo }}">{{ $grupo->descripcion }}</option>
                                @endforeach
                            @endif
                        </x-select>
                        <x-input-error for="grupo_codigo" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="genero">Género</x-label>
                        <x-select class="uppercase" wire:model="genero" id="genero">
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </x-select>
                        <x-input-error for="sistema_pension" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="cargo_id">Cargo</x-label>
                        <x-select class="uppercase" wire:model="cargo_id" id="cargo_id">
                            @if ($cargos)
                                @foreach ($cargos as $cargo)
                                    <option value="{{ $cargo->codigo }}">{{ $cargo->nombre }}</option>
                                @endforeach
                            @endif
                        </x-select>
                        <x-input-error for="sistema_pension" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="tipo_planilla">Tipo de planilla</x-label>
                        <x-select class="uppercase" wire:model="tipo_planilla" id="tipo_planilla_id">
                            <option value="">Seleccione el tipo de planilla</option>
                            <option value="1">Planilla Agraria</option>
                            <option value="2">Planilla Oficina</option>
                        </x-select>
                        <x-input-error for="tipo_planilla" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="fecha_nacimiento">Fecha de Nacimiento</x-label>
                        <x-input type="date" autocomplete="off" wire:model="fecha_nacimiento" class="uppercase"
                            id="fecha_nacimiento" />
                        <x-input-error for="fecha_nacimiento" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="fecha_ingreso">Fecha de Ingreso</x-label>
                        <x-input type="date" autocomplete="off" wire:model="fecha_ingreso" class="uppercase"
                            id="fecha_ingreso" />
                        <x-input-error for="fecha_ingreso" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="salario">Salario Base</x-label>
                        <x-input type="number" autocomplete="off" wire:model="salario" class="uppercase"
                            id="salario" />
                        <x-input-error for="salario" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="compensacion_vacacional">Compensación Vacacional</x-label>
                        <x-input type="number" autocomplete="off" wire:model="compensacion_vacacional" class="uppercase"
                            id="compensacion_vacacional" />
                        <x-input-error for="compensacion_vacacional" />
                    </div>
                    <div class="col-span-2 md:col-span-1 mt-3">
                        <x-label for="esta_jubilado">¿Está Jubilado(a)?</x-label>
                        <x-label for="esta_jubilado" class="mt-4">
                            <x-checkbox wire:model="esta_jubilado" id="esta_jubilado" class="mr-2" />
                            Está Jubilado(a)
                        </x-label>
                        <x-input-error for="esta_jubilado" />
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" wire:click="closeForm" class="mr-2">Cancelar</x-secondary-button>
            <x-button type="submit" wire:click="store" class="ml-3">Guardar</x-button>
        </x-slot>
    </x-dialog-modal-header>
</div>
