<div>
    <x-dialog-modal wire:model="mostrarFormularioEmpleadoContrato" maxWidth="full">
        <x-slot name="title">
            <x-h3>Contratos del Empleado</x-h3>
        </x-slot>

        <x-slot name="content">

            {{-- 🔹 Tabla de contratos existentes --}}
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-300 rounded-lg">
                    <thead class="bg-gray-50 text-gray-700 uppercase text-xs">
                        <tr>
                            <th class="px-3 py-2 text-center">Inicio</th>
                            <th class="px-3 py-2 text-center">Fin</th>
                            <th class="px-3 py-2 text-center">Cargo</th>
                            <th class="px-3 py-2 text-center">Grupo</th>
                            <th class="px-3 py-2 text-center">Planilla</th>
                            <th class="px-3 py-2 text-center">Modalidad</th>
                            <th class="px-3 py-2 text-center">SP</th>
                            <th class="px-3 py-2 text-center">Comp. Vac.</th>
                            <th class="px-3 py-2 text-center">Tipo Cont.</th>
                            <th class="px-3 py-2 text-center">Jubilado</th>
                            <th class="px-3 py-2 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse ($contratos as $contrato)
                            <tr class="{{ $loop->last ? '' : 'border-b' }}">
                                <td class="px-3 py-2">{{ $contrato->fecha_inicio }}</td>
                                <td class="px-3 py-2">{{ $contrato->fecha_fin ?? '—' }}</td>
                                <td class="px-3 py-2 uppercase">{{ $contrato->cargo?->nombre }}</td>
                                <td class="px-3 py-2 uppercase">{{ $contrato->grupo?->descripcion }}</td>
                                <td class="px-3 py-2 uppercase">{{ $contrato->tipo_planilla }}</td>
                                <td class="px-3 py-2 uppercase">{{ $contrato->modalidad_pago }}</td>
                                <td class="px-3 py-2 uppercase">{{ $contrato->descuento?->descripcion ?? 'No Afiliado' }}</td>
                                <td class="px-3 py-2 uppercase">{{ $contrato->compensacion_vacacional }}</td>
                                <td class="px-3 py-2 uppercase">{{ $contrato->tipo_contrato }}</td>
                                <td class="px-3 py-2 text-center">
                                    {{ $contrato->esta_jubilado ? 'Sí' : 'No' }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <x-button variant="danger" wire:click="eliminarContrato('{{ $contrato->id }}')" size="xs"
                                        wire:confirm="¿Desea eliminar este contrato?">
                                        <i class="fa fa-trash"></i>
                                    </x-button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-gray-500 py-3">No se encontraron contratos registrados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 🔹 Formulario para agregar nuevo contrato --}}
            <x-card class="bg-gray-50 border border-gray-300 p-4">
                <x-h4 class="mb-3">Registrar nuevo contrato</x-h4>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <x-group-field>
                        <x-label for="fechaInicio">Fecha de vigencia</x-label>
                        <x-input type="date" wire:model="fechaInicio" />
                        <x-input-error for="fecha_inicio" />
                    </x-group-field>

                    <x-select-planilla-descuentos label="Sistema de Pensión" textTodos="NO AFILIADO"
                        wire:model="planSpCodigo" class="!w-full" error="plan_sp_codigo" />

                    <x-select-planilla-grupos label="Grupo" textoTodos="SELECCIONAR" wire:model="grupoCodigo"
                        class="!w-full" />

                    <x-select-planilla-cargos label="Cargo" wire:model="cargoCodigo" id="cargoCodigo"
                        class="!w-full" />

                    <x-select class="uppercase" label="Tipo de planilla" wire:model="tipoPlanilla" id="tipoPlanilla"
                        class="!w-full uppercase" error="tipo_planilla">
                        <option value="">SELECCIONAR</option>
                        <option value="agraria">AGRARIA</option>
                        <option value="oficina">OFICINA</option>
                    </x-select>

                    <x-select class="uppercase" label="Tipo de contrato" wire:model="tipoContrato" id="tipoContrato"
                        class="!w-full uppercase" error="tipo_contrato">
                        <option value="">SELECCIONAR</option>
                        <option value="plazo fijo">PLAZO FIJO</option>
                        <option value="indefinido">INDEFINIDO</option>
                        <option value="temporal">TEMPORAL</option>
                    </x-select>

                    <x-group-field>
                        <x-label for="compensacionVacacional">Compensación Vacacional</x-label>
                        <x-input type="number" step="0.01" wire:model="compensacionVacacional" id="compensacionVacacional" />
                        <x-input-error for="compensacionVacacional" />
                    </x-group-field>

                    <x-select label="Modalidad de Pago" wire:model="modalidadPago" id="modalidadPago"
                        class="!w-full uppercase" error="modalidad_pago">
                        <option value="">Modalidad de pago</option>
                        <option value="mensual">Mensual</option>
                        <option value="quincenal">Quincenal</option>
                    </x-select>

                    <x-group-field>
                        <x-label for="estaJubilado">¿Está Jubilado(a)?</x-label>
                        <div class="flex items-center mt-2">
                            <x-checkbox wire:model="estaJubilado" id="estaJubilado" class="mr-2" />
                            <span>Sí, está jubilado(a)</span>
                        </div>
                    </x-group-field>

                </div>
            </x-card>

        </x-slot>

        <x-slot name="footer">
            <x-flex>
                <x-button type="button" variant="secondary"
                    @click="$wire.set('mostrarFormularioEmpleadoContrato', false)">Cerrar</x-button>

                <x-button type="submit" wire:click="guardarContrato">
                    <i class="fa fa-save"></i> Guardar nuevo contrato
                </x-button>
            </x-flex>
        </x-slot>
    </x-dialog-modal>

    <x-loading wire:loading />
</div>
