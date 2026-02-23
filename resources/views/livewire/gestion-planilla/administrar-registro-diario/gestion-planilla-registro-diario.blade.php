<div>
    <x-flex class="justify-between">
        <x-flex>
            <x-title>
                Reporte Diario de Trabajo
            </x-title>
            <x-button variant="secondary" @click="$wire.dispatch('verLabores')">
                <i class="fa fa-eye"></i> Ver Labores
            </x-button>
        </x-flex>
        <div>
            {{-- -mostrar flag de que es domingo --}}
            @if (\Carbon\Carbon::parse($fecha)->dayOfWeek === 0)
                <x-danger class="animate-pulse">
                    Atención: Día Domingo
                </x-danger>
            @endif
        </div>
    </x-flex>
    <x-card class="w-full mt-4">
        <x-flex class="justify-between w-full">
            <x-button variant="secondary" wire:click="fechaAnterior">
                <i class="fa fa-chevron-left"></i> <span class="hidden lg:inline">Fecha Anterior</span>
            </x-button>

            <div class="lg:flex gap-4 lg:w-auto text-center">
                <x-selector-dia type="date" wire:model.live="fecha" class="text-center !w-auto" />
                <x-button @click="$wire.dispatch('mostrarModalOrdenPlanilla',{mes:{{ $mes }},anio:{{ $anio }}})">
                    <i class="fa fa-users"></i> Gestionar Lista
                </x-button>
            </div>

            <x-button variant="secondary" wire:click="fechaPosterior">
                <span class="hidden lg:inline">Fecha Posterior</span> <i class="fa fa-chevron-right"></i>
            </x-button>
        </x-flex>
    </x-card>

    <livewire:gestion-planilla.administrar-registro-diario.gestion-planilla-registro-diario-detalle-component
        wire:key="{{ $fecha }}" :fecha="$fecha" />
    @include('livewire.gestion-planilla.administrar-registro-diario.partials.orden-planilla-mensual')


    <x-loading wire:loading />
</div>
