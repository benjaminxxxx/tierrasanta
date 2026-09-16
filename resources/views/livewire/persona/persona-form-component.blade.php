<div>
    <x-dialog-modal wire:model="mostrarModalPersona" maxWidth="xl">
        <x-slot name="title">
            <x-title>
                {{ $paso === 1 ? 'Buscar Persona / Empresa' : ($personaId ? 'Editar Persona' : 'Registrar Persona') }}
            </x-title>
        </x-slot>

        <x-slot name="content">
            @if ($paso === 1)
                <!-- ── PASO 1: BÚSQUEDA ── -->
                <div class="space-y-6 py-4">
                    <div class="w-full">
                        <x-label value="Buscar Persona / Empresa por RUC, DNI o Nombre" />
                        <x-select-dropdown 
                            wire:model="personaSeleccionadaId" 
                            source="getPersonas"
                            placeholder="Escribe el RUC o Nombre..." 
                        />
                    </div>

                    <div class="relative flex py-2 items-center">
                        <div class="flex-grow border-t border-gray-300 dark:border-gray-700"></div>
                        <span class="flex-shrink mx-4 text-xs font-semibold uppercase text-gray-400">O bien</span>
                        <div class="flex-grow border-t border-gray-300 dark:border-gray-700"></div>
                    </div>

                    <div class="flex justify-center">
                        <x-button type="button" variant="primary" wire:click="nuevaPersona">
                            <i class="fa fa-plus mr-1"></i> Registrar Nueva Persona / Empresa
                        </x-button>
                    </div>
                </div>
            @else
                <!-- ── PASO 2: FORMULARIO ── -->
                <form wire:submit.prevent="guardar" class="space-y-4">
                    <!-- Selector de Tipo -->
                    <div class="flex gap-4 mb-4">
                        <label class="inline-flex items-center">
                            <input type="radio" wire:model.live="tipo" value="empresa" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 font-semibold">Empresa (RUC)</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" wire:model.live="tipo" value="individual" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 font-semibold">Persona Natural</span>
                        </label>
                    </div>

                    <!-- Documento -->
                    <div class="grid grid-cols-2 gap-4">
                        <x-input type="text" label="Tipo Doc." wire:model="tipo_documento" error="tipo_documento" />
                        <x-input type="text" label="N° Documento / RUC" wire:model="numero_documento" error="numero_documento" />
                    </div>

                    <!-- Campos según tipo de Persona -->
                    @if ($tipo === 'empresa')
                        <div>
                            <x-input type="text" label="Razón Social" wire:model="razon_social" class="uppercase" error="razon_social" />
                        </div>
                    @else
                        <div>
                            <x-input type="text" label="Nombres" wire:model="nombres" error="nombres" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <x-input type="text" label="Apellido Paterno" wire:model="apellido_paterno" error="apellido_paterno" />
                            <x-input type="text" label="Apellido Materno" wire:model="apellido_materno" error="apellido_materno" />
                        </div>
                    @endif

                    <!-- Contacto -->
                    <div class="grid grid-cols-2 gap-4">
                        <x-input type="text" label="Teléfono / Móvil" wire:model="telefono_movil" error="telefono_movil" />
                        <x-input type="email" label="Correo Electrónico" wire:model="email" error="email" />
                    </div>

                    <!-- Ubicación -->
                    <div>
                        <x-input type="text" label="Dirección Fiscal" wire:model="direccion" error="direccion" />
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <x-input type="text" label="Distrito" wire:model="distrito" error="distrito" />
                        <x-input type="text" label="Provincia" wire:model="provincia" error="provincia" />
                        <x-input type="text" label="Departamento" wire:model="departamento" error="departamento" />
                    </div>
                </form>
            @endif
        </x-slot>

        <x-slot name="footer">
            <div class="flex justify-between w-full">
                <div>
                    @if ($paso === 2)
                        <x-button type="button" variant="secondary" wire:click="$set('paso', 1)">
                            <i class="fa fa-arrow-left"></i> Volver a Búsqueda
                        </x-button>
                    @endif
                </div>
                <div class="flex gap-2">
                    <x-button type="button" variant="secondary" @click="$wire.set('mostrarModalPersona', false)">
                        Cancelar
                    </x-button>
                    @if ($paso === 2)
                        <x-button type="button" wire:click="guardarProveedorYSeleccionar">
                            <i class="fa fa-save"></i> {{ $personaId ? 'Actualizar y Seleccionar' : 'Guardar y Seleccionar' }}
                        </x-button>
                    @endif
                </div>
            </div>
        </x-slot>
    </x-dialog-modal>
    <x-loading wire:loading/>
</div>