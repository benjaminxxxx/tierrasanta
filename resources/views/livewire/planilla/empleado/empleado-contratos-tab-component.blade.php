<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    {{-- Historial --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <x-subtitle>Historial de contratos</x-subtitle>
            <x-button size="sm" wire:click="nuevoContrato"><i class="fa fa-plus"></i> Nuevo contrato</x-button>
        </div>

        <x-table>
            <x-slot name="thead">
                <x-tr>
                    <x-th>Tipo</x-th>
                    <x-th>Inicio</x-th>
                    <x-th>Fin</x-th>
                    <x-th>Grupo</x-th>
                    <x-th>Estado</x-th>
                    <x-th class="text-right">Acciones</x-th>
                </x-tr>
            </x-slot>
            <x-slot name="tbody">
                @forelse ($historial as $contrato)
                    <x-tr wire:key="contrato-{{ $contrato->id }}">
                        <x-td class="uppercase text-xs">{{ $contrato->tipo_contrato }}</x-td>
                        <x-td>{{ $contrato->fecha_inicio->format('d/m/Y') }}</x-td>
                        <x-td>{{ $contrato->fecha_fin?->format('d/m/Y') ?? '—' }}</x-td>
                        <x-td>{{ $contrato->grupo_codigo ?? '—' }}</x-td>
                        <x-td>
                            @if ($contrato->estado === 'finalizado')
                                <span class="px-2 py-0.5 rounded text-xs bg-muted text-muted-foreground">Finalizado</span>
                            @elseif ($contrato->estado === 'en_prueba')
                                <span class="px-2 py-0.5 rounded text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">En prueba</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">Activo</span>
                            @endif
                        </x-td>
                        <x-td class="text-right">
                            <div class="flex justify-end gap-2">
                                @if ($contrato->estado !== 'finalizado')
                                    <x-button size="xs" variant="secondary" wire:click="editarContrato({{ $contrato->id }})">Editar</x-button>
                                    <x-button size="xs" variant="danger" wire:click="abrirFinalizar({{ $contrato->id }})">Finalizar</x-button>
                                @elseif ($historial->first()?->id === $contrato->id)
                                    <x-button size="xs" variant="secondary" wire:click="reabrirContrato({{ $contrato->id }})"
                                        wire:confirm="¿Reaperturar este contrato?">Reaperturar</x-button>
                                @endif
                            </div>
                        </x-td>
                    </x-tr>

                    @if ($contratoAFinalizarId === $contrato->id)
                        <x-tr>
                            <x-td colspan="6">
                                <div class="p-3 rounded border border-amber-300 bg-amber-50 dark:bg-amber-950 space-y-3">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <x-input type="date" label="Fecha de fin" wire:model="datosCierre.fecha_fin" error="datosCierre.fecha_fin" />
                                        <x-select label="Motivo cese (SUNAT)" wire:model="datosCierre.motivo_cese_sunat" error="datosCierre.motivo_cese_sunat">
                                            <option value="">Seleccione...</option>
                                            @foreach (\App\Services\Planilla\Empleado\EmpleadoPerfilServicio::MOTIVOS_CESE as $codigo => $texto)
                                                <option value="{{ $codigo }}">{{ $texto }}</option>
                                            @endforeach
                                        </x-select>
                                        <x-input type="text" label="Comentario" wire:model="datosCierre.comentario_cese" />
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <x-button size="sm" variant="secondary" wire:click="$set('contratoAFinalizarId', null)">Cancelar</x-button>
                                        <x-button size="sm" wire:click="confirmarFinalizar">Confirmar cierre</x-button>
                                    </div>
                                </div>
                            </x-td>
                        </x-tr>
                    @endif
                @empty
                    <x-tr>
                        <x-td colspan="6" class="py-6 text-center text-muted-foreground">Este empleado no tiene contratos registrados.</x-td>
                    </x-tr>
                @endforelse
            </x-slot>
        </x-table>
    </div>

    {{-- Formulario --}}
    <div>
        @if ($mostrarForm)
            <x-subtitle>{{ $esEdicion ? 'Editar contrato' : 'Nuevo contrato' }}</x-subtitle>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                <x-select label="Tipo de Contrato *" wire:model="tipo_contrato" error="tipo_contrato">
                    <option value="">Seleccionar tipo</option>
                    <option value="plazo fijo">PLAZO FIJO</option>
                    <option value="indefinido">INDEFINIDO</option>
                    <option value="temporal">TEMPORAL</option>
                </x-select>

                <x-input label="Fecha de Inicio *" type="date" wire:model="fecha_inicio" error="fecha_inicio" />
                <x-input label="Fecha Fin de Prueba" type="date" wire:model="fecha_fin_prueba" />

                <x-select-planilla-grupos label="Grupo" textoTodos="SELECCIONAR" wire:model="grupo_codigo" error="grupo_codigo" />

                <x-select label="Tipo de planilla" wire:model="tipo_planilla" error="tipo_planilla" class="uppercase">
                    <option value="">SELECCIONAR</option>
                    <option value="agraria">AGRARIA</option>
                    <option value="oficina">OFICINA</option>
                    <option value="general">GENERAL</option>
                    <option value="mype">MYPE</option>
                    <option value="construccion">CONSTRUCCIÓN</option>
                </x-select>

                <x-select-planilla-descuentos label="Sistema de Pensión" textTodos="NO AFILIADO" wire:model="plan_sp_codigo" error="plan_sp_codigo" />

                <x-input label="Remuneración Básica (opcional)" type="number" step="0.1" wire:model="remuneracion_basica"
                    placeholder="Monto en soles" help="Colocar si tiene un sueldo fijo personalizado en el plame" />

                <x-input label="Bonificación" type="number" step="0.1" wire:model="bonificacion" placeholder="Monto en soles"
                    help="Se suma a la remuneración básica mensual (0121 del PLAME) y entra al costo" />

                <x-input label="Compensación Vacacional" type="number" step="0.01" wire:model="compensacion_vacacional" placeholder="Monto en soles" />

                <x-select label="Modalidad de Pago *" wire:model="modalidad_pago" error="modalidad_pago">
                    <option value="">SELECCIONAR</option>
                    <option value="mensual">MENSUAL</option>
                    <option value="quincenal">QUINCENAL</option>
                    <option value="semanal">SEMANAL</option>
                </x-select>

                <x-group-field>
                    <x-label for="esta_jubilado_{{ $empleadoId }}">¿Está Jubilado(a)?</x-label>
                    <div class="flex items-center mt-2">
                        <x-checkbox wire:model="esta_jubilado" id="esta_jubilado_{{ $empleadoId }}" class="mr-2" />
                        <span>Sí, está jubilado(a)</span>
                    </div>
                </x-group-field>
            </div>

            {{-- Cómo y dónde se le paga (lo usa la planilla oficina y su hoja ADM) --}}
            <div class="mt-5 border-t border-border pt-4">
                <h4 class="text-sm font-semibold text-foreground mb-1">Pago</h4>
                <p class="text-xs text-muted-foreground mb-3">
                    Planilla (5ta categoría) o recibo por honorarios (4ta). La cuenta principal recibe lo de planilla (blanco); la secundaria, la diferencia.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <x-select label="Forma de ingreso" wire:model.live="tipo_ingreso">
                        <option value="planilla">PLANILLA (5ta categoría)</option>
                        <option value="honorarios">RECIBO POR HONORARIOS (4ta)</option>
                    </x-select>
                    @if ($tipo_ingreso === 'honorarios')
                        <x-group-field>
                            <x-label>Retención de 4ta</x-label>
                            <label class="flex items-center gap-2 mt-2 text-sm">
                                <x-checkbox wire:model="suspension_cuarta" /> Tiene suspensión de retenciones (no se le retiene el 8%)
                            </label>
                        </x-group-field>
                    @else
                        <x-select label="CTS y gratificación" wire:model="beneficios_mensuales">
                            <option value="0">EN DOS TRAMOS (may/nov y jul/dic)</option>
                            <option value="1">CADA MES CON EL SUELDO</option>
                        </x-select>
                    @endif
                    <x-select label="Método de pago" wire:model="metodo_pago">
                        <option value="">SELECCIONAR</option>
                        <option value="transferencia">TRANSFERENCIA</option>
                        <option value="efectivo">EFECTIVO</option>
                        <option value="cheque">CHEQUE</option>
                    </x-select>

                    <x-input label="Banco (cuenta principal)" wire:model="banco" placeholder="BCP, BBVA, Interbank…" />
                    <div class="grid grid-cols-2 gap-2">
                        <x-select label="Tipo de cuenta" wire:model="tipo_cuenta">
                            <option value="">—</option>
                            <option value="ahorros">AHORROS</option>
                            <option value="corriente">CORRIENTE</option>
                        </x-select>
                        <x-select label="Moneda" wire:model="moneda_cuenta">
                            <option value="PEN">SOLES</option>
                            <option value="USD">DÓLARES</option>
                        </x-select>
                    </div>
                    <x-input label="N° de cuenta principal (blanco)" wire:model="numero_cuenta" placeholder="215-00000000-0-00" />

                    <x-input label="Banco (cuenta secundaria)" wire:model="banco_secundario" placeholder="Donde se paga la diferencia" />
                    <div class="grid grid-cols-2 gap-2">
                        <x-select label="Tipo de cuenta" wire:model="tipo_cuenta_secundaria">
                            <option value="">—</option>
                            <option value="ahorros">AHORROS</option>
                            <option value="corriente">CORRIENTE</option>
                        </x-select>
                        <x-select label="Moneda" wire:model="moneda_cuenta_secundaria">
                            <option value="PEN">SOLES</option>
                            <option value="USD">DÓLARES</option>
                        </x-select>
                    </div>
                    <x-input label="N° de cuenta secundaria (diferencia)" wire:model="numero_cuenta_secundaria" />
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <x-button variant="secondary" wire:click="cerrarForm">Cancelar</x-button>
                <x-button wire:click="guardarContrato" wire:loading.attr="disabled">
                    <i class="fa fa-save"></i> {{ $esEdicion ? 'Actualizar contrato' : 'Crear contrato' }}
                </x-button>
            </div>
        @else
            <div class="py-12 text-center text-muted-foreground text-sm">
                Usa "Nuevo contrato" o "Editar" en un contrato de la lista para ver el formulario aquí.
            </div>
        @endif
    </div>
</div>
