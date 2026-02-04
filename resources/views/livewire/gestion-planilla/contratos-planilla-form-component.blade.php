<div x-data="contratoPlanillaForm">

    <x-dialog-modal wire:model.live="mostrarFormulario" maxWidth="2xl">
        <x-slot name="title">
            {{ $esEdicion ? 'Editar Contrato' : 'Nuevo Contrato' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6">

                <div>

                    <div x-show="!esEdicion">
                        <x-searchable-select :options="$empleados" search-placeholder="Buscar trabajador..."
                            wire:model.live="plan_empleado_id" />
                    </div>
                    @if ($contrato)
                        <div class="mt-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Empleado: {{ $contrato->empleado->nombre_completo ?? 'No disponible' }}
                            </p>
                        </div>
                    @endif
                    @if (count($contratosAbiertos) > 0)
                        <x-warning class="my-4">
                            <h4 class="mb-2">
                                El empleado tiene contratos sin finalizar
                            </h4>
                            <p class="text-smmb-4">Debe cerrar los siguientes contratos para poder
                                registrar uno nuevo:</p>

                            <div class="mt-4">
                                @foreach ($contratosAbiertos as $contrato)
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <label for="" class="dark:text-white">Inicio: {{ $contrato->fecha_inicio }}</label>
                                            <x-input type="date"
                                                wire:model="datosCierre.{{ $contrato->id }}.fecha_fin" />
                                        </div>
                                        <div>
                                            <label for="" class="dark:text-white">Motivo Cese (SUNAT)</label>
                                            <x-select wire:model="datosCierre.{{ $contrato->id }}.motivo_cese_sunat"
                                                class="w-full text-sm">
                                                <option value="">Seleccione...</option>
                                                <option value="01">Renuncia</option>
                                                <option value="02">Despido</option>
                                            </x-select>
                                        </div>
                                        <div>
                                            <label for="" class="dark:text-white">Comentario</label>
                                            <x-input type="text"
                                                wire:model="datosCierre.{{ $contrato->id }}.comentario_cese" />
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        </x-warning>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <x-select label="Tipo de Contrato *" wire:model="tipo_contrato" error="tipo_contrato"
                        class="w-full">
                        <option value="">Seleccionar tipo</option>
                        <option value="plazo fijo">PLAZO FIJO</option>
                        <option value="indefinido">INDEFINIDO</option>
                        <option value="temporal">TEMPORAL</option>
                    </x-select>

                    <x-input id="fecha_inicio" label="Fecha de Inicio *" type="date" class=""
                        wire:model="fecha_inicio" error="fecha_inicio" />



                    <x-input id="fecha_fin_prueba" label="Fecha Fin de Prueba" type="date" class="mt-1 block w-full"
                        wire:model="fecha_fin_prueba" />



                    <x-select-planilla-cargos label="Cargo" wire:model="cargo_codigo" id="cargo_codigo"
                        error="cargo_codigo" />

                    <x-select-planilla-grupos label="Grupo" textoTodos="SELECCIONAR" wire:model="grupo_codigo"
                        error="grupo_codigo" />


                    <x-select class="uppercase" label="Tipo de planilla" wire:model="tipo_planilla" id="tipo_planilla"
                        class="!w-full uppercase" error="tipo_planilla">
                        <option value="">SELECCIONAR</option>
                        <option value="agraria">AGRARIA</option>
                        <option value="oficina">OFICINA</option>
                    </x-select>

                    <x-select-planilla-descuentos label="Sistema de Pensión" textTodos="NO AFILIADO"
                        wire:model="plan_sp_codigo" error="plan_sp_codigo" />

                    <x-input id="compensacion_vacacional" label="Compensación Vacacional" type="number" step="0.01"
                        class="mt-1 block w-full" wire:model="compensacion_vacacional" placeholder="Monto en soles" />

                    <x-select label="Modalidad de Pago *" wire:model="modalidad_pago" error="modalidad_pago">
                        <option value="">SELECCIONAR</option>
                        <option value="mensual">MENSUAL</option>
                        <option value="quincenal">QUINCENAL</option>
                        <option value="semanal">SEMANAL</option>
                    </x-select>

                    <x-group-field>
                        <x-label for="esta_jubilado">¿Está Jubilado(a)?</x-label>
                        <div class="flex items-center mt-2">
                            <x-checkbox wire:model="esta_jubilado" id="esta_jubilado" class="mr-2" />
                            <span>Sí, está jubilado(a)</span>
                        </div>
                    </x-group-field>
                </div>

                <!-- Mensajes de Error -->
                @if ($errors->any())
                    <x-warning>
                        <div class="text-sm">
                            <p class="font-medium mb-2">Por favor, corrige los siguientes errores:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </x-warning>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="cerrarFormulario">
                Cancelar
            </x-button>
            <x-button wire:click="guardarContrato" wire:loading.attr="disabled">
                <i class="fas fa-save"></i> {{ $esEdicion ? 'Actualizar Contrato' : 'Crear Contrato' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>


    <x-loading wire:loading />
</div>
@script
    <script>
        Alpine.data('contratoPlanillaForm', () => ({
            esEdicion: @entangle('esEdicion')
        }));
    </script>
@endscript
