<div class="space-y-4">
    <div>
        <x-breadcrumb :items="$breadcrumb" />
    </div>
    <x-flex>
        <div>

            <x-title>
                Administración de Contratos
            </x-title>
            <x-subtitle>
                Gestiona contratos de empleados, vigencia y finalizaciones
            </x-subtitle>
        </div>
    </x-flex>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        {{-- Contratos Activos --}}
        <x-stat-card icon="fa-solid fa-circle-check" icon-color="text-emerald-500" label="Contratos Activos"
            :value="$statsActive" trend="↑ Sistema operativo" />

        {{-- En Período de Prueba --}}
        <x-stat-card icon="fa-solid fa-file-lines" icon-color="text-blue-500" label="En Período de Prueba"
            :value="$statsTrial" trend="Evaluación en progreso" />

        {{-- Por Vencer --}}
        <x-stat-card icon="fa-solid fa-triangle-exclamation" icon-color="text-amber-500" label="Por Vencer (30 días)"
            :value="$statsExpiring" trend="Requiere atención" />

        {{-- Finalizados --}}
        <x-stat-card icon="fa-solid fa-circle-check" icon-color="text-gray-500" label="Finalizados"
            :value="$statsTerminated" trend="Total histórico" />

    </div>

    {{-- Botones de Acción --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

        <x-action-button title="Ver Todos los Contratos" description="Listado completo con filtros y búsqueda"
            wire:click="navigate('list')" />

        <x-action-button title="Crear Contrato" description="Nuevo contrato para un empleado"
            @click="$wire.dispatch('abrirFormularioRegistroContrato')" />

        <x-action-button title="Empleados sin contratos"
            description="Hay {{ $personalSinContratos->count() }} empleados sin contrato"
            wire:click="mostrarListaSinContratos" />

    </div>

    {{-- Contratos Recientes --}}
    <x-card>
        <div class="px-6 py-4 border-b border-border bg-secondary/50">
            <h2 class="text-lg font-semibold text-foreground">
                Contratos Recientes
            </h2>
        </div>
        <div class="divide-y divide-border bg-card">
            @forelse($recentContracts as $contract)
                <div class="px-6 py-4 hover:bg-secondary/50 transition-colors cursor-pointer" wire:click="navigate('list')">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-foreground">
                                {{ $contract->empleado->nombre_completo ?? 'Empleado no asignado' }}
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                {{ $contract->cargo_codigo }} • {{ $contract->grupo_codigo }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="font-semibold text-foreground">
                                S/ {{ number_format($contract->compensacion_vacacional ?? 0, 2) }}
                            </div>
                            <div class="flex items-center gap-2 justify-end mt-1">
                                <x-status-badge :status="$contract->estado" />
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-muted-foreground text-sm">
                    No hay contratos registrados recientemente.
                </div>
            @endforelse
        </div>
    </x-card>

    <x-dialog-modal wire:model.live="mostrarModalSinContratos">
        <x-slot name="title">
            {{ __('Empleados sin contrato') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                @forelse($personalSinContratos as $empleado)
                    <x-resumen-item :label="$empleado->nombre_completo">
                        <!-- Botón para emitir el evento pasando el empleadoId -->
                        <x-button variant="secondary" size="sm"
                            @click="$wire.dispatch('abrirFormularioRegistroContrato', { empleadoId: {{ $empleado->id }} })">
                            Crear Contrato
                        </x-button>
                    </x-resumen-item>
                @empty
                    <div class="py-4 text-center text-sm text-gray-500">
                        No hay empleados pendientes de contrato.
                    </div>
                @endforelse
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-button variant="secondary" wire:click="$set('mostrarModalSinContratos', false)">
                {{ __('Cerrar') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <livewire:gestion-planilla.contratos-planilla-form-component />
</div>