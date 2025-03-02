<div>
    
    <x-loading wire:loading/>

    <x-card class="w-full">
        <x-spacing>
            
            <div class="flex justify-between items-center w-full">
                <x-secondary-button wire:click="fechaAnterior">
                    <i class="fa fa-chevron-left"></i> <span class="hidden lg:inline">Fecha Anterior</span>
                </x-secondary-button>

                <div class="lg:flex gap-4 w-full lg:w-auto text-center">
                    <x-input type="date" wire:model.live="fecha" class="text-center mx-2 !mt-0 !w-auto mb-3 lg:mb-0" />
                    <x-button @click="$dispatch('importarPlanilla')">Importar Empleados y Riegos</x-button>
                </div>

                <x-secondary-button wire:click="fechaPosterior" class="ml-3">
                    <span class="hidden lg:inline">Fecha Posterior</span> <i class="fa fa-chevron-right"></i>
                </x-secondary-button>
            </div>
        </x-spacing>
    </x-card>

    <livewire:reporte-diario-detalle-component wire:key="{{$fecha}}" :fecha="$fecha"/>

</div>
