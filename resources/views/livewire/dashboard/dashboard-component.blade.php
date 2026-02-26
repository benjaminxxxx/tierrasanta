<div class="space-y-6">
    <x-title>
        Panel Principal
    </x-title>
    <x-card>
        <x-flex class="w-full justify-between">

            <x-flex>
                <x-button variant="secondary" wire:click="mesAnterior">
                    <i class="fa fa-arrow-left"></i>
                </x-button>
                <x-select-meses class="w-auto" wire:model.live="mes" />
                <x-select-anios class="w-auto" wire:model.live="anio" />
                <x-button variant="secondary" wire:click="mesSiguiente">
                    <i class="fa fa-arrow-right"></i>
                </x-button>
            </x-flex>
            <div>
                <x-button variant="success" wire:click="actualizar">
                    <i class="fa fa-sync"></i> Actualizar
                </x-button>
            </div>
        </x-flex>
    </x-card>
    {{-- -Total de empleados --}}
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <x-stats-card title="Planilla Agraria" :value="$estadisticas['total_empleados_agraria']['valor'] ?? 0" icon="fa-users"
            description="Empleados activos en la empresa" :trend="$this->calcularTrend('total_empleados_agraria')" trendLabel="vs mes anterior" />
        <x-stats-card title="Planilla Oficina" :value="$estadisticas['total_empleados_oficina']['valor'] ?? 0" icon="fa-users"
            description="Empleados activos en la empresa" :trend="$this->calcularTrend('total_empleados_oficina')" trendLabel="vs mes anterior" />

    </div>
    <x-loading wire:loading />
</div>
