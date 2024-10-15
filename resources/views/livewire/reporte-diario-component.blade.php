<div>
    
    <x-loading wire:loading/>

    <x-card class="w-full">
        <x-spacing>
            
            <div class="md:flex justify-between items-center w-full">
                <x-secondary-button wire:click="fechaAnterior">
                    <i class="fa fa-chevron-left"></i> Fecha Anterior
                </x-secondary-button>

                <div class="md:flex gap-4">
                    <x-input type="date" wire:model.live="fecha" class="text-center mx-2 !mt-0 !w-auto" />
                    <x-button @click="$dispatch('importarPlanilla')">Importar Empleados y Riegos</x-button>
                </div>

                <x-secondary-button wire:click="fechaPosterior" class="ml-3">
                    Fecha Posterior <i class="fa fa-chevron-right"></i>
                </x-secondary-button>
            </div>
        </x-spacing>
    </x-card>

    <livewire:reporte-diario-detalle-component wire:key="{{$fecha}}" :fecha="$fecha"/>

</div>
