<div>
    <x-card2 class="mt-4">
        
        <x-table class="!w-auto">
            <x-slot name="thead">

            </x-slot>
            <x-slot name="tbody">
                @include('livewire.gestion-campania.partials.campania-x-campo-selector-informacion-general')
                @include('livewire.gestion-campania.partials.campania-x-campo-poblacion-plantas')
                @include('livewire.gestion-campania.partials.campania-x-campo-brotes')
            </x-slot>
        </x-table>
    </x-card2>
    
    <livewire:evaluaciones.evaluacion-poblacion-planta-form-component/>
    <livewire:evaluaciones.evaluacion-brotes-form-component/>
    <x-loading wire:loading />
</div>