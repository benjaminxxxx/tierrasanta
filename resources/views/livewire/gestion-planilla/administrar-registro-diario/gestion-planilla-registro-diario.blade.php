<div>
    <x-card2 class="w-full">
            <div class="flex justify-between items-center w-full">
                <x-button variant="secondary" wire:click="fechaAnterior">
                    <i class="fa fa-chevron-left"></i> <span class="hidden lg:inline">Fecha Anterior</span>
                </x-button>

                <div class="lg:flex gap-4 lg:w-auto text-center">
                    <x-input type="date" wire:model.live="fecha" class="text-center !w-auto" />
                    <x-button wire:click="gestionarListaMensual">
                        <i class="fa fa-users"></i> Gestionar Lista
                    </x-button>
                </div>

                <x-button variant="secondary" wire:click="fechaPosterior">
                    <span class="hidden lg:inline">Fecha Posterior</span> <i class="fa fa-chevron-right"></i>
                </x-button>
            </div>
    </x-card2>
    
    <livewire:gestion-planilla.administrar-registro-diario.gestion-planilla-registro-diario-detalle-component  wire:key="{{$fecha}}" :fecha="$fecha"/>
    @include('livewire.gestion-planilla.administrar-registro-diario.partials.orden-planilla-mensual')
    <x-loading wire:loading />
</div>