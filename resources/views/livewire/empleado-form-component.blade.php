<div>
    <x-button type="button" wire:click="abrirFormularioNuevoEmpleado">
        <i class="fa fa-plus"></i> Registrar empleado
    </x-button>

    <x-dialog-modal wire:model="isFormOpen" maxWidth="full">
        <x-slot name="title">
            <x-h3>
                Registro de Empleado
            </x-h3>
        </x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="store">
                <div class="mt-5">
                    <x-tabs defaultValue="informacion_general" storageKey="contratos" :remember="false">

                        <x-tabs-list class="mb-4">
                            <x-tabs-trigger value="informacion_general">Información Personal</x-tabs-trigger>
                            @if($empleadoId)
                                <x-tabs-trigger value="contrato" x-show="empleadoId!=null">Contratos</x-tabs-trigger>
                            @endif
                        </x-tabs-list>


                        <x-tabs-content value="informacion_general">

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                                <x-group-field>
                                    <x-label for="nombres">Nombres</x-label>
                                    <x-input type="text" wire:keydown.enter="store" wire:model="nombres"
                                        class="uppercase" id="nombres" />
                                    <x-input-error for="nombres" />
                                </x-group-field>

                                <x-group-field>
                                    <x-label for="apellido_paterno">Apellido Paterno</x-label>
                                    <x-input type="text" wire:keydown.enter="store" class="uppercase"
                                        wire:model="apellido_paterno" id="apellido_paterno" />
                                    <x-input-error for="apellido_paterno" />
                                </x-group-field>

                                <x-group-field>
                                    <x-label for="apellido_materno">Apellido Materno</x-label>
                                    <x-input type="text" wire:keydown.enter="store" class="uppercase"
                                        wire:model="apellido_materno" id="apellido_materno" />
                                    <x-input-error for="apellido_materno" />
                                </x-group-field>

                                <x-group-field>
                                    <x-label for="documento">Documento</x-label>
                                    <x-input type="text" wire:keydown.enter="store" class="uppercase"
                                        wire:model="documento" id="documento" />
                                    <x-input-error for="documento" />
                                </x-group-field>

                                <x-select class="uppercase" label="Género" wire:model="genero" id="genero"
                                    class="w-full">
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </x-select>

                                <x-group-field>
                                    <x-label for="fecha_nacimiento">Fecha de Nacimiento</x-label>
                                    <x-input type="date" autocomplete="off" wire:model="fecha_nacimiento"
                                        class="uppercase" id="fecha_nacimiento" />
                                    <x-input-error for="fecha_nacimiento" />
                                </x-group-field>
                                <x-group-field>
                                    <x-label for="fecha_ingreso">Fecha de Ingreso</x-label>
                                    <x-input type="date" autocomplete="off" wire:model="fecha_ingreso" class="uppercase"
                                        id="fecha_ingreso" />
                                    <x-input-error for="fecha_ingreso" />
                                </x-group-field>
                                @if(!$empleadoId)
                                    <div class="col-span-3">
                                        <x-label>
                                            Información del primer contrato
                                        </x-label>
                                    </div>
                                    <x-group-field>
                                        <x-label for="fecha_inicio">Fecha de vigencia</x-label>
                                        <x-input type="date" wire:model="fecha_inicio" />
                                        <x-input-error for="fecha_inicio" />
                                    </x-group-field>
                                    <x-select class="uppercase" label="Sistema de Pension" wire:model="descuento_sp_id"
                                        error="descuento_sp_id" id="descuento_sp_id" class="w-full">
                                        <option value="">No Afiliado</option>
                                        @if ($descuentos)
                                            @foreach ($descuentos as $descuento)
                                                <option value="{{ $descuento->codigo }}">{{ $descuento->descripcion }}</option>
                                            @endforeach
                                        @endif
                                    </x-select>
                                    <x-select class="uppercase" label="Grupo" wire:model="grupo_codigo" id="grupo_codigo"
                                        class="w-full">
                                        <option value="">SIN GRUPO</option>
                                        @if ($grupos)
                                            @foreach ($grupos as $grupo)
                                                <option value="{{ $grupo->codigo }}">{{ $grupo->descripcion }}</option>
                                            @endforeach
                                        @endif
                                    </x-select>
                                    <x-select class="uppercase" label="Cargo" wire:model="cargo_codigo" id="cargo_codigo"
                                        class="w-full">
                                        <option value="">SIN CARGO</option>
                                        @if ($cargos)
                                            @foreach ($cargos as $cargo)
                                                <option value="{{ $cargo->codigo }}">{{ $cargo->nombre }}</option>
                                            @endforeach
                                        @endif
                                    </x-select>
                                    <x-select class="uppercase" label="Tipo de planilla" wire:model="tipo_planilla"
                                        id="tipo_planilla_id" class="w-full" error="tipo_planilla">
                                        <option value="">Seleccione el tipo de planilla</option>
                                        <option value="1">Planilla Agraria</option>
                                        <option value="2">Planilla Oficina</option>
                                    </x-select>

                                    <x-group-field>
                                        <x-label for="sueldo">Salario</x-label>
                                        <x-input type="number" wire:model="sueldo" class="uppercase" />
                                        <x-input-error for="sueldo" />
                                    </x-group-field>
                                    <x-group-field>
                                        <x-label for="compensacion_vacacional">Compensación Vacacional</x-label>
                                        <x-input type="number" autocomplete="off" wire:model="compensacion_vacacional"
                                            class="uppercase" id="compensacion_vacacional" />
                                        <x-input-error for="compensacion_vacacional" />
                                    </x-group-field>

                                    <x-select class="uppercase" label="Modalidad de Pago" wire:model="modalidad_pago"
                                        id="modalidad_pago" class="w-full">
                                        <option value="">Modalidad de pago</option>
                                        <option value="mensual">Mensual</option>
                                        <option value="quincenal">Quincenal</option>
                                    </x-select>

                                    <x-group-field>
                                        <x-label for="esta_jubilado">¿Está Jubilado(a)?</x-label>
                                        <x-label for="esta_jubilado" class="mt-4">
                                            <x-checkbox wire:model="esta_jubilado" id="esta_jubilado" class="mr-2" />
                                            Está Jubilado(a)
                                        </x-label>
                                        <x-input-error for="esta_jubilado" />
                                    </x-group-field>
                                @endif
                            </div>
                        </x-tabs-content>

                        <x-tabs-content value="contrato">
                            <x-table>
                                <x-slot name="thead">
                                    <x-tr>
                                        <x-th>N°</x-th>
                                        <x-th>Tipo Contrato</x-th>
                                        <x-th>Fecha Inicio</x-th>
                                        <x-th>Fecha Fin</x-th>
                                        <x-th>Sueldo</x-th>
                                        <x-th>Cargo Código</x-th>
                                        <x-th>Grupo Código</x-th>
                                        <x-th>Comp. Vac.</x-th>
                                        <x-th>Tipo Planilla</x-th>
                                        <x-th>Desc. SP</x-th>
                                        <x-th>Jubilado</x-th>
                                        <x-th>Moda. Pago</x-th>
                                        <x-th>Motivo Despido</x-th>
                                        <x-th>Acciones</x-th>
                                    </x-tr>
                                </x-slot>

                                <x-slot name="tbody">
                                    @foreach ($contratos as $indice => $contrato)
                                        <x-tr>
                                            <x-th>{{ $indice + 1 }}</x-th>
                                            <x-td>{{ $contrato->tipo_contrato }}</x-td>
                                            <x-td>{{ formatear_fecha($contrato->fecha_inicio) }}</x-td>
                                            <x-td>{{ formatear_fecha($contrato->fecha_fin ?? '-') }}</x-td>
                                            <x-td>{{ formatear_numero($contrato->sueldo) }}</x-td>
                                            <x-td>{{ $contrato->cargo_codigo }}</x-td>
                                            <x-td>{{ $contrato->grupo_codigo }}</x-td>
                                            <x-td>{{ formatear_numero($contrato->compensacion_vacacional) }}</x-td>
                                            <x-td>{{ $contrato->tipo_planilla }}</x-td>
                                            <x-td>{{ $contrato->descuento_sp_id }}</x-td>
                                            <x-td>{{ $contrato->esta_jubilado ? 'Sí' : 'No' }}</x-td>
                                            <x-td>{{ ucfirst($contrato->modalidad_pago) }}</x-td>
                                            <x-td>{{ $contrato->motivo_despido ?? '-' }}</x-td>
                                            <x-td>
                                                <x-danger-button wire:confirm="Está seguro que desea eliminar el contrato?"
                                                    wire:click="eliminarContrato({{ $contrato->id }})">
                                                    <i class="fa fa-trash"></i>
                                                </x-danger-button>


                                            </x-td>
                                        </x-tr>
                                    @endforeach
                                </x-slot>
                            </x-table>
                            <div class="w-full lg:w-1/2">
                                <x-label class="mt-5">
                                    Para actualizar el sueldo, modalidad de contrato o más información, debe generar un
                                    nuevo contrato para que este vigente a partir de la nueva fecha
                                </x-label>
                                <x-button class="mt-2" wire:click="$set('mostrarFormularioContrato', true)"
                                    wire:loading.attr="disabled">
                                    <i class="fa fa-plus"></i> Nuevo contrato
                                </x-button>
                            </div>
                        </x-tabs-content>
                    </x-tabs>
                </div>

            </form>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" @click="$wire.set('isFormOpen',false)"
                class="mr-2">Cancelar</x-secondary-button>
            <x-button type="submit" wire:click="store" class="ml-3">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model="mostrarFormularioContrato" maxWidth="lg">
        <x-slot name="title">
            <x-h3>
                Registro de Contrato
            </x-h3>
        </x-slot>
        <x-slot name="content">
            <x-label>
                El sueldo del empleado ahora tendra un nuevo sueldo que sera válido a partir de la fecha de vigencia
            </x-label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
                <x-group-field>
                    <x-label for="fecha_inicio">Fecha de vigencia</x-label>
                    <x-input type="date" wire:model="fecha_inicio" />
                    <x-input-error for="fecha_inicio" />
                </x-group-field>
                <x-select class="uppercase" label="Sistema de Pension" wire:model="descuento_sp_id"
                    error="descuento_sp_id" id="descuento_sp_id" class="w-full">
                    <option value="">No Afiliado</option>
                    @if ($descuentos)
                        @foreach ($descuentos as $descuento)
                            <option value="{{ $descuento->codigo }}">{{ $descuento->descripcion }}</option>
                        @endforeach
                    @endif
                </x-select>
                <x-select class="uppercase" label="Grupo" wire:model="grupo_codigo" id="grupo_codigo" class="w-full">
                    <option value="">SIN GRUPO</option>
                    @if ($grupos)
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo->codigo }}">{{ $grupo->descripcion }}</option>
                        @endforeach
                    @endif
                </x-select>
                <x-select class="uppercase" label="Cargo" wire:model="cargo_codigo" id="cargo_codigo" class="w-full">
                    @if ($cargos)
                        @foreach ($cargos as $cargo)
                            <option value="{{ $cargo->codigo }}">{{ $cargo->nombre }}</option>
                        @endforeach
                    @endif
                </x-select>
                <x-select class="uppercase" label="Tipo de planilla" wire:model="tipo_planilla" id="tipo_planilla_id"
                    class="w-full">
                    <option value="">Seleccione el tipo de planilla</option>
                    <option value="1">Planilla Agraria</option>
                    <option value="2">Planilla Oficina</option>
                </x-select>

                <x-group-field>
                    <x-label for="sueldo">Salario</x-label>
                    <x-input type="number" wire:model="sueldo" class="uppercase" />
                    <x-input-error for="sueldo" />
                </x-group-field>
                <x-group-field>
                    <x-label for="compensacion_vacacional">Compensación Vacacional</x-label>
                    <x-input type="number" autocomplete="off" wire:model="compensacion_vacacional" class="uppercase"
                        id="compensacion_vacacional" />
                    <x-input-error for="compensacion_vacacional" />
                </x-group-field>

                <x-select class="uppercase" label="Modalidad de Pago" wire:model="modalidad_pago" id="modalidad_pago"
                    class="w-full">
                    <option value="">Modalidad de pago</option>
                    <option value="mensual">Mensual</option>
                    <option value="quincenal">Quincenal</option>
                </x-select>

                <x-group-field>
                    <x-label for="esta_jubilado">¿Está Jubilado(a)?</x-label>
                    <x-label for="esta_jubilado" class="mt-4">
                        <x-checkbox wire:model="esta_jubilado" id="esta_jubilado" class="mr-2" />
                        Está Jubilado(a)
                    </x-label>
                    <x-input-error for="esta_jubilado" />
                </x-group-field>

            </div>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button type="button" @click="$wire.set('mostrarFormularioContrato',false)"
                class="mr-2">Cancelar</x-secondary-button>
            <x-button type="submit" wire:click="guardarContrato" class="ml-3">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>