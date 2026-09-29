<div>
    <x-dialog-modal wire:model="mostrarFormularioEmpleados" maxWidth="complete">
        <x-slot name="title">
            <div class="flex flex-wrap items-center gap-2">
                <x-h3>{{ $empleadoId ? $empleadoNombre : ($buscandoEmpleado ? 'Seleccionar empleado' : 'Registro de empleado') }}</x-h3>
                @if ($eliminado)
                    <span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Eliminado</span>
                @endif
            </div>
        </x-slot>

        <x-slot name="content">
            @if ($buscandoEmpleado && !$empleadoId)
                {{-- Abierto sin empleado (p. ej. "Crear contrato" del panel de contratos) --}}
                <div class="w-full md:w-[28rem] py-6">
                    <x-label value="Empleado" />
                    <x-select-dropdown wire:model.live="empleadoBuscadoId" source="getEmpleados"
                        placeholder="Buscar por nombre o DNI..." />
                </div>
            @else
                <div class="flex flex-col lg:flex-row gap-6 items-start">
                    {{-- Pestañas --}}
                    <nav class="flex lg:flex-col gap-1 shrink-0 lg:w-52 overflow-x-auto w-full">
                        @foreach (\App\Livewire\Planilla\Empleado\GestionPlanillaEmpleadosFormComponent::TABS as $clave => $etiqueta)
                            @php $habilitada = $empleadoId || $clave === 'datos'; @endphp
                            <button type="button" wire:click="$set('tab', '{{ $clave }}')" @disabled(!$habilitada)
                                title="{{ $habilitada ? '' : 'Guarda primero los datos personales' }}"
                                @class([
                                    'px-4 py-2 rounded-md text-left whitespace-nowrap transition font-semibold text-sm',
                                    'bg-muted text-foreground' => $tab === $clave,
                                    'text-muted-foreground hover:text-foreground' => $tab !== $clave && $habilitada,
                                    'opacity-40 cursor-not-allowed text-muted-foreground' => !$habilitada,
                                ])>
                                {{ $etiqueta }}
                            </button>
                        @endforeach
                    </nav>

                    {{-- Contenido de la pestaña activa --}}
                    <div class="flex-1 min-w-0 w-full">
                        @if ($tab === 'datos' || !$empleadoId)
                            <form wire:submit.prevent="guardarEmpleado" id="formPlanillaEmpleado">
                                @include('livewire.planilla.empleado.partials.form-empleado')
                            </form>
                            @if (!$empleadoId)
                                <p class="text-xs text-muted-foreground mt-4">
                                    Al guardar se habilitan las pestañas de perfil, contratos, sueldos, cargos y derecho habientes.
                                </p>
                            @endif
                        @elseif ($tab === 'perfil')
                            <livewire:planilla.empleado.empleado-perfil-component :empleado-id="$empleadoId" :key="'perfil-' . $empleadoId" />
                        @elseif ($tab === 'contratos')
                            <livewire:planilla.empleado.empleado-contratos-tab-component :empleado-id="$empleadoId" :key="'contratos-' . $empleadoId" />
                        @elseif ($tab === 'sueldos')
                            <livewire:planilla.empleado.empleado-sueldos-tab-component :empleado-id="$empleadoId" :key="'sueldos-' . $empleadoId" />
                        @elseif ($tab === 'cargos')
                            <livewire:planilla.empleado.empleado-cargos-tab-component :empleado-id="$empleadoId" :key="'cargos-' . $empleadoId" />
                        @elseif ($tab === 'familiares')
                            <livewire:planilla.empleado.empleado-familiares-tab-component :empleado-id="$empleadoId" :key="'familiares-' . $empleadoId" />
                        @endif
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" type="button" @click="$wire.set('mostrarFormularioEmpleados', false)">
                Cerrar
            </x-button>
            @if (($tab === 'datos' || !$empleadoId) && !$buscandoEmpleado)
                <x-button type="submit" form="formPlanillaEmpleado" class="ml-3">
                    <i class="fa fa-save"></i> Guardar datos
                </x-button>
            @endif
        </x-slot>
    </x-dialog-modal>

    {{-- Gestión de derecho habientes (la usa la pestaña "Derecho habientes") --}}
    <livewire:planilla.derecho-habiente.derecho-habiente-wizard-component />
    <livewire:planilla.derecho-habiente.derecho-habiente-form-component />

    <x-loading wire:loading />
</div>
