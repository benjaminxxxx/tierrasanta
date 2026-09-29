<x-app-layout>
    
    <!--MODULO COCHINILLA INGRESO-->
    @livewire('cochinilla.cochinilla-ingreso-mapa-component')
    @livewire('cochinilla.cochinilla-ingreso-component')
    @livewire('cochinilla.cochinilla-ingreso-detalle-component')
    
    <livewire:cochinilla.cochinilla-venteado-form-component />
    <livewire:cochinilla.cochinilla-filtrado-form-component />
</x-app-layout>
