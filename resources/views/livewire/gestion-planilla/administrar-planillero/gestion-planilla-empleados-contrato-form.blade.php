<div>


    <x-dialog-modal wire:model="mostrarFormularioEmpleadoContrato" maxWidth="lg">
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


                <x-select-planilla-descuentos label="Sistema de Pensión" textTodos="NO AFILIADDO"
                    wire:model="descuento_sp_id" class="!w-full" />

                <x-select-planilla-grupos label="Grupo" textoTodos="SELECCIONAR" wire:model="planGrupoCodigo"
                    class="!w-full" />

                <x-select-planilla-cargos label="Cargo" wire:model="cargo_codigo" id="cargo_codigo" class="!w-full" />

                <x-select class="uppercase" label="Tipo de planilla" wire:model="tipo_planilla" id="tipo_planilla_id"
                    class="!w-full uppercase">
                    <option value="">SELECCIONAR</option>
                    <option value="agraria">AGRARIA</option>
                    <option value="oficina">OFICINA</option>
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

                <x-select label="Modalidad de Pago" wire:model="modalidad_pago" id="modalidad_pago"
                    class="!w-full uppercase">
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
            <x-flex>
                <x-button type="button" variant="secondary"
                    @click="$wire.set('mostrarFormularioEmpleadoContrato',false)">Cancelar</x-button>
                <x-button type="submit" wire:click="guardarContrato">
                    <i class="fa fa-save"></i> Guardar
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading />
</div>