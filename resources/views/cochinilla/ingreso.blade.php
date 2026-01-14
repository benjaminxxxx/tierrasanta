<x-app-layout>
    
    <!--MODULO COCHINILLA INGRESO-->
    @livewire('cochinilla-ingreso-mapa-component')
    @livewire('cochinilla-ingreso-component')
    @livewire('cochinilla-ingreso-form-component')
    @livewire('cochinilla-ingreso-detalle-component')
    
    <livewire:gestion-cochinilla.cochinilla-venteado-form-component />
    <livewire:gestion-cochinilla.cochinilla-filtrado-form-component />
</x-app-layout>
