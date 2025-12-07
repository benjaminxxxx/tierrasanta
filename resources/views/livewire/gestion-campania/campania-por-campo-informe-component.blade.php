<div>
    <x-card2 class="mt-4">
        
        <x-table class="!w-auto">
            <x-slot name="thead">

            </x-slot>
            <x-slot name="tbody">
                @include('livewire.gestion-campania.partials.campania-x-campo-selector-informacion-general')
                @include('livewire.gestion-campania.partials.campania-x-campo-poblacion-plantas')
                @include('livewire.gestion-campania.partials.campania-x-campo-brotes')
                @include('livewire.gestion-campania.partials.campania-x-campo-infestacion')
                @include('livewire.gestion-campania.partials.campania-x-campo-reinfestacion')
                @include('livewire.gestion-campania.partials.campania-x-campo-nutrientes')
            </x-slot>
        </x-table>
    </x-card2>
    
    <livewire:evaluaciones.evaluacion-poblacion-planta-form-component/>
    <livewire:evaluaciones.evaluacion-brotes-form-component/>
    <livewire:evaluaciones.evaluacion-infestacion-form-component/>
    <livewire:evaluaciones.evaluacion-reinfestacion-form-component/>
    <x-loading wire:loading />
</div>