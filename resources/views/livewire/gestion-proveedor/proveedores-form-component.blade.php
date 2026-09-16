<div>
    <x-dialog-modal wire:model="mostrarFormularioProveedores" maxWidth="2xl">
        <x-slot name="title">
            <x-title>
                {{ $proveedorId ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
            </x-title>
        </x-slot>

        <x-slot name="content">
            <form wire:submit.prevent="guardarProveedores" class="space-y-6">

                <!-- ── Seccion 1: Datos de Identidad (Persona) ── -->
                <div>
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-3">
                        Persona / Empresa
                    </span>

                    @if ($personaId)
                        <!-- Card cuando ya existe una persona asignada -->
                        <div
                            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase">
                                    {{ $personaRazonSocial }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    RUC / Doc: {{ $personaNumeroDocumento ?? 'Sin documento' }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- Botón Editar: Lanza evento para abrir Wizard de Persona en modo edición -->
                                <x-button type="button" variant="secondary" size="sm"
                                    @click="$wire.dispatch('abrirWizardPersona', { personaId: {{ $personaId }} })"
                                    title="Editar datos de Persona">
                                    <i class="fa fa-edit"></i>
                                </x-button>

                                <!-- Botón Quitar: Desvincula la persona y reinicia la selección -->
                                <x-button type="button" variant="danger" size="sm" wire:click="quitarPersona"
                                    title="Quitar selección">
                                    <i class="fa fa-times"></i>
                                </x-button>
                            </div>
                        </div>
                    @else
                        <!-- Estado vacío: Botón para iniciar el Wizard de Persona desde Paso 0 (Búsqueda) -->
                        <div
                            class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                            <p class="text-sm text-gray-500 mb-3">No has seleccionado ninguna persona o empresa.</p>
                            <x-button type="button" variant="secondary"
                                x-on:click="$dispatch('abrirWizardPersona', { modo: 'busqueda' })">
                                <i class="fa fa-search mr-1"></i> Buscar / Crear Persona
                            </x-button>
                        </div>
                    @endif

                    @error('personaId')
                        <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- ── Seccion 2: Estado ante SUNAT ── -->
                <div>
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-3">
                        Estado ante SUNAT
                    </span>
                    <div class="grid grid-cols-2 gap-5">
                        <x-input type="text" label="Tipo Contribuyente" wire:model="tipoContribuyente"
                            error="tipoContribuyente" />
                        <x-input type="text" label="Condición" wire:model="condicion" error="condicion" />
                        <x-input type="text" label="Estado Contribuyente" wire:model="estadoContribuyente"
                            error="estadoContribuyente" />
                        <x-input type="text" label="Estado Domicilio" wire:model="estadoDomicilio"
                            error="estadoDomicilio" />
                        <x-input type="date" label="Fecha Inscripción" wire:model="fechaInscripcion"
                            error="fechaInscripcion" />
                        <x-input type="date" label="Inicio de Actividades" wire:model="fechaInicioActividades"
                            error="fechaInicioActividades" />
                    </div>
                </div>

                <!-- ── Seccion 3: Actividad Económica ── -->
                <div>
                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 block mb-3">
                        Actividad Económica
                    </span>
                    <div class="grid grid-cols-2 gap-5">
                        <x-input type="text" label="CIIU" wire:model="ciiu" error="ciiu" />
                        <x-input type="text" label="Actividad Comercio Exterior"
                            wire:model="actividadComercioExterior" error="actividadComercioExterior" />
                    </div>
                </div>

            </form>
        </x-slot>

        <x-slot name="footer">
            <x-button type="button" variant="secondary" @click="$wire.set('mostrarFormularioProveedores', false)">
                Cancelar
            </x-button>
            <x-button type="submit" wire:click="guardarProveedores">
                <i class="fa fa-save"></i> Guardar
            </x-button>
        </x-slot>
    </x-dialog-modal>
    <livewire:persona.persona-form-component />
</div>
